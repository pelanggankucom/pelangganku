<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\LoyaltyProgram;
use App\Models\Merchant;
use App\Models\Reward;
use App\Models\StampTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Owner
        $owner = User::firstOrCreate(
            ['email' => 'owner@pelangganku.com'],
            [
                'name' => 'Owner Demo',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OWNER,
                'is_active' => true,
            ],
        );

        // Merchant 1: Toko Baju
        $merchant1 = Merchant::firstOrCreate(
            ['name' => 'Toko Baju Tanah Abang'],
            ['is_active' => true],
        );
        $merchant1->forceFill(['owner_user_id' => $owner->id])->save();
        if (!$owner->merchants()->where('merchant_id', $merchant1->id)->exists()) {
            $owner->merchants()->attach($merchant1->id, ['role' => 'owner']);
        }

        // Merchant 2: Toko Jam
        $merchant2 = Merchant::firstOrCreate(
            ['name' => 'Toko Jam Tangan Mayestik'],
            ['is_active' => true],
        );
        $merchant2->forceFill(['owner_user_id' => $owner->id])->save();
        if (!$owner->merchants()->where('merchant_id', $merchant2->id)->exists()) {
            $owner->merchants()->attach($merchant2->id, ['role' => 'owner']);
        }

        // Create branches for merchant1
        $branches = [];
        foreach (['Cabang Pusat', 'Cabang Sunter', 'Cabang Jakarta Selatan'] as $name) {
            $branches[$name] = Branch::firstOrCreate(
                ['merchant_id' => $merchant1->id, 'name' => $name],
                ['address' => 'Jl. Demo, Jakarta', 'is_active' => true],
            );
        }
        $mainBranch = $branches['Cabang Pusat'];

        // Merchant for loop (for merchant1 data)
        $merchant = $merchant1;

        // Cashiers
        $cashiers = [];
        foreach ($branches as $branchName => $branch) {
            $cashiers[$branchName] = User::firstOrCreate(
                ['email' => 'kasir.' . strtolower(str_replace(' ', '', $branchName)) . '@pelangganku.com'],
                [
                    'merchant_id' => $merchant->id,
                    'branch_id' => $branch->id,
                    'name' => 'Kasir ' . $branchName,
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_CASHIER,
                    'pin_hash' => Hash::make('1234'),
                    'is_active' => true,
                ],
            );
        }

        // Loyalty Program
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

        // Demo customers with realistic data
        $customerData = [
            ['name' => 'Budi Santoso', 'phone' => '081234567890', 'branch' => 'Cabang Pusat', 'stamps' => 8, 'redeemed' => 0],
            ['name' => 'Siti Nurhaliza', 'phone' => '082345678901', 'branch' => 'Cabang Pusat', 'stamps' => 5, 'redeemed' => 1],
            ['name' => 'Ahmad Wijaya', 'phone' => '083456789012', 'branch' => 'Cabang Pusat', 'stamps' => 12, 'redeemed' => 1],
            ['name' => 'Rina Kusuma', 'phone' => '084567890123', 'branch' => 'Cabang Sunter', 'stamps' => 3, 'redeemed' => 0],
            ['name' => 'Hendra Gunawan', 'phone' => '085678901234', 'branch' => 'Cabang Sunter', 'stamps' => 15, 'redeemed' => 2],
            ['name' => 'Maya Sari', 'phone' => '086789012345', 'branch' => 'Cabang Jakarta Selatan', 'stamps' => 6, 'redeemed' => 0],
            ['name' => 'Dedi Suryanto', 'phone' => '087890123456', 'branch' => 'Cabang Jakarta Selatan', 'stamps' => 9, 'redeemed' => 1],
            ['name' => 'Lina Wijaya', 'phone' => '088901234567', 'branch' => 'Cabang Pusat', 'stamps' => 0, 'redeemed' => 0],
            ['name' => 'Rudi Hartono', 'phone' => '089123456789', 'branch' => 'Cabang Sunter', 'stamps' => 7, 'redeemed' => 0],
            ['name' => 'Vina Puspita', 'phone' => '081567890123', 'branch' => 'Cabang Jakarta Selatan', 'stamps' => 4, 'redeemed' => 0],
            ['name' => 'Toni Kusuma', 'phone' => '081111111111', 'branch' => 'Cabang Pusat', 'stamps' => 20, 'redeemed' => 2],
            ['name' => 'Wati Handoko', 'phone' => '082222222222', 'branch' => 'Cabang Sunter', 'stamps' => 2, 'redeemed' => 0],
        ];

        foreach ($customerData as $data) {
            $phone = \App\Support\PhoneNumber::normalize($data['phone']);
            $branch = $branches[$data['branch']];

            $customer = Customer::firstOrCreate(
                ['merchant_id' => $merchant->id, 'phone_canonical' => $phone],
                [
                    'name' => $data['name'],
                    'phone_raw' => $data['phone'],
                    'created_branch_id' => $branch->id,
                ],
            );

            // Create or update balance
            $balance = CustomerBalance::firstOrCreate(
                ['customer_id' => $customer->id, 'loyalty_program_id' => $program->id],
                ['stamps_current' => 0, 'lifetime_stamps' => 0],
            );

            // Create stamp transactions to reach desired state
            $currentStamps = $balance->stamps_current ?? 0;
            $targetStamps = $data['stamps'];

            if ($currentStamps < $targetStamps) {
                $stampsToAdd = $targetStamps - $currentStamps;
                StampTransaction::firstOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'type' => StampTransaction::TYPE_EARN,
                        'idempotency_key' => "seed-{$customer->id}-earn",
                    ],
                    [
                        'branch_id' => $branch->id,
                        'stamps_delta' => $stampsToAdd,
                        'created_at' => now()->subDays(rand(1, 7)),
                    ],
                );
                $balance->update([
                    'stamps_current' => $targetStamps,
                    'lifetime_stamps' => $balance->lifetime_stamps + $stampsToAdd,
                ]);
            }

            // Add redeem transactions
            for ($i = 0; $i < $data['redeemed']; $i++) {
                StampTransaction::firstOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'type' => StampTransaction::TYPE_REDEEM,
                        'idempotency_key' => "seed-{$customer->id}-redeem-{$i}",
                    ],
                    [
                        'branch_id' => $branch->id,
                        'stamps_delta' => 5,
                        'created_at' => now()->subDays(rand(1, 14)),
                    ],
                );
            }
        }
    }
}
