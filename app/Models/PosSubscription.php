<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSubscription extends Model
{
    protected $fillable = [
        'merchant_id', 'status', 'starts_at', 'expires_at',
        'doku_invoice_number', 'doku_payment_url', 'amount',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public function daysLeft(): int
    {
        if (!$this->isActive()) return 0;
        return (int) now()->diffInDays($this->expires_at);
    }
}
