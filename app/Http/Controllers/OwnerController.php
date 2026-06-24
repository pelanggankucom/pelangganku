<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FinanceEntry;
use App\Models\PosOrder;
use App\Models\StampTransaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $to = now()->endOfDay();

        if ($period === 'hari') {
            $from = now()->startOfDay();
            $periodLabel = 'Hari ini';
        } elseif ($period === 'minggu') {
            $from = now()->startOfWeek();
            $periodLabel = 'Seminggu ini';
        } elseif ($period === 'kustom') {
            $from = $dari ? \Carbon\Carbon::parse($dari)->startOfDay() : now()->startOfMonth();
            $to = $sampai ? \Carbon\Carbon::parse($sampai)->endOfDay() : now()->endOfDay();
            $periodLabel = $from->isoFormat('D MMM') . ' – ' . $to->isoFormat('D MMM Y');
        } else {
            $period = 'selama';
            $from = null;
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

        // --- Data Laporan Keuangan ---
        $financeData = null;
        if ($merchant->hasFinanceAccess()) {
            $posIncomeRows = PosOrder::where('merchant_id', $mid)->where('status', 'paid')
                ->when($from, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
                ->get(['total', 'created_at']);

            $incomeRows = FinanceEntry::where('merchant_id', $mid)->where('type', 'income')
                ->when($from, fn ($q) => $q->whereBetween('date', [$from, $to]))
                ->get(['amount', 'date']);

            $expenseRows = FinanceEntry::where('merchant_id', $mid)->where('type', 'expense')
                ->when($from, fn ($q) => $q->whereBetween('date', [$from, $to]))
                ->get(['amount', 'date']);

            $finTotalIncome  = $posIncomeRows->sum('total') + $incomeRows->sum('amount');
            $finTotalExpense = $expenseRows->sum('amount');
            $finNetProfit    = $finTotalIncome - $finTotalExpense;

            // Chart buckets (sama logika dengan chart pelanggan)
            $finChartFrom = $from
                ? $from->copy()->startOfDay()
                : (collect([
                    $posIncomeRows->pluck('created_at')->min(),
                    $incomeRows->pluck('date')->min() ? \Carbon\Carbon::parse($incomeRows->pluck('date')->min()) : null,
                    $expenseRows->pluck('date')->min() ? \Carbon\Carbon::parse($expenseRows->pluck('date')->min()) : null,
                ])->filter()->sort()->first() ?? now()->subMonth())->copy()->startOfDay();

            $finChartTo  = $to->copy();
            $finSpan     = max(1, (int) $finChartFrom->diffInDays($finChartTo));
            $finWidth    = max(1, (int) ceil($finSpan / 7));
            $finChart    = [];
            $cur         = $finChartFrom->copy();

            while ($cur->lt($finChartTo) && count($finChart) < 12) {
                $bS  = $cur->copy();
                $bE  = $cur->copy()->addDays($finWidth);
                $bPOS    = $posIncomeRows->filter(fn ($o) => $o->created_at->gte($bS) && $o->created_at->lt($bE))->sum('total');
                $bInc    = $incomeRows->filter(fn ($e) => $e->date->gte($bS) && $e->date->lt($bE))->sum('amount');
                $bExp    = $expenseRows->filter(fn ($e) => $e->date->gte($bS) && $e->date->lt($bE))->sum('amount');
                $bTotInc = $bPOS + $bInc;
                $finChart[] = [
                    'label'   => $bS->isoFormat('D/M'),
                    'income'  => $bTotInc,
                    'expense' => $bExp,
                    'net'     => $bTotInc - $bExp,
                ];
                $cur->addDays($finWidth);
            }

            $financeData = compact('finTotalIncome', 'finTotalExpense', 'finNetProfit', 'finChart');
        }

        // --- Data POS (hanya jika merchant punya akses POS) ---
        $posData = null;
        if ($merchant->hasPosAccess()) {
            $posQ = PosOrder::where('merchant_id', $mid)->where('status', 'paid');
            if ($from) {
                $posQ->whereBetween('created_at', [$from, $to]);
            }

            $posOrders = $posQ->latest()->get(['id', 'order_number', 'total', 'discount', 'payment_method', 'created_at']);

            $posRevenue       = $posOrders->sum('total');
            $posTransactions  = $posOrders->count();
            $posDiscount      = $posOrders->sum('discount');

            $byMethod = $posOrders->groupBy('payment_method')->map(fn ($g) => [
                'count'  => $g->count(),
                'total'  => $g->sum('total'),
            ]);

            $posData = compact('posRevenue', 'posTransactions', 'posDiscount', 'byMethod', 'posOrders');
        }

        return view('owner.dashboard', compact(
            'merchant', 'storeCount', 'period', 'periodLabel', 'dari', 'sampai',
            'totalCustomers', 'pelangganLama', 'pelangganBaru',
            'totalStempel', 'stempelLama', 'stempelBaru',
            'hadiahDitukar', 'avgReorder', 'chart', 'posData', 'financeData',
        ));
    }

    /** Riwayat transaksi POS lengkap. */
    public function posHistory(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        if (! $merchant->hasPosAccess()) {
            return redirect()->route('owner.pos');
        }

        $orders = PosOrder::where('merchant_id', $merchant->id)
            ->where('status', 'paid')
            ->with(['items', 'kasir', 'customer'])
            ->latest()
            ->paginate(30);

        return view('owner.pos-history', compact('merchant', 'orders'));
    }

    public function exportPosHistory(Request $request): Response
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant || ! $merchant->hasPosAccess(), 403);

        $orders = PosOrder::where('merchant_id', $merchant->id)
            ->where('status', 'paid')
            ->latest()
            ->get();

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, ['Riwayat Transaksi POS', $merchant->name]);
        fputcsv($handle, ['Diekspor pada', now()->format('d/m/Y H:i')]);
        fputcsv($handle, []);

        fputcsv($handle, ['No. Order', 'Tanggal', 'Metode Bayar', 'Subtotal (Rp)', 'Diskon (Rp)', 'Total (Rp)']);
        foreach ($orders as $order) {
            fputcsv($handle, [
                $order->order_number,
                $order->created_at->format('d/m/Y H:i'),
                $order->payment_method,
                $order->subtotal,
                $order->discount ?? 0,
                $order->total,
            ]);
        }
        if ($orders->isEmpty()) {
            fputcsv($handle, ['(tidak ada data)', '', '', '', '', '']);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $name = 'POS_' . preg_replace('/\s+/', '_', $merchant->name) . '_' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    /** Atur — menu sederhana (bukan tab bertingkat). */
    public function settings(): View
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        return view('owner.settings', [
            'merchant'      => $merchant,
            'storeCount'    => auth()->user()->merchants()->count(),
            'branchCount'   => $merchant->branches()->count(),
            'cashierCount'  => $merchant->users()->where('role', 'cashier')->count(),
            'rewardCount'   => $merchant->activeProgram()?->rewards()->count() ?? 0,
            'posActive'     => $merchant->hasPosAccess(),
            'financeActive' => $merchant->hasFinanceAccess(),
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
        $to = now()->endOfDay();

        if ($range === 'hari') {
            $from = now()->startOfDay();
            $rangeLabel = 'hari ini';
        } elseif ($range === 'minggu') {
            $from = now()->startOfWeek();
            $rangeLabel = 'minggu ini';
        } elseif ($range === 'kustom') {
            $from = $dari ? \Carbon\Carbon::parse($dari)->startOfDay() : now()->startOfMonth();
            $to = $sampai ? \Carbon\Carbon::parse($sampai)->endOfDay() : now()->endOfDay();
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
