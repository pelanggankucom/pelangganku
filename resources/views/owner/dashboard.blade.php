@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
<style>
    .switcher { display:inline-flex; align-items:center; gap:7px; background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.28); color:#fff; padding:7px 13px; border-radius:999px; font-size:13px; font-weight:700; text-decoration:none; position:relative; z-index:1; }
    .sec-title { font-size:20px; font-weight:800; letter-spacing:-.4px; margin:4px 2px 14px; }
    .periode { display:flex; gap:7px; margin-bottom:10px; }
    .periode label { flex:1; }
    .periode input { position:absolute; opacity:0; pointer-events:none; }
    .periode span { display:block; text-align:center; padding:11px 4px; border-radius:13px; font-size:12.5px; font-weight:700; background:#fff; border:1.5px solid var(--line); color:var(--muted); cursor:pointer; }
    .periode input:checked + span { background:var(--grad-blue); color:#fff; border-color:transparent; box-shadow:0 4px 12px rgba(13,71,161,.22); }
    .pdates { margin-bottom:12px; }
    .pdates .two { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .pdates label { margin-top:0; margin-bottom:5px; display:block; }
    .periode-info { font-size:13px; color:var(--muted); font-weight:600; margin:0 2px 14px; }
    .periode-info b { color:var(--blue); }
    .g3 { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:14px; }
    .g2 { display:grid; grid-template-columns:1.55fr 1fr; gap:10px; margin-bottom:14px; }
    .acard { background:#fff; border:1.5px solid var(--line); border-radius:18px; padding:16px 10px; text-align:center; box-shadow:var(--shadow); display:flex; flex-direction:column; align-items:center; justify-content:center; }
    .acard .n { font-size:30px; font-weight:800; letter-spacing:-1px; line-height:1; color:var(--text); }
    .acard .n.gold { color:var(--gold-d); }
    .acard .l { font-size:11.5px; color:var(--muted); font-weight:600; margin-top:8px; line-height:1.3; }
    .acard.wide { text-align:left; align-items:flex-start; padding:18px; }
    .acard.wide .n { font-size:34px; }
    .chartcard { background:#fff; border:1.5px solid var(--line); border-radius:18px; padding:16px; box-shadow:var(--shadow); margin-bottom:14px; }
    .chartcard .ttl { font-size:14px; font-weight:800; margin-bottom:4px; }
    .legend { display:flex; gap:16px; font-size:12px; font-weight:700; margin:8px 0 12px; }
    .legend i { display:inline-block; width:11px; height:11px; border-radius:3px; margin-right:6px; vertical-align:middle; }
    .legend .blue { background:var(--blue); } .legend .gold { background:var(--gold-d); }
    .linechart { width:100%; height:auto; display:block; overflow:visible; }
    .linechart text { font-size:9px; fill:var(--muted); font-weight:600; }
    .linechart text.vlama { fill:var(--gold-d); font-weight:800; font-size:10px; }
    .linechart text.vbaru { fill:var(--blue); font-weight:800; font-size:10px; }
    .linechart .grid { stroke:var(--line); stroke-width:1; }
</style>

<div class="hero">
    <div class="label" style="position:relative;z-index:1">Halo {{ auth()->user()->name }} <b style="font-weight:700;opacity:.9">owner</b> 👋</div>
    <div class="big">{{ $merchant->name }}</div>
    @if($storeCount > 1)
        <a href="{{ route('merchant.select') }}" class="switcher" style="margin-top:8px">🔄 Ganti toko</a>
    @endif
</div>

<div class="sec-title">Pencapaian kamu</div>

{{-- Periode --}}
<form method="GET" id="periodeForm">
    <div class="periode">
        <label><input type="radio" name="periode" value="selama" {{ $period === 'selama' ? 'checked' : '' }} onchange="this.form.submit()"><span>Selama ini</span></label>
        <label><input type="radio" name="periode" value="hari" {{ $period === 'hari' ? 'checked' : '' }} onchange="this.form.submit()"><span>Hari ini</span></label>
        <label><input type="radio" name="periode" value="minggu" {{ $period === 'minggu' ? 'checked' : '' }} onchange="this.form.submit()"><span>Seminggu ini</span></label>
        <label><input type="radio" name="periode" value="kustom" {{ $period === 'kustom' ? 'checked' : '' }} onclick="document.getElementById('pdates').style.display='block'"><span>Kustom</span></label>
    </div>
    <div class="pdates" id="pdates" style="display:{{ $period === 'kustom' ? 'block' : 'none' }}">
        <div class="two">
            <div><label>Dari</label><input type="date" name="dari" value="{{ $dari }}"></div>
            <div><label>Sampai</label><input type="date" name="sampai" value="{{ $sampai }}"></div>
        </div>
        <button type="submit" class="btn" style="margin-top:10px">Terapkan</button>
    </div>
</form>

<p class="periode-info">Menampilkan data <b>{{ $periodLabel }}</b></p>

{{-- Baris 1: basis pelanggan --}}
<div class="g3">
    <div class="acard">
        <div class="n">{{ number_format($totalCustomers) }}</div>
        <div class="l">Total Pelanggan</div>
    </div>
    <div class="acard">
        <div class="n">{{ number_format($pelangganLama) }}</div>
        <div class="l">Pelanggan Lama<br>(Min 2x Datang)</div>
    </div>
    <div class="acard">
        <div class="n">{{ number_format($pelangganBaru) }}</div>
        <div class="l">Pelanggan Baru<br>Sekali Datang</div>
    </div>
</div>

{{-- Grafik garis lama vs baru --}}
@php
    $cmax = max(1, collect($chart)->max(fn ($b) => max($b['baru'], $b['lama'])));
    $n = count($chart);
    $W = 300; $padX = 12; $padTop = 12; $padBot = 24;
    $H = 150; $plotW = $W - 2 * $padX; $plotH = $H - $padTop - $padBot;
    $xat = fn ($i) => $n <= 1 ? $padX + $plotW / 2 : $padX + $i * ($plotW / ($n - 1));
    $yat = fn ($v) => round($padTop + ($plotH - ($v / $cmax) * $plotH), 1);
    $baruPts = ''; $lamaPts = '';
    foreach ($chart as $i => $b) {
        $baruPts .= round($xat($i), 1) . ',' . $yat($b['baru']) . ' ';
        $lamaPts .= round($xat($i), 1) . ',' . $yat($b['lama']) . ' ';
    }
@endphp
<div class="chartcard">
    <div class="ttl">Pelanggan Lama vs Baru</div>
    <div class="legend">
        <span><i class="gold"></i>Lama</span>
        <span><i class="blue"></i>Baru</span>
    </div>
    @if($n > 0)
        <svg class="linechart" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMid meet">
            <line class="grid" x1="{{ $padX }}" y1="{{ $padTop }}" x2="{{ $padX }}" y2="{{ $padTop + $plotH }}"></line>
            <line class="grid" x1="{{ $padX }}" y1="{{ $padTop + $plotH }}" x2="{{ $W - $padX }}" y2="{{ $padTop + $plotH }}"></line>
            <polyline points="{{ trim($lamaPts) }}" fill="none" stroke="var(--gold-d)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"></polyline>
            <polyline points="{{ trim($baruPts) }}" fill="none" stroke="var(--blue)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"></polyline>
            @foreach($chart as $i => $b)
                <circle cx="{{ round($xat($i), 1) }}" cy="{{ $yat($b['lama']) }}" r="3" fill="var(--gold-d)"></circle>
                <circle cx="{{ round($xat($i), 1) }}" cy="{{ $yat($b['baru']) }}" r="3" fill="var(--blue)"></circle>
                <text class="vbaru" x="{{ round($xat($i), 1) }}" y="{{ $yat($b['baru']) - 7 }}" text-anchor="middle">{{ $b['baru'] }}</text>
                <text class="vlama" x="{{ round($xat($i), 1) }}" y="{{ $yat($b['lama']) + 14 }}" text-anchor="middle">{{ $b['lama'] }}</text>
                <text x="{{ round($xat($i), 1) }}" y="{{ $H - 6 }}" text-anchor="middle">{{ $b['label'] }}</text>
            @endforeach
        </svg>
    @else
        <p class="muted">Belum ada data.</p>
    @endif
</div>

{{-- Baris 2: stempel --}}
<div class="g3">
    <div class="acard">
        <div class="n">{{ number_format($totalStempel) }}</div>
        <div class="l">Total Stempel<br>Terkumpul</div>
    </div>
    <div class="acard">
        <div class="n">{{ number_format($stempelLama) }}</div>
        <div class="l">Stempel dari<br>pelanggan lama</div>
    </div>
    <div class="acard">
        <div class="n">{{ number_format($stempelBaru) }}</div>
        <div class="l">Stempel dari<br>pelanggan baru</div>
    </div>
</div>

{{-- Baris 3: rata-rata & hadiah --}}
<div class="g2">
    <div class="acard wide">
        <div class="n">{{ $avgReorder !== null ? $avgReorder . ' hari' : '—' }}</div>
        <div class="l">Rata-rata waktu pelanggan datang lagi</div>
    </div>
    <div class="acard">
        <div class="n gold">{{ number_format($hadiahDitukar) }}</div>
        <div class="l">Hadiah Ditukar</div>
    </div>
</div>

@if($posData)
<div class="sec-title" style="margin-top:8px">Penjualan POS</div>

{{-- Pendapatan & transaksi --}}
<div class="g2" style="margin-bottom:10px">
    <div class="acard wide">
        <div class="n" style="font-size:26px">Rp {{ number_format($posData['posRevenue'], 0, ',', '.') }}</div>
        <div class="l">Total Pendapatan</div>
        @if($posData['posDiscount'] > 0)
            <div style="font-size:12px; color:var(--muted); margin-top:4px">Diskon: Rp {{ number_format($posData['posDiscount'], 0, ',', '.') }}</div>
        @endif
    </div>
    <div class="acard">
        <div class="n">{{ number_format($posData['posTransactions']) }}</div>
        <div class="l">Transaksi</div>
    </div>
</div>

{{-- Metode pembayaran --}}
@php
    $methods = ['cash' => ['label' => '💵 Cash', 'icon' => '💵'], 'qris' => ['label' => '📱 QRIS', 'icon' => '📱'], 'transfer' => ['label' => '🏦 Transfer', 'icon' => '🏦']];
@endphp
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:14px;">
    @foreach($methods as $key => $m)
    @php $d = $posData['byMethod'][$key] ?? ['count' => 0, 'total' => 0]; @endphp
    <div class="acard" style="padding:14px 8px;">
        <div style="font-size:20px; margin-bottom:4px;">{{ $m['icon'] }}</div>
        <div style="font-size:16px; font-weight:800; letter-spacing:-.5px;">{{ $d['count'] }}</div>
        <div style="font-size:10.5px; color:var(--muted); font-weight:600; margin-top:2px;">{{ explode(' ', $m['label'])[1] }}</div>
        <div style="font-size:11px; color:var(--blue); font-weight:700; margin-top:3px;">Rp {{ number_format($d['total'], 0, ',', '.') }}</div>
    </div>
    @endforeach
</div>

{{-- Riwayat transaksi --}}
@if($posData['posOrders']->isNotEmpty())
<div class="chartcard" style="padding:0; overflow:hidden;">
    <div style="padding:14px 16px 10px; font-size:14px; font-weight:800;">Riwayat Transaksi</div>
    @foreach($posData['posOrders']->take(5) as $order)
    <div style="display:flex; align-items:center; gap:10px; padding:11px 16px; border-top:1px solid var(--line);">
        <div style="flex:1; min-width:0;">
            <div style="font-size:13px; font-weight:700;">{{ $order->order_number }}</div>
            <div style="font-size:12px; color:var(--muted);">{{ $order->created_at->format('d M Y · H:i') }}</div>
        </div>
        <div style="font-size:12px; color:var(--muted); font-weight:600; text-transform:uppercase;">
            {{ $order->payment_method }}
        </div>
        <div style="font-size:14px; font-weight:800; color:var(--blue); min-width:80px; text-align:right;">
            Rp {{ number_format($order->total, 0, ',', '.') }}
        </div>
    </div>
    @endforeach
    <a href="{{ route('owner.pos.history') }}"
       style="display:block; padding:13px 16px; text-align:center; font-size:13px; font-weight:700;
              color:var(--blue); border-top:1px solid var(--line); text-decoration:none;">
        Lihat Semua Riwayat →
    </a>
</div>
@endif
@endif

@if($financeData)
{{-- ─────────── LAPORAN KEUANGAN ─────────── --}}
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; margin-top:8px;">
    <div class="sec-title" style="margin:0;">Laporan Keuangan</div>
    <a href="{{ route('owner.laporan') }}" style="font-size:13px; font-weight:700; color:var(--blue); text-decoration:none;">Lihat Detail →</a>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
    <div class="acard" style="align-items:flex-start; text-align:left; padding:16px;">
        <div style="font-size:11.5px; color:var(--muted); font-weight:700; margin-bottom:5px;">Total Pemasukan</div>
        <div style="font-size:18px; font-weight:800; color:var(--ok); letter-spacing:-.5px; line-height:1.2;">
            Rp {{ number_format($financeData['finTotalIncome'], 0, ',', '.') }}
        </div>
    </div>
    <div class="acard" style="align-items:flex-start; text-align:left; padding:16px;">
        <div style="font-size:11.5px; color:var(--muted); font-weight:700; margin-bottom:5px;">Total Pengeluaran</div>
        <div style="font-size:18px; font-weight:800; color:var(--danger); letter-spacing:-.5px; line-height:1.2;">
            Rp {{ number_format($financeData['finTotalExpense'], 0, ',', '.') }}
        </div>
    </div>
</div>
<div class="acard" style="margin-bottom:14px; padding:18px; {{ $financeData['finNetProfit'] >= 0 ? 'background:var(--grad-blue); border:none;' : 'background:#FCE8EB; border-color:#FFCDD2;' }} align-items:flex-start; text-align:left; flex-direction:row; justify-content:space-between;">
    <div>
        <div style="font-size:12px; font-weight:700; {{ $financeData['finNetProfit'] >= 0 ? 'color:rgba(255,255,255,.7)' : 'color:var(--danger)' }}; margin-bottom:4px;">
            Pendapatan Bersih{{ $financeData['finNetProfit'] < 0 ? ' (Rugi)' : '' }}
        </div>
        <div style="font-size:26px; font-weight:800; letter-spacing:-1px; {{ $financeData['finNetProfit'] >= 0 ? 'color:var(--gold-l)' : 'color:var(--danger)' }};">
            {{ $financeData['finNetProfit'] < 0 ? '- ' : '' }}Rp {{ number_format(abs($financeData['finNetProfit']), 0, ',', '.') }}
        </div>
    </div>
    <div style="font-size:32px; opacity:.25; align-self:center;">📈</div>
</div>

@php
    $fc = $financeData['finChart'];
    $fcN = count($fc);
    if ($fcN > 0) {
        $allVals = collect($fc)->flatMap(fn($b) => [$b['income'], $b['expense'], $b['net']]);
        $fcMax = max(1, $allVals->max());
        $fcMin = min(0, $allVals->min());
        $fcRange = max(1, $fcMax - $fcMin);
        $fcW = 300; $fcPadX = 14; $fcPadTop = 14; $fcPadBot = 24; $fcH = 140;
        $fcPlotW = $fcW - 2 * $fcPadX; $fcPlotH = $fcH - $fcPadTop - $fcPadBot;
        $fcXat = fn($i) => $fcN <= 1 ? $fcPadX + $fcPlotW / 2 : $fcPadX + $i * ($fcPlotW / ($fcN - 1));
        $fcYat = fn($v) => round($fcPadTop + (($fcMax - $v) / $fcRange) * $fcPlotH, 1);
        $incPts = $expPts = $netPts = '';
        foreach ($fc as $i => $b) {
            $x = round($fcXat($i), 1);
            $incPts .= "$x,{$fcYat($b['income'])} ";
            $expPts .= "$x,{$fcYat($b['expense'])} ";
            $netPts .= "$x,{$fcYat($b['net'])} ";
        }
        $zeroY = $fcYat(0);
    }
@endphp
@if($fcN > 0)
<div class="chartcard" style="margin-bottom:14px;">
    <div class="ttl">Tren Keuangan</div>
    <div class="legend">
        <span><i style="background:var(--ok); display:inline-block; width:11px; height:11px; border-radius:3px; margin-right:6px; vertical-align:middle;"></i>Pemasukan</span>
        <span><i style="background:var(--danger); display:inline-block; width:11px; height:11px; border-radius:3px; margin-right:6px; vertical-align:middle;"></i>Pengeluaran</span>
        <span><i style="background:var(--blue); display:inline-block; width:11px; height:11px; border-radius:3px; margin-right:6px; vertical-align:middle;"></i>Laba Bersih</span>
    </div>
    <svg class="linechart" viewBox="0 0 {{ $fcW }} {{ $fcH }}" preserveAspectRatio="xMidYMid meet">
        <line class="grid" x1="{{ $fcPadX }}" y1="{{ $fcPadTop }}" x2="{{ $fcPadX }}" y2="{{ $fcPadTop + $fcPlotH }}"></line>
        <line class="grid" x1="{{ $fcPadX }}" y1="{{ $fcPadTop + $fcPlotH }}" x2="{{ $fcW - $fcPadX }}" y2="{{ $fcPadTop + $fcPlotH }}"></line>
        @if($fcMin < 0)
            <line x1="{{ $fcPadX }}" y1="{{ $zeroY }}" x2="{{ $fcW - $fcPadX }}" y2="{{ $zeroY }}"
                  stroke="#ccc" stroke-width="1" stroke-dasharray="4,3"></line>
        @endif
        <polyline points="{{ trim($incPts) }}" fill="none" stroke="var(--ok)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"></polyline>
        <polyline points="{{ trim($expPts) }}" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"></polyline>
        <polyline points="{{ trim($netPts) }}" fill="none" stroke="var(--blue)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" stroke-dasharray="5,3"></polyline>
        @foreach($fc as $i => $b)
            <circle cx="{{ round($fcXat($i), 1) }}" cy="{{ $fcYat($b['net']) }}" r="3" fill="var(--blue)"></circle>
            <text x="{{ round($fcXat($i), 1) }}" y="{{ $fcH - 6 }}" text-anchor="middle" style="font-size:9px; fill:var(--muted); font-weight:600;">{{ $b['label'] }}</text>
        @endforeach
    </svg>
</div>
@endif
@endif
@endsection
