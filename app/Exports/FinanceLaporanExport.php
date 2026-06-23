<?php

namespace App\Exports;

use App\Models\FinanceEntry;
use App\Models\PosOrder;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinanceLaporanExport implements FromView, ShouldAutoSize, WithStyles
{
    public function __construct(
        private string $merchantName,
        private \Carbon\Carbon $from,
        private \Carbon\Carbon $to,
        private string $periodLabel,
        private float $posIncome,
        private float $totalIncome,
        private float $totalExpense,
        private float $netProfit,
        private $incomeEntries,
        private $expenseEntries,
    ) {}

    public function view(): View
    {
        return view('exports.laporan', [
            'merchantName'   => $this->merchantName,
            'from'           => $this->from,
            'to'             => $this->to,
            'periodLabel'    => $this->periodLabel,
            'posIncome'      => $this->posIncome,
            'totalIncome'    => $this->totalIncome,
            'totalExpense'   => $this->totalExpense,
            'netProfit'      => $this->netProfit,
            'incomeEntries'  => $this->incomeEntries,
            'expenseEntries' => $this->expenseEntries,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['size' => 14, 'bold' => true]],
            2 => ['font' => ['size' => 11]],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E3F2FD']]],
        ];
    }
}
