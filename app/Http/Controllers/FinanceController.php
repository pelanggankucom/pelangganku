<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use App\Models\PosOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

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

    public function export(Request $request): Response
    {
        $merchant = auth()->user()->currentMerchant();
        abort_unless($merchant->hasFinanceAccess(), 403);

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

        $handle = fopen('php://temp', 'r+');
        // BOM so Excel opens UTF-8 correctly
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, ['Laporan Keuangan', $merchant->name]);
        fputcsv($handle, ['Periode', $periodLabel]);
        fputcsv($handle, ['Diekspor pada', now()->format('d/m/Y H:i')]);
        fputcsv($handle, []);

        fputcsv($handle, ['RINGKASAN', '']);
        fputcsv($handle, ['Total Pemasukan', $totalIncome]);
        fputcsv($handle, ['Total Pengeluaran', $totalExpense]);
        fputcsv($handle, ['Laba Bersih', $netProfit]);
        fputcsv($handle, []);

        fputcsv($handle, ['PEMASUKAN', 'Jumlah (Rp)', 'Tanggal']);
        if ($posIncome > 0) {
            fputcsv($handle, ['Pendapatan POS (otomatis)', $posIncome, '']);
        }
        foreach ($incomeEntries as $e) {
            fputcsv($handle, [$e->description, $e->amount, $e->date->format('d/m/Y')]);
        }
        if ($posIncome === 0 && $incomeEntries->isEmpty()) {
            fputcsv($handle, ['(tidak ada data)', '', '']);
        }
        fputcsv($handle, []);

        fputcsv($handle, ['PENGELUARAN', 'Jumlah (Rp)', 'Tanggal']);
        foreach ($expenseEntries as $e) {
            fputcsv($handle, [$e->description, $e->amount, $e->date->format('d/m/Y')]);
        }
        if ($expenseEntries->isEmpty()) {
            fputcsv($handle, ['(tidak ada data)', '', '']);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $name = 'Laporan_' . preg_replace('/\s+/', '_', $merchant->name) . '_' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }
}
