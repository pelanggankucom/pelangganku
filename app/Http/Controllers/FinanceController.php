<?php

namespace App\Http\Controllers;

use App\Exports\FinanceLaporanExport;
use App\Models\FinanceEntry;
use App\Models\PosOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class FinanceController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();

        if (! $merchant->hasFinanceAccess()) {
            return redirect()->route('owner.laporan.sub');
        }

        $period = $request->get('periode', 'bulan');
        $dari   = $request->get('dari');
        $sampai = $request->get('sampai');
        $to     = now()->endOfDay();

        if ($period === 'hari') {
            $from        = now()->startOfDay();
            $periodLabel = 'Hari ini';
        } elseif ($period === 'minggu') {
            $from        = now()->startOfWeek();
            $periodLabel = 'Seminggu ini';
        } elseif ($period === 'kustom') {
            $from        = $dari ? \Carbon\Carbon::parse($dari)->startOfDay() : now()->startOfMonth();
            $to          = $sampai ? \Carbon\Carbon::parse($sampai)->endOfDay() : now()->endOfDay();
            $periodLabel = $from->isoFormat('D MMM') . ' – ' . $to->isoFormat('D MMM Y');
        } else {
            $period      = 'bulan';
            $from        = now()->startOfMonth();
            $periodLabel = 'Bulan ini (' . now()->isoFormat('MMMM Y') . ')';
        }

        $mid = $merchant->id;

        // Pendapatan POS
        $posIncome = PosOrder::where('merchant_id', $mid)
            ->where('status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->sum('total');

        // Pendapatan manual
        $incomeEntries = FinanceEntry::where('merchant_id', $mid)
            ->where('type', 'income')
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')->orderByDesc('id')
            ->get();

        // Pengeluaran
        $expenseEntries = FinanceEntry::where('merchant_id', $mid)
            ->where('type', 'expense')
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')->orderByDesc('id')
            ->get();

        $totalIncome  = $posIncome + $incomeEntries->sum('amount');
        $totalExpense = $expenseEntries->sum('amount');
        $netProfit    = $totalIncome - $totalExpense;

        return view('owner.laporan', compact(
            'merchant', 'period', 'periodLabel', 'dari', 'sampai',
            'posIncome', 'incomeEntries', 'expenseEntries',
            'totalIncome', 'totalExpense', 'netProfit',
        ));
    }

    public function storeEntry(Request $request): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_unless($merchant->hasFinanceAccess(), 403);

        $data = $request->validate([
            'type'        => ['required', 'in:income,expense'],
            'description' => ['required', 'string', 'max:200'],
            'amount'      => ['required', 'integer', 'min:1', 'max:999999999'],
            'date'        => ['required', 'date'],
        ]);

        FinanceEntry::create([...$data, 'merchant_id' => $merchant->id]);

        $label  = $data['type'] === 'income' ? 'pemasukan' : 'pengeluaran';
        $params = ['periode' => $request->get('_periode', 'bulan')];
        if ($params['periode'] === 'kustom') {
            $params['dari']   = $request->get('_dari');
            $params['sampai'] = $request->get('_sampai');
        }
        return redirect()->route('owner.laporan', $params)
            ->with('success', "Item {$label} berhasil ditambahkan.");
    }

    public function destroyEntry(Request $request, FinanceEntry $entry): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_unless($entry->merchant_id === $merchant->id, 403);

        $entry->delete();
        $params = ['periode' => $request->get('_periode', 'bulan')];
        if ($params['periode'] === 'kustom') {
            $params['dari']   = $request->get('_dari');
            $params['sampai'] = $request->get('_sampai');
        }
        return redirect()->route('owner.laporan', $params)
            ->with('success', 'Item berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $merchant = auth()->user()->currentMerchant();
        abort_unless($merchant->hasFinanceAccess(), 403);

        try {
            $period = $request->get('periode', 'bulan');
            $dari   = $request->get('dari');
            $sampai = $request->get('sampai');
            $to     = now()->endOfDay();

            if ($period === 'hari') {
                $from        = now()->startOfDay();
                $periodLabel = 'Hari ini';
            } elseif ($period === 'minggu') {
                $from        = now()->startOfWeek();
                $periodLabel = 'Seminggu ini';
            } elseif ($period === 'kustom') {
                $from        = $dari ? \Carbon\Carbon::parse($dari)->startOfDay() : now()->startOfMonth();
                $to          = $sampai ? \Carbon\Carbon::parse($sampai)->endOfDay() : now()->endOfDay();
                $periodLabel = $from->isoFormat('D MMM') . ' – ' . $to->isoFormat('D MMM Y');
            } else {
                $period      = 'bulan';
                $from        = now()->startOfMonth();
                $periodLabel = 'Bulan ini (' . now()->isoFormat('MMMM Y') . ')';
            }

            $mid = $merchant->id;

            $posIncome = PosOrder::where('merchant_id', $mid)
                ->where('status', 'paid')
                ->whereBetween('created_at', [$from, $to])
                ->sum('total');

            $incomeEntries = FinanceEntry::where('merchant_id', $mid)
                ->where('type', 'income')
                ->whereBetween('date', [$from, $to])
                ->orderByDesc('date')->orderByDesc('id')
                ->get();

            $expenseEntries = FinanceEntry::where('merchant_id', $mid)
                ->where('type', 'expense')
                ->whereBetween('date', [$from, $to])
                ->orderByDesc('date')->orderByDesc('id')
                ->get();

            $totalIncome  = $posIncome + $incomeEntries->sum('amount');
            $totalExpense = $expenseEntries->sum('amount');
            $netProfit    = $totalIncome - $totalExpense;

            $fileName = 'Laporan_Keuangan_' . $merchant->name . '_' . now()->format('Y-m-d') . '.xlsx';

            return Excel::download(
                new FinanceLaporanExport(
                    $merchant->name,
                    $from,
                    $to,
                    $periodLabel,
                    $posIncome,
                    $totalIncome,
                    $totalExpense,
                    $netProfit,
                    $incomeEntries,
                    $expenseEntries,
                ),
                $fileName
            );
        } catch (\Exception $e) {
            return redirect()->route('owner.laporan')->with('error', 'Export gagal: ' . $e->getMessage());
        }
    }
}
