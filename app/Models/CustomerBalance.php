<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerBalance extends Model
{
    protected $fillable = [
        'customer_id', 'loyalty_program_id', 'stamps_current', 'lifetime_stamps',
    ];

    protected $casts = [
        'stamps_current' => 'integer',
        'lifetime_stamps' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }
}
