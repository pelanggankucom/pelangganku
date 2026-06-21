<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function dashboard(): View
    {
        $totalOwners    = User::where('role', User::ROLE_OWNER)->count();
        $totalKasir     = User::where('role', User::ROLE_CASHIER)->count();
        $totalMerchants = Merchant::count();
        $totalCustomers = Customer::count();

        $activeOwners  = User::where('role', User::ROLE_OWNER)->where('is_active', true)->count();
        $activeKasir   = User::where('role', User::ROLE_CASHIER)->where('is_active', true)->count();

        $recentOwners = User::where('role', User::ROLE_OWNER)
            ->with('merchants')
            ->latest()
            ->limit(5)
            ->get();

        $recentMerchants = Merchant::with('owner')
            ->latest()
            ->limit(5)
            ->get();

        return view('superadmin.dashboard', compact(
            'totalOwners', 'totalKasir', 'totalMerchants', 'totalCustomers',
            'activeOwners', 'activeKasir', 'recentOwners', 'recentMerchants'
        ));
    }

    public function owners(Request $request): View
    {
        $q = $request->input('q');

        $owners = User::where('role', User::ROLE_OWNER)
            ->with(['merchants'])
            ->when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.owners', compact('owners', 'q'));
    }

    public function kasir(Request $request): View
    {
        $q = $request->input('q');

        $kasirList = User::where('role', User::ROLE_CASHIER)
            ->with(['merchant', 'branch'])
            ->when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.kasir', compact('kasirList', 'q'));
    }

    public function merchants(Request $request): View
    {
        $q = $request->input('q');

        $merchants = Merchant::with(['owner', 'posSubscription', 'financeSubscription'])
            ->when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.merchants', compact('merchants', 'q'));
    }

    public function togglePos(Request $request, Merchant $merchant): RedirectResponse
    {
        if ($merchant->pos_granted_by_admin) {
            $merchant->update([
                'pos_granted_by_admin' => false,
                'pos_admin_expires_at' => null,
            ]);
            return back()->with('success', "Akses POS untuk toko {$merchant->name} berhasil dicabut.");
        }

        $expiresAt = $request->filled('expires_at')
            ? \Carbon\Carbon::parse($request->input('expires_at'))->endOfDay()
            : null;

        $merchant->update([
            'pos_granted_by_admin' => true,
            'pos_admin_expires_at' => $expiresAt,
        ]);

        $until = $expiresAt ? ' hingga ' . $expiresAt->format('d M Y') : ' (tanpa batas)';
        return back()->with('success', "POS untuk toko {$merchant->name} diaktifkan{$until}.");
    }

    public function updatePosExpiry(Request $request, Merchant $merchant): RedirectResponse
    {
        if (!$merchant->pos_granted_by_admin) {
            return back()->with('error', 'POS belum diaktifkan untuk toko ini.');
        }

        $expiresAt = $request->filled('expires_at')
            ? \Carbon\Carbon::parse($request->input('expires_at'))->endOfDay()
            : null;

        $merchant->update(['pos_admin_expires_at' => $expiresAt]);

        $until = $expiresAt ? $expiresAt->format('d M Y') : 'selamanya';
        return back()->with('success', "Tanggal POS {$merchant->name} diperbarui: aktif hingga {$until}.");
    }

    public function toggleFinance(Request $request, Merchant $merchant): RedirectResponse
    {
        if ($merchant->finance_granted_by_admin) {
            $merchant->update([
                'finance_granted_by_admin' => false,
                'finance_admin_expires_at' => null,
            ]);
            return back()->with('success', "Akses Laporan Keuangan untuk toko {$merchant->name} berhasil dicabut.");
        }

        $expiresAt = $request->filled('expires_at')
            ? \Carbon\Carbon::parse($request->input('expires_at'))->endOfDay()
            : null;

        $merchant->update([
            'finance_granted_by_admin' => true,
            'finance_admin_expires_at' => $expiresAt,
        ]);

        $until = $expiresAt ? ' hingga ' . $expiresAt->format('d M Y') : ' (tanpa batas)';
        return back()->with('success', "Laporan Keuangan untuk toko {$merchant->name} diaktifkan{$until}.");
    }

    public function updateFinanceExpiry(Request $request, Merchant $merchant): RedirectResponse
    {
        if (! $merchant->finance_granted_by_admin) {
            return back()->with('error', 'Laporan Keuangan belum diaktifkan untuk toko ini.');
        }

        $expiresAt = $request->filled('expires_at')
            ? \Carbon\Carbon::parse($request->input('expires_at'))->endOfDay()
            : null;

        $merchant->update(['finance_admin_expires_at' => $expiresAt]);

        $until = $expiresAt ? $expiresAt->format('d M Y') : 'selamanya';
        return back()->with('success', "Tanggal Laporan {$merchant->name} diperbarui: aktif hingga {$until}.");
    }

    public function toggleUser(User $user): RedirectResponse
    {
        abort_if($user->isSuperAdmin(), 403);

        $user->update(['is_active' => !$user->is_active]);

        $label = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun {$user->name} berhasil {$label}.");
    }

    public function deleteUser(User $user): RedirectResponse
    {
        abort_if($user->isSuperAdmin(), 403);

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Akun {$name} berhasil dihapus.");
    }
}
