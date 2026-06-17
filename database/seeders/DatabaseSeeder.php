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

        Reward::updateOrCreate(
            ['loyalty_program_id' => $program->id, 'milestone' => 5],
            ['name' => 'Snack Gratis', 'cost_stamps' => 5, 'value' => 15000, 'terms' => 'Berlaku untuk 1 snack reguler.', 'is_active' => true],
        );

        Reward::updateOrCreate(
            ['loyalty_program_id' => $program->id, 'milestone' => 10],
            ['name' => '1 Produk Gratis', 'cost_stamps' => 10, 'value' => 50000, 'terms' => 'Maks. 1 produk, tidak bisa diuangkan.', 'is_active' => true],
        );

        $rewardList = Reward::where('loyalty_program_id', $program->id)->orderBy('milestone')->get();

        // Bersihkan transaksi demo lama supaya tidak dobel saat re-seed (deploy auto-seed).
        StampTransaction::where('idempotency_key', 'like', 'seed-%')->delete();

        // 'visits' = berapa kali pelanggan datang (>=2 = pelanggan lama).
        $customerData = [
            ['name' => 'Budi Santoso', 'phone' => '081234567890', 'branch' => 'Cabang Pusat', 'visits' => 4, 'redeemed' => 1],
            ['name' => 'Siti Nurhaliza', 'phone' => '082345678901', 'branch' => 'Cabang Pusat', 'visits' => 1, 'redeemed' => 0],
            ['name' => 'Ahmad Wijaya', 'phone' => '083456789012', 'branch' => 'Cabang Pusat', 'visits' => 5, 'redeemed' => 2],
            ['name' => 'Rina Kusuma', 'phone' => '084567890123', 'branch' => 'Cabang Sunter', 'visits' => 1, 'redeemed' => 0],
            ['name' => 'Hendra Gunawan', 'phone' => '085678901234', 'branch' => 'Cabang Sunter', 'visits' => 6, 'redeemed' => 2],
            ['name' => 'Maya Sari', 'phone' => '086789012345', 'branch' => 'Cabang Jakarta Selatan', 'visits' => 1, 'redeemed' => 0],
            ['name' => 'Dedi Suryanto', 'phone' => '087890123456', 'branch' => 'Cabang Jakarta Selatan', 'visits' => 3, 'redeemed' => 1],
            ['name' => 'Lina Wijaya', 'phone' => '088901234567', 'branch' => 'Cabang Pusat', 'visits' => 1, 'redeemed' => 0],
            ['name' => 'Rudi Hartono', 'phone' => '089123456789', 'branch' => 'Cabang Sunter', 'visits' => 2, 'redeemed' => 0],
            ['name' => 'Vina Puspita', 'phone' => '081567890123', 'branch' => 'Cabang Jakarta Selatan', 'visits' => 1, 'redeemed' => 0],
            ['name' => 'Toni Kusuma', 'phone' => '081111111111', 'branch' => 'Cabang Pusat', 'visits' => 4, 'redeemed' => 1],
            ['name' => 'Wati Handoko', 'phone' => '082222222222', 'branch' => 'Cabang Sunter', 'visits' => 1, 'redeemed' => 0],
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

            $balance = CustomerBalance::firstOrCreate(
                ['customer_id' => $customer->id, 'loyalty_program_id' => $program->id],
                ['stamps_current' => 0, 'lifetime_stamps' => 0],
            );

            // Buat satu transaksi stempel per kunjungan, tersebar di masa lalu.
            $visits = $data['visits'];
            $gap = rand(5, 9); // jarak rata-rata antar kunjungan (hari)
            $lifetime = 0;
            $current = 0;
            for ($v = 0; $v < $visits; $v++) {
                $stamps = rand(1, 2);
                $daysAgo = ($visits - 1 - $v) * $gap + rand(0, 2);
                $txn = StampTransaction::create([
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'type' => StampTransaction::TYPE_EARN,
                    'stamps_delta' => $stamps,
                    'idempotency_key' => "seed-{$customer->id}-v{$v}",
                ]);
                $txn->forceFill(['created_at' => now()->subDays($daysAgo)])->saveQuietly();
                $lifetime += $stamps;
                $current += $stamps;
            }

            $balance->update([
                'stamps_current' => min($current, $program->card_size),
                'lifetime_stamps' => $lifetime,
            ]);

            // Penukaran hadiah (dengan reward_id supaya penghematan terhitung).
            for ($i = 0; $i < $data['redeemed']; $i++) {
                $rw = $rewardList[$i] ?? $rewardList->first();
                $txn = StampTransaction::create([
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'type' => StampTransaction::TYPE_REDEEM,
                    'stamps_delta' => 0,
                    'reward_id' => $rw?->id,
                    'idempotency_key' => "seed-{$customer->id}-r{$i}",
                ]);
                $txn->forceFill(['created_at' => now()->subDays(rand(1, 20))])->saveQuietly();
            }
        }

        // Akun demo pelanggan (sisi member): login pakai nomor + password.
        \App\Models\CustomerAccount::updateOrCreate(
            ['phone_canonical' => \App\Support\PhoneNumber::normalize('081111111111')],
            ['name' => 'Toni Kusuma', 'password' => Hash::make('password')],
        );
    }
}
