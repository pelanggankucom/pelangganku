<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosSubscription;
use App\Services\LoyaltyService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(private LoyaltyService $loyalty)
    {
    }

    public function show(): View|RedirectResponse
    {
        $user     = auth()->user();
        $merchant = $user->currentMerchant();

        if (!$this->hasActivePos($merchant->id)) {
            return redirect()->route('owner.pos')
                ->with('error', 'POS belum aktif. Aktifkan berlangganan terlebih dahulu.');
        }

        $menuItems = \App\Models\PosMenuItem::where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->orderBy('category')->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'category', 'price']);

        return view('kasir.pos', [
            'merchant'       => $merchant,
            'menuItems'      => $menuItems,
            'printerSettings'=> $merchant->printerSettings(),
        ]);
    }

    public function lookupCustomer(Request $request): JsonResponse
    {
        $merchant = auth()->user()->currentMerchant();
        if (! $this->hasActivePos($merchant->id)) {
            return response()->json(['found' => false], 403);
        }

        $data      = $request->validate(['phone' => ['required', 'string']]);
        $canonical = PhoneNumber::normalize($data['phone']);
        if (! $canonical) {
            return response()->json(['found' => false, 'invalid' => true]);
        }

        $customer = Customer::where('merchant_id', $merchant->id)
            ->where('phone_canonical', $canonical)
            ->first();

        if (! $customer) {
            return response()->json(['found' => false]);
        }

        $program = $merchant->activeProgram();
        $stamps  = 0;
        $cardSize = 0;
        $rewards  = [];

        if ($program) {
            $balance  = $customer->balanceFor($program);
            $stamps   = $balance->stamps_current;
            $cardSize = $program->card_size;
            $rewards  = $program->activeRewards()->get(['name', 'milestone'])->toArray();
        }

        return response()->json([
            'found'          => true,
            'name'           => $customer->name,
            'stamps_current' => $stamps,
            'card_size'      => $cardSize,
            'rewards'        => $rewards,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $merchant = $user->currentMerchant();

        if (!$this->hasActivePos($merchant->id)) {
            return response()->json(['error' => 'POS tidak aktif.'], 403);
        }

        $data = $request->validate([
            'items'           => ['required', 'array', 'min:1'],
            'items.*.name'    => ['required', 'string', 'max:120'],
            'items.*.qty'     => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.price'   => ['required', 'integer', 'min:0'],
            'discount'        => ['nullable', 'integer', 'min:0'],
            'payment_method'  => ['required', 'in:cash,qris,transfer'],
            'note'            => ['nullable', 'string', 'max:255'],
            'phone'           => ['nullable', 'string'],
            'register_name'   => ['nullable', 'string', 'max:100'],
        ]);

        // Hitung total
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }
        $discount = (int) ($data['discount'] ?? 0);
        $total    = max(0, $subtotal - $discount);

        // Cari atau daftarkan customer berdasarkan nomor HP
        $customerId = null;
        $customer   = null;
        if (!empty($data['phone'])) {
            $canonical = PhoneNumber::normalize($data['phone']);
            if ($canonical) {
                $customer = Customer::where('merchant_id', $merchant->id)
                    ->where('phone_canonical', $canonical)
                    ->first();

                // Daftarkan sebagai pelanggan baru jika belum ada dan nama disertakan
                if (! $customer && !empty($data['register_name'])) {
                    $customer = Customer::create([
                        'merchant_id'       => $merchant->id,
                        'name'              => $data['register_name'],
                        'phone_raw'         => $data['phone'],
                        'phone_canonical'   => $canonical,
                        'created_branch_id' => $user->branch_id,
                    ]);
                }

                $customerId = $customer?->id;
            }
        }

        $order = PosOrder::create([
            'merchant_id'    => $merchant->id,
            'branch_id'      => $user->branch_id,
            'user_id'        => $user->id,
            'customer_id'    => $customerId,
            'order_number'   => PosOrder::generateOrderNumber($merchant->id),
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'total'          => $total,
            'payment_method' => $data['payment_method'],
            'status'         => 'paid',
            'note'           => $data['note'] ?? null,
        ]);

        foreach ($data['items'] as $item) {
            PosOrderItem::create([
                'pos_order_id' => $order->id,
                'name'         => $item['name'],
                'qty'          => $item['qty'],
                'price'        => $item['price'],
                'subtotal'     => $item['qty'] * $item['price'],
            ]);
        }

        // Beri 1 stamp otomatis ke pelanggan yang terhubung
        $loyalty = null;
        if ($customer) {
            $program = $merchant->activeProgram();
            if ($program) {
                try {
                    $result  = $this->loyalty->giveStamp($customer, $program, 1, $user);
                    $balance = $result['balance'];
                    $rewards = $program->activeRewards()->get(['name', 'milestone'])->toArray();
                    $loyalty = [
                        'customer_name'  => $customer->name,
                        'stamp_added'    => $result['duplicate'] ? 0 : 1,
                        'stamps_current' => $balance->stamps_current,
                        'card_size'      => $program->card_size,
                        'rewards'        => $rewards,
                        'is_new'         => !empty($data['register_name']),
                    ];
                } catch (\Throwable $e) {
                    \Log::error('Gagal beri stempel POS', [
                        'customer_id' => $customer->id,
                        'program_id'  => $program->id,
                        'error'       => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json([
            'order_id'       => $order->id,
            'order_number'   => $order->order_number,
            'total'          => $order->total,
            'subtotal'       => $order->subtotal,
            'payment_method' => $order->payment_method,
            'merchant_name'  => $merchant->name,
            'merchant_addr'  => $merchant->address,
            'merchant_wa'    => $merchant->whatsapp,
            'kasir_name'     => $user->name,
            'items'          => $order->items()->get(['name', 'qty', 'price', 'subtotal']),
            'discount'       => $order->discount,
            'created_at'     => $order->created_at->format('d M Y H:i'),
            'loyalty'        => $loyalty,
        ]);
    }

    private function hasActivePos(int $merchantId): bool
    {
        $merchant = \App\Models\Merchant::find($merchantId);
        return $merchant && $merchant->hasPosAccess();
    }
}
