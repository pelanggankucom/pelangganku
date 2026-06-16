<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyProgram;
use App\Models\Reward;
use App\Models\RewardClaim;
use App\Models\StampTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoyaltyService
{
    /**
     * Beri stempel ke pelanggan (di-cap pada ukuran kartu). Idempoten via key.
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
                return ['duplicate' => true, 'balance' => $customer->balanceFor($program)];
            }

            $balance = $customer->balanceFor($program);
            $cardSize = max(1, $program->card_size);

            $applied = min($amount, max(0, $cardSize - $balance->stamps_current));
            $balance->stamps_current += $applied;
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

            return ['duplicate' => false, 'balance' => $balance, 'capped' => $applied < $amount];
        });
    }

    /**
     * Klaim hadiah pada milestone-nya. Kartu reset otomatis bila semua hadiah
     * telah diklaim & kartu sudah penuh.
     *
     * @throws RuntimeException jika belum mencapai milestone.
     */
    public function claimReward(
        Customer $customer,
        LoyaltyProgram $program,
        Reward $reward,
        User $cashier,
        ?string $idempotencyKey = null
    ): array {
        return DB::transaction(function () use ($customer, $program, $reward, $cashier, $idempotencyKey) {
            if ($idempotencyKey && StampTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
                return ['duplicate' => true, 'balance' => $customer->balanceFor($program)];
            }

            $balance = $customer->balances()
                ->where('loyalty_program_id', $program->id)
                ->lockForUpdate()
                ->first() ?? $customer->balanceFor($program);

            if ($balance->stamps_current < $reward->milestone) {
                throw new RuntimeException('Pelanggan belum mencapai stempel ke-' . $reward->milestone . '.');
            }

            if ($customer->hasClaimed($reward)) {
                throw new RuntimeException('Hadiah ini sudah ditukar untuk kartu berjalan.');
            }

            RewardClaim::create([
                'customer_id' => $customer->id,
                'reward_id' => $reward->id,
                'branch_id' => $cashier->branch_id,
                'user_id' => $cashier->id,
            ]);

            StampTransaction::create([
                'customer_id' => $customer->id,
                'branch_id' => $cashier->branch_id,
                'user_id' => $cashier->id,
                'type' => StampTransaction::TYPE_REDEEM,
                'stamps_delta' => 0,
                'reward_id' => $reward->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            // Reset kartu bila penuh & seluruh hadiah aktif sudah diklaim.
            $resetted = false;
            $totalRewards = $program->activeRewards()->count();
            $claimed = $customer->claims()
                ->whereIn('reward_id', $program->rewards()->pluck('id'))
                ->count();

            if ($balance->stamps_current >= $program->card_size && $totalRewards > 0 && $claimed >= $totalRewards) {
                $this->resetCardInline($customer, $program, $balance);
                $resetted = true;
            }

            return ['duplicate' => false, 'balance' => $balance->fresh(), 'card_reset' => $resetted];
        });
    }

    /** Mulai kartu baru: nol-kan stempel & hapus klaim kartu berjalan. */
    public function resetCard(Customer $customer, LoyaltyProgram $program): void
    {
        DB::transaction(function () use ($customer, $program) {
            $balance = $customer->balanceFor($program);
            $this->resetCardInline($customer, $program, $balance);
        });
    }

    private function resetCardInline(Customer $customer, LoyaltyProgram $program, $balance): void
    {
        $balance->stamps_current = 0;
        $balance->save();

        $customer->claims()
            ->whereIn('reward_id', $program->rewards()->pluck('id'))
            ->delete();
    }

    /**
     * Daftar hadiah + status untuk ditampilkan ke kasir.
     *
     * @return array<int, array{reward: Reward, claimable: bool, claimed: bool}>
     */
    public function rewardStatuses(Customer $customer, LoyaltyProgram $program): array
    {
        $current = $customer->balanceFor($program)->stamps_current;
        $claimedIds = $customer->claims()->pluck('reward_id')->all();

        return $program->activeRewards()->get()->map(function (Reward $reward) use ($current, $claimedIds) {
            $claimed = in_array($reward->id, $claimedIds, true);

            return [
                'reward' => $reward,
                'claimed' => $claimed,
                'claimable' => ! $claimed && $current >= $reward->milestone,
            ];
        })->all();
    }
}
