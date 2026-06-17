<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';
    public const ROLE_CASHIER = 'cashier';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'merchant_id',
        'branch_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'pin_hash',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'merchant_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Toko yang sedang aktif untuk user ini.
     * - Kasir: terikat ke satu toko lewat kolom merchant_id.
     * - Owner: toko yang dipilih (session), atau toko pertama yang dimiliki.
     */
    public function currentMerchant(): ?Merchant
    {
        if ($this->isCashier() && $this->merchant_id) {
            return $this->merchant;
        }

        $id = session('selected_merchant_id');
        if ($id && ($m = $this->merchants()->find($id))) {
            return $m;
        }

        return $this->merchants()->first() ?? $this->merchant;
    }

    public function currentMerchantId(): ?int
    {
        return $this->currentMerchant()?->id;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isCashier(): bool
    {
        return $this->role === self::ROLE_CASHIER;
    }
}
