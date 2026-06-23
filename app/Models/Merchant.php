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
        'pos_granted_by_admin', 'pos_admin_expires_at',
        'finance_granted_by_admin', 'finance_admin_expires_at',
        'pos_trial_used_at', 'finance_trial_used_at',
        'printer_settings',
    ];

    protected $casts = [
        'is_active'                  => 'boolean',
        'pos_granted_by_admin'       => 'boolean',
        'pos_admin_expires_at'       => 'datetime',
        'finance_granted_by_admin'   => 'boolean',
        'finance_admin_expires_at'   => 'datetime',
        'pos_trial_used_at'          => 'datetime',
        'finance_trial_used_at'      => 'datetime',
        'printer_settings'           => 'array',
    ];

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

    public function posSubscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\PosSubscription::class);
    }

    public function financeSubscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\FinanceSubscription::class);
    }

    public function printerSettings(): array
    {
        $defaults = [
            'footer_text'   => 'Terima kasih sudah berbelanja!',
            'show_address'  => true,
            'show_whatsapp' => true,
            'auto_print'    => false,
        ];
        return array_merge($defaults, $this->printer_settings ?? []);
    }

    public function hasPosAccess(): bool
    {
        if ($this->pos_granted_by_admin) {
            // Tanpa tanggal expired = akses selamanya
            if ($this->pos_admin_expires_at === null) return true;
            return $this->pos_admin_expires_at->isFuture();
        }
        $sub = $this->posSubscription;
        return $sub && $sub->isActive();
    }

    public function hasFinanceAccess(): bool
    {
        if ($this->finance_granted_by_admin) {
            if ($this->finance_admin_expires_at === null) return true;
            return $this->finance_admin_expires_at->isFuture();
        }
        $sub = $this->financeSubscription;
        return $sub && $sub->isActive();
    }
}
