<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Reward;
use App\Services\LoyaltyService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class KasirController extends Controller
{
    public function __construct(private LoyaltyService $loyalty)
    {
    }

    /** Layar utama: numpad. */
    public function numpad(): View
    {
        $merchant = auth()->user()->currentMerchant();

        return view('kasir.numpad', [
            'merchant' => $merchant,
            'program' => $merchant?->activeProgram(),
        ]);
    }

    /** Cek nomor → profil (jika ada) atau form daftar (jika baru). */
    public function lookup(Request $request): RedirectResponse
    {
        $request->validate(['phone' => ['required', 'string']]);

        $canonical = PhoneNumber::normalize($request->input('phone'));
        if ($canonical === null) {
            return back()->withInput()->with('error', 'Nomor telepon tidak valid.');
        }

        $customer = Customer::where('merchant_id', auth()->user()->currentMerchantId())
            ->where('phone_canonical', $canonical)
            ->first();

        if ($customer) {
            return redirect()->route('kasir.profile', $customer);
        }

        return redirect()->route('kasir.register.form', ['phone' => $canonical]);
    }

    /** Form pendaftaran cepat (nomor sudah terisi). */
    public function registerForm(Request $request): View
    {
        $canonical = PhoneNumber::normalize($request->query('phone'));

        return view('kasir.register', [
            'phone_canonical' => $canonical,
            'phone_display' => $canonical ? '0' . substr($canonical, 2) : '',
        ]);
    }

    /** Simpan pelanggan baru → langsung ke profil. */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string'],
        ]);

        $canonical = PhoneNumber::normalize($data['phone']);
        if ($canonical === null) {
            return back()->withInput()->with('error', 'Nomor telepon tidak valid.');
        }

        $user = auth()->user();

        // Anti-duplikasi: kalau sudah ada, buka profil yang ada.
        $customer = Customer::firstOrCreate(
            ['merchant_id' => $user->currentMerchantId(), 'phone_canonical' => $canonical],
            [
                'name' => $data['name'],
                'phone_raw' => $data['phone'],
                'created_branch_id' => $user->branch_id,
            ],
        );

        return redirect()->route('kasir.profile', $customer)
            ->with('success', "Pelanggan {$customer->name} terdaftar.");
    }

    /** Halaman profil loyalitas pelanggan. */
    public function profile(Customer $customer): View
    {
        $this->authorizeCustomer($customer);

        $program = auth()->user()->currentMerchant()?->activeProgram();
        $balance = $program ? $customer->balanceFor($program) : null;

        return view('kasir.profile', [
            'customer' => $customer,
            'program' => $program,
            'balance' => $balance,
            'rewardStatuses' => $program ? $this->loyalty->rewardStatuses($customer, $program) : [],
        ]);
    }

    /** Beri stempel. */
    public function stamp(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        $request->validate([
            'amount' => ['nullable', 'integer', 'min:1', 'max:50'],
            'idempotency_key' => ['nullable', 'string'],
        ]);

        $program = auth()->user()->currentMerchant()?->activeProgram();
        if (! $program) {
            return back()->with('error', 'Program loyalitas belum diatur owner.');
        }

        $result = $this->loyalty->giveStamp(
            $customer,
            $program,
            (int) $request->input('amount', 1),
            auth()->user(),
            $request->input('idempotency_key'),
        );

        $msg = $result['duplicate']
            ? 'Stempel sudah diproses.'
            : 'Stempel berhasil ditambahkan!';

        return redirect()->route('kasir.profile', $customer)->with('popup', $msg);
    }

    /** Tukar hadiah pada milestone-nya. */
    public function redeem(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        $data = $request->validate([
            'reward_id' => ['required', 'integer'],
            'idempotency_key' => ['nullable', 'string'],
        ]);

        $program = auth()->user()->currentMerchant()?->activeProgram();
        if (! $program) {
            return back()->with('error', 'Program loyalitas belum diatur owner.');
        }

        $reward = Reward::where('loyalty_program_id', $program->id)
            ->where('id', $data['reward_id'])
            ->firstOrFail();

        try {
            $this->loyalty->claimReward($customer, $program, $reward, auth()->user(), $data['idempotency_key'] ?? null);
        } catch (RuntimeException $e) {
            return redirect()->route('kasir.profile', $customer)->with('error', $e->getMessage());
        }

        return redirect()->route('kasir.profile', $customer)
            ->with('success', "Hadiah \"{$reward->name}\" berhasil ditukar!");
    }

    /** Mulai kartu baru (reset stempel pelanggan). */
    public function reset(Customer $customer): RedirectResponse
    {
        $this->authorizeCustomer($customer);

        $program = auth()->user()->currentMerchant()?->activeProgram();
        if ($program) {
            $this->loyalty->resetCard($customer, $program);
        }

        return redirect()->route('kasir.profile', $customer)->with('success', 'Kartu baru dimulai.');
    }

    /** Pastikan pelanggan milik merchant kasir yang login. */
    private function authorizeCustomer(Customer $customer): void
    {
        abort_unless($customer->merchant_id === auth()->user()->currentMerchantId(), 404);
    }
}
