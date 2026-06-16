<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reward extends Model
{
    protected $fillable = [
        'loyalty_program_id', 'name', 'milestone', 'image_path', 'terms', 'cost_stamps', 'is_active',
    ];

    protected $casts = [
        'milestone' => 'integer',
        'cost_stamps' => 'integer',
        'is_active' => 'boolean',
    ];

    /** URL gambar hadiah (atau null). */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? \Illuminate\Support\Facades\Storage::url($this->image_path) : null;
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }
}
