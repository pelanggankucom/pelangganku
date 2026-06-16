<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\StampTransaction;
use Illuminate\View\View;

class OwnerController extends Controller
{
    public function dashboard(): View
    {
        $merchant = auth()->user()->merchant;
        $mid = $merchant->id;
        $program = $merchant->activeProgram();
        $cardSize = $program?->card_size ?? 10;

        $startMonth = now()->startOfMonth();

        // --- Pelanggan ---
        $totalCustomers = Customer::where('merchant_id', $mid)->count();
        $newToday = Customer::where('merchant_id', $mid)->whereDate('created_at', today())->count();
        $newThisMonth = Customer::where('merchant_id', $mid)->where('created_at', '>=', $startMonth)->count();

        // Pelanggan loyal = pernah menukar minimal 1 hadiah.
        $loyalCount = Customer::where('merchant_id', $mid)
            ->whereHas('transactions', fn ($q) => $q->where('type', StampTransaction::TYPE_REDEEM))
            ->count();

        // Pelanggan kembali = punya >= 2 transaksi stempel (datang lagi).
        $repeatCount = Customer::where('merchant_id', $mid)
            ->whereHas('transactions', fn ($q) => $q->where('type', StampTransaction::TYPE_EARN), '>=', 2)
            ->count();
        $repeatRate = $totalCustomers ? round($repeatCount * 100 / $totalCustomers) : 0;

        // Aktif 30 hari terakhir.
        $active30 = Customer::where('merchant_id', $mid)
            ->whereHas('transactions', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->count();

        // Hampir dapat hadiah (tinggal <= 2 stempel menuju kartu penuh).
        $almostThreshold = max(1, $cardSize - 2);
        $almostDone = Customer::where('merchant_id', $mid)
            ->whereHas('balances', fn ($q) => $q->where('stamps_current', '>=', $almostThreshold)->where('stamps_current', '<', $cardSize))
            ->count();

        // --- Transaksi (scoped ke pelanggan merchant) ---
        $txn = fn () => StampTransaction::whereHas('customer', fn ($q) => $q->where('merchant_id', $mid));
        $stampsMonth = (int) $txn()->where('type', StampTransaction::TYPE_EARN)->where('created_at', '>=', $startMonth)->sum('stamps_delta');
        $redeemMonth = $txn()->where('type', StampTransaction::TYPE_REDEEM)->where('created_at', '>=', $startMonth)->count();
        $stampsToday = (int) $txn()->where('type', StampTransaction::TYPE_EARN)->whereDate('created_at', today())->sum('stamps_delta');
        $redeemToday = $txn()->where('type', StampTransaction::TYPE_REDEEM)->whereDate('created_at', today())->count();

        // --- Tren 7 hari ---
        $trendNew = [];
        $trendStamps = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $label = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][$day->dayOfWeek];
            $trendNew[] = [
                'label' => $label,
                'count' => Customer::where('merchant_id', $mid)->whereDate('created_at', $day)->count(),
            ];
            $trendStamps[] = [
                'label' => $label,
                'count' => (int) StampTransaction::whereHas('customer', fn ($q) => $q->where('merchant_id', $mid))
                    ->where('type', StampTransaction::TYPE_EARN)->whereDate('created_at', $day)->sum('stamps_delta'),
            ];
        }

        // --- Top pelanggan loyal ---
        $topLoyal = Customer::where('merchant_id', $mid)
            ->withSum('balances as lifetime', 'lifetime_stamps')
            ->orderByDesc('lifetime')
            ->limit(5)
            ->get();

        // --- Performa per outlet ---
        $branches = $merchant->branches()->orderBy('name')->get()->map(fn ($b) => [
            'name' => $b->name,
            'is_active' => $b->is_active,
            'customers' => Customer::where('created_branch_id', $b->id)->count(),
            'stamps' => (int) StampTransaction::where('branch_id', $b->id)->where('type', StampTransaction::TYPE_EARN)->sum('stamps_delta'),
            'redeem' => StampTransaction::where('branch_id', $b->id)->where('type', StampTransaction::TYPE_REDEEM)->count(),
        ]);

        return view('owner.dashboard', compact(
            'merchant', 'program', 'cardSize',
            'totalCustomers', 'newToday', 'newThisMonth',
            'loyalCount', 'repeatCount', 'repeatRate', 'active30', 'almostDone',
            'stampsMonth', 'redeemMonth', 'stampsToday', 'redeemToday',
            'trendNew', 'trendStamps', 'topLoyal', 'branches',
        ));
    }
}
