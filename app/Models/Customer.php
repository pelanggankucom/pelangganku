<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'merchant_id', 'name', 'phone_canonical', 'phone_raw', 'dob', 'created_branch_id',
    ];

    protected $casts = ['dob' => 'date'];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(CustomerBalance::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StampTransaction::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(RewardClaim::class);
    }

    public function hasClaimed(Reward $reward): bool
    {
        return $this->claims()->where('reward_id', $reward->id)->exists();
    }

    public function balanceFor(LoyaltyProgram $program): CustomerBalance
    {
        return $this->balances()->firstOrCreate(
            ['loyalty_program_id' => $program->id],
            ['stamps_current' => 0, 'lifetime_stamps' => 0],
        );
    }

    /** Nomor telepon yang disamarkan untuk tampilan kasir, mis. 0812****890. */
    public function getPhoneMaskedAttribute(): string
    {
        $local = '0' . substr($this->phone_canonical, 2); // 628... -> 08...
        if (strlen($local) <= 7) {
            return $local;
        }

        return substr($local, 0, 4) . str_repeat('*', 4) . substr($local, -3);
    }
}
