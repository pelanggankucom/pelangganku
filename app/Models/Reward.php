<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reward extends Model
{
    protected $fillable = ['loyalty_program_id', 'name', 'cost_stamps', 'is_active'];

    protected $casts = [
        'cost_stamps' => 'integer',
        'is_active' => 'boolean',
    ];

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }
}
