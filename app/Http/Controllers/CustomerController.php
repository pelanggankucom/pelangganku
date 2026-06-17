<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\StampTransaction;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private LoyaltyService $loyalty)
    {
    }

    public function dashboard(): View
    {
        $account = Auth::guard('customer')->user();

        // Semua kartu (per merchant) untuk nomor ini.
        $cards = Customer::where('phone_canonical', $account->phone_canonical)
            ->with('merchant')
            ->get();

        $cardData = $cards->map(function (Customer $c) {
            $merchant = $c->merchant;
            $program = $merchant?->activeProgram();
            if (! $program) {
                return null;
            }

            $balance = $c->balanceFor($program);

            return [
                'merchant' => $merchant,
                'cardSize' => $program->card_size,
                'current' => $balance->stamps_current,
                'rewards' => $this->loyalty->rewardStatuses($c, $program),
            ];
        })->filter()->values();

        // Total penghematan = jumlah nilai hadiah yang sudah ditukar.
        $savings = (int) StampTransaction::whereIn('customer_id', $cards->pluck('id'))
            ->where('stamp_transactions.type', StampTransaction::TYPE_REDEEM)
            ->whereNotNull('reward_id')
            ->join('rewards', 'rewards.id', '=', 'stamp_transactions.reward_id')
            ->sum('rewards.value');

        return view('member.dashboard', [
            'account' => $account,
            'cards' => $cardData,
            'savings' => $savings,
        ]);
    }
}
