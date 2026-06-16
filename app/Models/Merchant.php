<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    protected $fillable = [
        'name', 'address', 'phone', 'logo_path', 'photo_path',
        'instagram', 'whatsapp', 'facebook', 'tiktok', 'website',
        'owner_user_id', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? \Illuminate\Support\Facades\Storage::url($this->logo_path) : null;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? \Illuminate\Support\Facades\Storage::url($this->photo_path) : null;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function loyaltyPrograms(): HasMany
    {
        return $this->hasMany(LoyaltyProgram::class);
    }

    public function activeProgram(): ?LoyaltyProgram
    {
        return $this->loyaltyPrograms()->where('is_active', true)->first();
    }
}
