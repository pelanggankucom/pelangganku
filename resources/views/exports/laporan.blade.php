<table>
    <tr>
        <td colspan="4" style="font-size:14px; font-weight:bold;">Laporan Keuangan</td>
    </tr>
    <tr>
        <td colspan="4">{{ $merchantName }}</td>
    </tr>
    <tr>
        <td colspan="4">{{ $periodLabel }}</td>
    </tr>
    <tr></tr>
    <tr>
        <td style="font-weight:bold;">RINGKASAN</td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>Total Pemasukan</td>
        <td style="text-align:right;">Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>Total Pengeluaran</td>
        <td style="text-align:right;">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>Pendapatan Bersih</td>
        <td style="text-align:right; font-weight:bold;">Rp {{ number_format($netProfit, 0, ',', '.') }}</td>
        <td></td>
        <td></td>
    </tr>
    <tr></tr>
    <tr>
        <td style="font-weight:bold;">PEMASUKAN</td>
        <td style="text-align:right; font-weight:bold;">Jumlah (Rp)</td>
        <td style="font-weight:bold;">Tanggal</td>
        <td></td>
    </tr>
    @if($posIncome > 0)
    <tr>
        <td>Pendapatan POS</td>
        <td style="text-align:right;">{{ number_format($posIncome, 0, ',', '.') }}</td>
        <td></td>
        <td></td>
    </tr>
    @endif
    @forelse($incomeEntries as $entry)
    <tr>
        <td>{{ $entry->description }}</td>
        <td style="text-align:right;">{{ number_format($entry->amount, 0, ',', '.') }}</td>
        <td>{{ $entry->date->format('d M Y') }}</td>
        <td></td>
    </tr>
    @empty
    <tr>
        <td colspan="4" style="color:#999;">Tidak ada pemasukan manual</td>
    </tr>
    @endforelse
    <tr></tr>
    <tr>
        <td style="font-weight:bold;">PENGELUARAN</td>
        <td style="text-align:right; font-weight:bold;">Jumlah (Rp)</td>
        <td style="font-weight:bold;">Tanggal</td>
        <td></td>
    </tr>
    @forelse($expenseEntries as $entry)
    <tr>
        <td>{{ $entry->description }}</td>
        <td style="text-align:right;">{{ number_format($entry->amount, 0, ',', '.') }}</td>
        <td>{{ $entry->date->format('d M Y') }}</td>
        <td></td>
    </tr>
    @empty
    <tr>
        <td colspan="4" style="color:#999;">Tidak ada pengeluaran</td>
    </tr>
    @endforelse
</table>
