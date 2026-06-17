<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\StampTransaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OwnerController extends Controller
{
    /** Beranda — ringkasan sederhana satu toko. */
    public function dashboard(Request $request): View
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403, 'Toko tidak ditemukan');

        $mid = $merchant->id;
        $cardSize = $merchant->activeProgram()?->card_size ?? 10;

        // Periode: hari ini atau bulan ini (default bulan ini).
        $period = $request->get('periode') === 'hari' ? 'hari' : 'bulan';
        $from = $period === 'hari' ? now()->startOfDay() : now()->startOfMonth();

        $cust = fn () => Customer::where('merchant_id', $mid);
        $txn = fn () => StampTransaction::whereHas('customer', fn ($q) => $q->where('merchant_id', $mid));

        // Angka-angka utama (bahasa sederhana).
        $totalCustomers = $cust()->count();
        $newCustomers = $cust()->where('created_at', '>=', $from)->count();

        // Pelanggan setia = pernah menukar hadiah.
        $loyalCount = $cust()
            ->whereHas('transactions', fn ($q) => $q->where('type', StampTransaction::TYPE_REDEEM))
            ->count();

        $visits = $txn()->where('type', StampTransaction::TYPE_EARN)->where('created_at', '>=', $from)->count();
        $rewardsGiven = $txn()->where('type', StampTransaction::TYPE_REDEEM)->where('created_at', '>=', $from)->count();

        // Hampir dapat hadiah (tinggal <= 2 stempel).
        $almostThreshold = max(1, $cardSize - 2);
        $almostDone = $cust()
            ->whereHas('balances', fn ($q) => $q->where('stamps_current', '>=', $almostThreshold)->where('stamps_current', '<', $cardSize))
            ->count();

        // Pelanggan paling rajin (3 besar).
        $topLoyal = $cust()
            ->withSum('balances as lifetime', 'lifetime_stamps')
            ->orderByDesc('lifetime')
            ->limit(3)
            ->get();

        $storeCount = auth()->user()->merchants()->count();

        return view('owner.dashboard', compact(
            'merchant', 'storeCount', 'period',
            'totalCustomers', 'newCustomers', 'loyalCount',
            'visits', 'rewardsGiven', 'almostDone', 'topLoyal',
        ));
    }

    /** Atur — menu sederhana (bukan tab bertingkat). */
    public function settings(): View
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        return view('owner.settings', [
            'merchant' => $merchant,
            'storeCount' => auth()->user()->merchants()->count(),
            'branchCount' => $merchant->branches()->count(),
            'cashierCount' => $merchant->users()->where('role', 'cashier')->count(),
            'rewardCount' => $merchant->activeProgram()?->rewards()->count() ?? 0,
        ]);
    }

    /** Daftar pelanggan — stempel, jumlah tukar, & kapan terakhir hadir. */
    public function customers(Request $request): View
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        $sebelum = $request->get('sebelum'); // Y-m-d

        $customers = Customer::where('merchant_id', $merchant->id)
            ->withSum('balances as stamps_total', 'stamps_current')
            ->get();

        $ids = $customers->pluck('id');

        // Berapa kali tukar hadiah, per pelanggan.
        $redeems = StampTransaction::whereIn('customer_id', $ids)
            ->where('type', StampTransaction::TYPE_REDEEM)
            ->selectRaw('customer_id, COUNT(*) as c')
            ->groupBy('customer_id')
            ->pluck('c', 'customer_id');

        // Kunjungan terakhir (transaksi stempel terakhir), per pelanggan.
        $lastVisits = StampTransaction::whereIn('customer_id', $ids)
            ->where('type', StampTransaction::TYPE_EARN)
            ->selectRaw('customer_id, MAX(created_at) as t')
            ->groupBy('customer_id')
            ->pluck('t', 'customer_id');

        $customers->each(function ($c) use ($redeems, $lastVisits) {
            $c->redeem_count = (int) ($redeems[$c->id] ?? 0);
            $c->last_visit = $lastVisits[$c->id] ?? null;
        });

        // Lama tidak hadir di atas (belum pernah hadir paling atas).
        $customers = $customers->sortBy(fn ($c) => $c->last_visit ?? '0000-00-00')->values();

        if ($sebelum) {
            $cut = \Carbon\Carbon::parse($sebelum)->endOfDay();
            $customers = $customers
                ->filter(fn ($c) => ! $c->last_visit || \Carbon\Carbon::parse($c->last_visit)->lte($cut))
                ->values();
        }

        return view('owner.customers', compact('merchant', 'customers', 'sebelum'));
    }

    /** Profil akun owner (nama, telepon, ganti password). */
    public function profile(): View
    {
        return view('owner.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:4|confirmed',
        ]);

        $user->name = $data['name'];
        $user->phone = $data['phone'] ?? null;
        if (! empty($data['password'])) {
            $user->password = $data['password']; // di-hash otomatis (cast 'hashed')
        }
        $user->save();

        return redirect()->route('owner.profile')->with('success', 'Profil kamu berhasil diperbarui.');
    }

    public function storeCashier(Request $request): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'branch_id' => 'required|exists:branches,id',
            'pin' => 'required|digits:4',
        ]);

        User::create([
            'merchant_id' => $merchant->id,
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['pin']), // kasir login: email + PIN
            'pin_hash' => Hash::make($data['pin']),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        return redirect()->route('owner.branches')->with('success', 'Kasir berhasil ditambahkan.');
    }

    public function destroyCashier(User $user): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant || $user->merchant_id !== $merchant->id, 403);

        $user->delete();

        return redirect()->route('owner.branches')->with('success', 'Kasir dihapus.');
    }
}
