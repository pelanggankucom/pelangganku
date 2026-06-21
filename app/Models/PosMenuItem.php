<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosMenuItem extends Model
{
    protected $fillable = [
        'merchant_id', 'name', 'category', 'price', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
