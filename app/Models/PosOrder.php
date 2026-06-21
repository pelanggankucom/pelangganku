<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosOrder extends Model
{
    protected $fillable = [
        'merchant_id', 'branch_id', 'user_id', 'customer_id',
        'order_number', 'subtotal', 'discount', 'total',
        'payment_method', 'status', 'note',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosOrderItem::class);
    }

    public static function generateOrderNumber(int $merchantId): string
    {
        $prefix = 'TRX' . now()->format('ymd');
        $last   = static::where('merchant_id', $merchantId)
                        ->whereDate('created_at', today())
                        ->count();
        return $prefix . '-' . str_pad($last + 1, 3, '0', STR_PAD_LEFT);
    }
}
