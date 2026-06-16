<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyProgram;
use App\Models\Reward;
use App\Models\StampTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoyaltyService
{
    /**
     * Beri stempel ke pelanggan. Idempoten via idempotency_key (cegah double submit).
     */
    public function giveStamp(
        Customer $customer,
        LoyaltyProgram $program,
        int $amount,
        User $cashier,
        ?string $idempotencyKey = null
    ): array {
        $amount = max(1, $amount);

        return DB::transaction(function () use ($customer, $program, $amount, $cashier, $idempotencyKey) {
            if ($idempotencyKey && StampTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
                // Sudah diproses sebelumnya — kembalikan saldo terkini tanpa menambah.
                $balance = $customer->balanceFor($program);

                return ['duplicate' => true, 'balance' => $balance];
            }

            $balance = $customer->balanceFor($program);
            $balance->stamps_current += $amount;
            $balance->lifetime_stamps += $amount;
            $balance->save();

            StampTransaction::create([
                'customer_id' => $customer->id,
                'branch_id' => $cashier->branch_id,
                'user_id' => $cashier->id,
                'type' => StampTransaction::TYPE_EARN,
                'stamps_delta' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);

            return ['duplicate' => false, 'balance' => $balance];
        });
    }

    /**
     * Tukar reward. Verifikasi kelayakan & potong stempel di dalam transaksi (cegah race).
     *
     * @throws RuntimeException jika stempel tidak cukup.
     */
    public function redeem(
        Customer $customer,
        LoyaltyProgram $program,
        Reward $reward,
        User $cashier,
        ?string $idempotencyKey = null
    ): array {
        return DB::transaction(function () use ($customer, $program, $reward, $cashier, $idempotencyKey) {
            if ($idempotencyKey && StampTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
                $balance = $customer->balanceFor($program);

                return ['duplicate' => true, 'balance' => $balance];
            }

            // Kunci baris saldo untuk cegah race condition.
            $balance = $customer->balances()
                ->where('loyalty_program_id', $program->id)
                ->lockForUpdate()
                ->first() ?? $customer->balanceFor($program);

            if ($balance->stamps_current < $reward->cost_stamps) {
                throw new RuntimeException('Stempel tidak cukup untuk menukar hadiah ini.');
            }

            $balance->stamps_current = $program->carry_over
                ? $balance->stamps_current - $reward->cost_stamps
                : 0;
            $balance->save();

            StampTransaction::create([
                'customer_id' => $customer->id,
                'branch_id' => $cashier->branch_id,
                'user_id' => $cashier->id,
                'type' => StampTransaction::TYPE_REDEEM,
                'stamps_delta' => -1 * $reward->cost_stamps,
                'reward_id' => $reward->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            return ['duplicate' => false, 'balance' => $balance];
        });
    }
}
