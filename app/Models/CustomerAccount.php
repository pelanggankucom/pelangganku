<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CustomerAccount extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'phone_canonical', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    /** Semua kartu loyalty (per merchant) milik nomor ini. */
    public function cards()
    {
        return Customer::where('phone_canonical', $this->phone_canonical);
    }
}
