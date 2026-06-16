<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\StampTransaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OwnerController extends Controller
{
    public function dashboard(): View
    {
        $merchant = auth()->user()->selectedMerchant();
        abort_if(!$merchant, 403, 'Toko tidak ditemukan');
        $mid = $merchant->id;
        $program = $merchant->activeProgram();
        $cardSize = $program?->card_size ?? 10;

        // Branch & date range
        $branches = $merchant->branches()->where('is_active', true)->orderBy('name')->get();
        $selectedBranchId = (int) request('branch', $branches->first()?->id);
        $fromDate = request('from') ? \Carbon\Carbon::parse(request('from')) : now()->startOfMonth();
        $toDate = request('to') ? \Carbon\Carbon::parse(request('to'))->endOfDay() : now();
        $startMonth = now()->startOfMonth();

        // --- Pelanggan (scoped ke branch + date range) ---
        $cust = fn () => Customer::where('merchant_id', $mid)->where('created_branch_id', $selectedBranchId);
        $totalCustomers = $cust()->count();
        $newToday = $cust()->whereDate('created_at', today())->count();
        $newThisMonth = $cust()->where('created_at', '>=', $startMonth)->count();

        // Pelanggan loyal = pernah menukar minimal 1 hadiah.
        $loyalCount = $cust()
            ->whereHas('transactions', fn ($q) => $q->where('type', StampTransaction::TYPE_REDEEM))
            ->count();

        // Pelanggan kembali = punya >= 2 transaksi stempel (datang lagi).
        $repeatCount = $cust()
            ->whereHas('transactions', fn ($q) => $q->where('type', StampTransaction::TYPE_EARN), '>=', 2)
            ->count();
        $repeatRate = $totalCustomers ? round($repeatCount * 100 / $totalCustomers) : 0;

        // Aktif dalam range.
        $active30 = $cust()
            ->whereHas('transactions', fn ($q) => $q->where('created_at', '>=', $toDate->copy()->subDays(30)))
            ->count();

        // Hampir dapat hadiah.
        $almostThreshold = max(1, $cardSize - 2);
        $almostDone = $cust()
            ->whereHas('balances', fn ($q) => $q->where('stamps_current', '>=', $almostThreshold)->where('stamps_current', '<', $cardSize))
            ->count();

        // --- Transaksi (scoped ke branch) ---
        $txn = fn () => StampTransaction::where('branch_id', $selectedBranchId);
        $stampsMonth = (int) $txn()->where('type', StampTransaction::TYPE_EARN)->whereBetween('created_at', [$fromDate, $toDate])->sum('stamps_delta');
        $redeemMonth = $txn()->where('type', StampTransaction::TYPE_REDEEM)->whereBetween('created_at', [$fromDate, $toDate])->count();
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
                'count' => $cust()->whereDate('created_at', $day)->count(),
            ];
            $trendStamps[] = [
                'label' => $label,
                'count' => (int) $txn()->where('type', StampTransaction::TYPE_EARN)->whereDate('created_at', $day)->sum('stamps_delta'),
            ];
        }

        // --- Top pelanggan loyal (dari branch) ---
        $topLoyal = $cust()
            ->withSum('balances as lifetime', 'lifetime_stamps')
            ->orderByDesc('lifetime')
            ->limit(5)
            ->get();

        return view('owner.dashboard', compact(
            'merchant', 'program', 'cardSize', 'branches', 'selectedBranchId', 'fromDate', 'toDate',
            'totalCustomers', 'newToday', 'newThisMonth',
            'loyalCount', 'repeatCount', 'repeatRate', 'active30', 'almostDone',
            'stampsMonth', 'redeemMonth', 'stampsToday', 'redeemToday',
            'trendNew', 'trendStamps', 'topLoyal',
        ));
    }

    public function programOutlet(): View
    {
        $merchant = auth()->user()->selectedMerchant();
        abort_if(!$merchant, 403);
        $program = $merchant->activeProgram();
        $branches = $merchant->branches()->orderBy('name')->get();

        return view('owner.program-outlet', compact('merchant', 'program', 'branches'));
    }

    public function settings(): View
    {
        $merchant = auth()->user()->selectedMerchant();
        abort_if(!$merchant, 403);
        $branches = $merchant->branches()->orderBy('name')->get();
        $cashiers = $merchant->users()->where('role', 'cashier')->orderBy('name')->get();

        return view('owner.settings', compact('merchant', 'branches', 'cashiers'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $merchant = auth()->user()->merchant;
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'instagram' => 'nullable|string|max:100',
            'whatsapp' => 'nullable|string|max:20',
            'facebook' => 'nullable|string|max:100',
            'tiktok' => 'nullable|string|max:100',
            'website' => 'nullable|url',
        ]);

        $merchant->update($data);

        return redirect()->route('owner.settings')->with('success', 'Profil toko berhasil diperbarui.');
    }

    public function storeCashier(Request $request): RedirectResponse
    {
        $merchant = auth()->user()->merchant;
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'branch_id' => 'required|exists:branches,id',
            'pin' => 'required|digits:4',
        ]);

        User::create([
            'merchant_id' => $merchant->id,
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make('password'),
            'pin_hash' => Hash::make($data['pin']),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        return redirect()->route('owner.settings')->with('success', 'Kasir berhasil ditambahkan.');
    }

    public function updateCashier(Request $request, User $user): RedirectResponse
    {
        $merchant = auth()->user()->merchant;
        abort_if($user->merchant_id !== $merchant->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'branch_id' => 'required|exists:branches,id',
            'is_active' => 'boolean',
        ]);

        $user->update($data);

        return redirect()->route('owner.settings')->with('success', 'Kasir berhasil diperbarui.');
    }

    public function destroyCashier(User $user): RedirectResponse
    {
        $merchant = auth()->user()->merchant;
        abort_if($user->merchant_id !== $merchant->id, 403);

        $user->delete();

        return redirect()->route('owner.settings')->with('success', 'Kasir berhasil dihapus.');
    }
}
