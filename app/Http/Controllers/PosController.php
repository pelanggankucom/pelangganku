<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosSubscription;
use App\Services\LoyaltyService;
use App\Support\PhoneNumber;
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

        return view('kasir.pos', compact('merchant'));
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $user     = auth()->user();
        $merchant = $user->currentMerchant();

        if (!$this->hasActivePos($merchant->id)) {
            return response()->json(['error' => 'POS tidak aktif.'], 403);
        }

        $data = $request->validate([
            'items'          => ['required', 'array', 'min:1'],
            'items.*.name'   => ['required', 'string', 'max:120'],
            'items.*.qty'    => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.price'  => ['required', 'integer', 'min:0'],
            'discount'       => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
            'note'           => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string'],
        ]);

        // Hitung total
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }
        $discount = (int) ($data['discount'] ?? 0);
        $total    = max(0, $subtotal - $discount);

        // Cari customer (jika ada nomor HP)
        $customerId = null;
        if (!empty($data['phone'])) {
            $canonical = PhoneNumber::normalize($data['phone']);
            if ($canonical) {
                $customer   = Customer::where('merchant_id', $merchant->id)
                                      ->where('phone_canonical', $canonical)
                                      ->first();
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

        return response()->json([
            'order_number'   => $order->order_number,
            'total'          => $order->total,
            'payment_method' => $order->payment_method,
            'merchant_name'  => $merchant->name,
            'kasir_name'     => $user->name,
            'items'          => $order->items()->get(['name', 'qty', 'price', 'subtotal']),
            'discount'       => $order->discount,
            'created_at'     => $order->created_at->format('d M Y H:i'),
        ]);
    }

    private function hasActivePos(int $merchantId): bool
    {
        $merchant = \App\Models\Merchant::find($merchantId);
        return $merchant && $merchant->hasPosAccess();
    }
}
