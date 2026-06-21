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
    /** Beranda — "Pencapaian kamu": metrik pelanggan lama vs baru + grafik. */
    public function dashboard(Request $request): View
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403, 'Toko tidak ditemukan');

        $mid = $merchant->id;

        // Periode: selama ini | hari ini | seminggu ini | kustom (default selama ini).
        $period = $request->get('periode', 'selama');
        $dari = $request->get('dari');
        $sampai = $request->get('sampai');
        $to = now();

        if ($period === 'hari') {
            $from = now()->startOfDay();
            $periodLabel = 'Hari ini';
        } elseif ($period === 'minggu') {
            $from = now()->startOfWeek();
            $periodLabel = 'Seminggu ini';
        } elseif ($period === 'kustom') {
            $from = $dari ? \Carbon\Carbon::parse($dari)->startOfDay() : now()->startOfMonth();
            $to = $sampai ? \Carbon\Carbon::parse($sampai)->endOfDay() : now();
            $periodLabel = $from->isoFormat('D MMM') . ' – ' . $to->isoFormat('D MMM Y');
        } else {
            $period = 'selama';
            $from = null; // tanpa batas bawah
            $periodLabel = 'Selama ini';
        }

        $customers = Customer::where('merchant_id', $mid)->get(['id', 'created_at']);
        $ids = $customers->pluck('id');

        $earns = StampTransaction::whereIn('customer_id', $ids)
            ->where('type', StampTransaction::TYPE_EARN)
            ->get(['customer_id', 'created_at', 'stamps_delta']);

        // Jumlah kunjungan (visit) & kunjungan pertama per pelanggan.
        $visitCount = [];
        $firstSeen = [];
        foreach ($earns as $e) {
            $cid = $e->customer_id;
            $visitCount[$cid] = ($visitCount[$cid] ?? 0) + 1;
            if (! isset($firstSeen[$cid]) || $e->created_at->lt($firstSeen[$cid])) {
                $firstSeen[$cid] = $e->created_at;
            }
        }
        foreach ($customers as $c) {
            // Terdaftar = minimal 1x datang (saat didaftarkan kasir).
            $visitCount[$c->id] = $visitCount[$c->id] ?? 1;
            $firstSeen[$c->id] = $firstSeen[$c->id] ?? $c->created_at;
        }

        // --- Kartu pelanggan (kumulatif) ---
        $totalCustomers = $customers->count();
        $pelangganLama = collect($visitCount)->filter(fn ($n) => $n >= 2)->count();
        $pelangganBaru = $totalCustomers - $pelangganLama;

        // --- Stempel pada periode, dipecah lama vs baru ---
        $earnsPeriod = $earns->filter(fn ($e) => $from === null || $e->created_at->between($from, $to));
        $isLama = fn ($cid) => ($visitCount[$cid] ?? 0) >= 2;
        $totalStempel = (int) $earnsPeriod->sum('stamps_delta');
        $stempelLama = (int) $earnsPeriod->filter(fn ($e) => $isLama($e->customer_id))->sum('stamps_delta');
        $stempelBaru = $totalStempel - $stempelLama;

        // --- Hadiah ditukar pada periode ---
        $hadiahDitukar = StampTransaction::whereIn('customer_id', $ids)
            ->where('type', StampTransaction::TYPE_REDEEM)
            ->when($from, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->count();

        // --- Rata-rata waktu pelanggan datang lagi (hari) ---
        $gaps = [];
        foreach ($earns->sortBy('created_at')->groupBy('customer_id') as $list) {
            $dates = $list->pluck('created_at')->values();
            for ($i = 1; $i < $dates->count(); $i++) {
                $gaps[] = (int) $dates[$i - 1]->diffInDays($dates[$i]);
            }
        }
        $avgReorder = count($gaps) ? (int) round(array_sum($gaps) / count($gaps)) : null;

        // --- Grafik: pelanggan lama vs baru per bucket (maks 12) ---
        $chartFrom = ($from ?? $customers->pluck('created_at')->min() ?? now()->subMonth())->copy()->startOfDay();
        $chartTo = $to->copy();
        $spanDays = max(1, (int) $chartFrom->diffInDays($chartTo));
        $width = max(1, (int) ceil($spanDays / 7));
        $chart = [];
        $cursor = $chartFrom->copy();
        while ($cursor->lt($chartTo) && count($chart) < 12) {
            $bStart = $cursor->copy();
            $bEnd = $cursor->copy()->addDays($width);
            $baru = 0;
            foreach ($firstSeen as $fs) {
                if ($fs->gte($bStart) && $fs->lt($bEnd)) {
                    $baru++;
                }
            }
            $lamaSet = [];
            foreach ($earns as $e) {
                if ($e->created_at->gte($bStart) && $e->created_at->lt($bEnd) && $firstSeen[$e->customer_id]->lt($bStart)) {
                    $lamaSet[$e->customer_id] = true;
                }
            }
            $chart[] = [
                'label' => $bStart->isoFormat($width > 1 ? 'D/M' : 'D/M'),
                'baru' => $baru,
                'lama' => count($lamaSet),
            ];
            $cursor->addDays($width);
        }

        $storeCount = auth()->user()->merchants()->count();

        return view('owner.dashboard', compact(
            'merchant', 'storeCount', 'period', 'periodLabel', 'dari', 'sampai',
            'totalCustomers', 'pelangganLama', 'pelangganBaru',
            'totalStempel', 'stempelLama', 'stempelBaru',
            'hadiahDitukar', 'avgReorder', 'chart',
        ));
    }

    /** Atur — menu sederhana (bukan tab bertingkat). */
    public function settings(): View
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        return view('owner.settings', [
            'merchant'     => $merchant,
            'storeCount'   => auth()->user()->merchants()->count(),
            'branchCount'  => $merchant->branches()->count(),
            'cashierCount' => $merchant->users()->where('role', 'cashier')->count(),
            'rewardCount'  => $merchant->activeProgram()?->rewards()->count() ?? 0,
            'posActive'    => $merchant->hasPosAccess(),
        ]);
    }

    /** Daftar pelanggan — stempel, jumlah tukar, & kapan terakhir hadir. */
    public function customers(Request $request): View
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        // --- Range tanggal pengamatan (samakan dengan Beranda) ---
        $range = $request->get('range', 'bulan'); // hari | minggu | bulan | kustom
        $dari = $request->get('dari');
        $sampai = $request->get('sampai');
        $to = now();

        if ($range === 'hari') {
            $from = now()->startOfDay();
            $rangeLabel = 'hari ini';
        } elseif ($range === 'minggu') {
            $from = now()->startOfWeek();
            $rangeLabel = 'minggu ini';
        } elseif ($range === 'kustom') {
            $from = $dari ? \Carbon\Carbon::parse($dari)->startOfDay() : now()->startOfMonth();
            $to = $sampai ? \Carbon\Carbon::parse($sampai)->endOfDay() : now();
            $rangeLabel = $from->isoFormat('D MMM') . ' – ' . $to->isoFormat('D MMM Y');
        } else {
            $range = 'bulan';
            $from = now()->startOfMonth();
            $rangeLabel = 'bulan ini';
        }

        // --- Ambil pelanggan + stempel, jumlah tukar, kunjungan terakhir ---
        $customers = Customer::where('merchant_id', $merchant->id)
            ->withSum('balances as stamps_total', 'stamps_current')
            ->get();

        $ids = $customers->pluck('id');

        $redeems = StampTransaction::whereIn('customer_id', $ids)
            ->where('type', StampTransaction::TYPE_REDEEM)
            ->selectRaw('customer_id, COUNT(*) as c')
            ->groupBy('customer_id')->pluck('c', 'customer_id');

        $lastVisits = StampTransaction::whereIn('customer_id', $ids)
            ->where('type', StampTransaction::TYPE_EARN)
            ->selectRaw('customer_id, MAX(created_at) as t')
            ->groupBy('customer_id')->pluck('t', 'customer_id');

        $customers->each(function ($c) use ($redeems, $lastVisits, $from, $to) {
            $c->redeem_count = (int) ($redeems[$c->id] ?? 0);
            // Kalau belum ada transaksi stempel, anggap kunjungan = tanggal daftar
            // (pelanggan pasti pernah datang saat didaftarkan kasir).
            $c->last_visit = $lastVisits[$c->id] ?? $c->created_at->toDateTimeString();
            $c->in_range = \Carbon\Carbon::parse($c->last_visit)->between($from, $to);
        });

        // --- Filter berdasarkan kehadiran (relatif ke range) ---
        // Default "aktif": hanya pelanggan yang benar-benar hadir di dalam rentang.
        $hadir = $request->get('hadir', 'aktif'); // aktif | belum | semua
        $customers = $customers->filter(fn ($c) => match ($hadir) {
            'aktif' => $c->in_range,
            'belum' => ! $c->in_range, // termasuk yang sudah lama hilang & belum pernah
            default => true,           // semua
        });

        // Hadir: terbaru di atas. Belum hadir: yang paling lama/belum hadir di atas.
        $customers = $hadir === 'aktif'
            ? $customers->sortByDesc(fn ($c) => $c->last_visit ?? '0000-00-00')->values()
            : $customers->sortBy(fn ($c) => $c->last_visit ?? '0000-00-00')->values();

        $n = $customers->count();
        $countText = match ($hadir) {
            'aktif' => "$n pelanggan hadir — $rangeLabel",
            'belum' => "$n pelanggan belum hadir — $rangeLabel",
            default => "$n pelanggan — semua · $rangeLabel",
        };

        return view('owner.customers', compact(
            'merchant', 'customers', 'range', 'dari', 'sampai', 'hadir', 'rangeLabel', 'countText',
        ));
    }

    /** Profil akun owner (nama, telepon, ganti password). */
    public function profile(): View
    {
        return view('owner.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:4|confirmed',
        ]);

        $user->name = $data['name'];
        $user->phone = $data['phone'] ?? null;
        if (! empty($data['password'])) {
            $user->password = $data['password']; // di-hash otomatis (cast 'hashed')
        }
        $user->save();

        return redirect()->route('owner.profile')->with('success', 'Profil kamu berhasil diperbarui.');
    }

    public function storeCashier(Request $request): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string',
            'branch_id' => 'required|exists:branches,id',
            'pin' => 'required|digits:4',
        ]);

        $canonical = \App\Support\PhoneNumber::normalize($data['phone']);
        if ($canonical === null) {
            return back()->withErrors(['phone' => 'Nomor HP tidak valid.']);
        }
        if (User::where('phone', $canonical)->exists()) {
            return back()->withErrors(['phone' => 'Nomor HP sudah dipakai akun lain.']);
        }

        User::create([
            'merchant_id' => $merchant->id,
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'phone' => $canonical,
            'email' => $canonical . '@kasir.pelangganku.local',
            'password' => Hash::make($data['pin']), // kasir login: No HP + PIN
            'pin_hash' => Hash::make($data['pin']),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        return redirect()->route('owner.branches')->with('success', 'Kasir berhasil ditambahkan.');
    }

    public function destroyCashier(User $user): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant || $user->merchant_id !== $merchant->id, 403);

        $user->delete();

        return redirect()->route('owner.branches')->with('success', 'Kasir dihapus.');
    }
}
