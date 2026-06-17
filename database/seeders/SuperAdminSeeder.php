<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '628000000001'],
            [
                'name'      => 'Super Admin',
                'email'     => 'admin@pelangganku.com',
                'phone'     => '628000000001',
                'password'  => Hash::make('superadmin123'),
                'role'      => User::ROLE_SUPERADMIN,
                'is_active' => true,
            ]
        );
    }
}
