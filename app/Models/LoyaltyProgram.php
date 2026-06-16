<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyProgram extends Model
{
    protected $fillable = [
        'merchant_id', 'name', 'card_size', 'stamps_per_reward',
        'earn_rule', 'amount_per_stamp', 'carry_over', 'is_active',
    ];

    protected $casts = [
        'card_size' => 'integer',
        'stamps_per_reward' => 'integer',
        'amount_per_stamp' => 'integer',
        'carry_over' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function activeRewards(): HasMany
    {
        return $this->rewards()->where('is_active', true)->orderBy('milestone');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }
}
