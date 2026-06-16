<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\LoyaltyProgram;
use App\Models\Merchant;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data demo (idempotent — aman dijalankan berulang).
     */
    public function run(): void
    {
        $merchant = Merchant::firstOrCreate(
            ['name' => 'Toko Demo Pelangganku'],
            ['is_active' => true],
        );

        $branch = Branch::firstOrCreate(
            ['merchant_id' => $merchant->id, 'name' => 'Cabang Pusat'],
            ['address' => 'Jl. Contoh No. 1', 'is_active' => true],
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner@pelangganku.com'],
            [
                'merchant_id' => $merchant->id,
                'name' => 'Owner Demo',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OWNER,
                'is_active' => true,
            ],
        );

        $merchant->forceFill(['owner_user_id' => $owner->id])->save();

        User::firstOrCreate(
            ['email' => 'kasir@pelangganku.com'],
            [
                'merchant_id' => $merchant->id,
                'branch_id' => $branch->id,
                'name' => 'Kasir Demo',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CASHIER,
                'pin_hash' => Hash::make('1234'),
                'is_active' => true,
            ],
        );

        $program = LoyaltyProgram::firstOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'name' => 'Program Stempel',
                'card_size' => 10,
                'stamps_per_reward' => 10,
                'earn_rule' => 'per_visit',
                'carry_over' => true,
                'is_active' => true,
            ],
        );

        Reward::firstOrCreate(
            ['loyalty_program_id' => $program->id, 'milestone' => 5],
            ['name' => 'Snack Gratis', 'cost_stamps' => 5, 'is_active' => true],
        );

        Reward::firstOrCreate(
            ['loyalty_program_id' => $program->id, 'milestone' => 10],
            ['name' => '1 Produk Gratis', 'cost_stamps' => 10, 'is_active' => true],
        );
    }
}
