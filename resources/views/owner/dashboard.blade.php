@extends('layouts.app')
@section('title', 'Owner · Dashboard')

@section('content')
<style>
    .hero { position:relative; }
    .hero-ctrl { display:flex; gap:10px; margin-top:12px; }
    .hero-ctrl > * { flex:1; font-size:13px; padding:10px; border-radius:12px; background:rgba(255,255,255,.2); color:#fff; border:1px solid rgba(255,255,255,.3); cursor:pointer; }
    .hero-ctrl select, .hero-ctrl input { width:100%; }
    .stats { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
    .stat { background:#fff; border:1px solid var(--line); border-radius:16px; padding:16px; position:relative; overflow:hidden; }
    .stat::before { content:""; position:absolute; top:0; left:0; width:100%; height:3px; background:linear-gradient(90deg,var(--blue),var(--blue-l)); }
    .stat.gold::before { background:linear-gradient(90deg,var(--gold),var(--gold-d)); }
    .stat .lbl { font-size:12px; color:var(--muted); display:flex; align-items:center; gap:6px; }
    .stat .val { font-size:28px; font-weight:800; letter-spacing:-.5px; margin-top:4px; color:var(--text); }
    .stat .dlt { font-size:12px; margin-top:2px; }
    .dlt.up { color:#1d7a45; } .dlt.muted { color:var(--muted); } .dlt.gold { color:var(--gold-d); }
    .today { display:flex; gap:10px; }
    .today .t { flex:1; background:linear-gradient(135deg,var(--blue),var(--blue-l)); color:#fff; border-radius:14px; padding:14px; text-align:center; }
    .today .t.g { background:linear-gradient(135deg,var(--gold),var(--gold-d)); color:#3a2c00; }
    .today .t b { font-size:22px; display:block; }
    .today .t span { font-size:11px; opacity:.9; }
    .chart .bars { display:flex; align-items:flex-end; gap:8px; height:104px; margin-top:6px; }
    .chart .bc { flex:1; display:flex; flex-direction:column; align-items:center; gap:5px; height:100%; justify-content:flex-end; }
    .chart .bar { width:70%; border-radius:6px 6px 0 0; background:linear-gradient(180deg,var(--gold),var(--gold-d)); min-height:3px; }
    .chart.blue .bar { background:linear-gradient(180deg,var(--blue-l),var(--blue)); }
    .chart .bv { font-size:11px; font-weight:700; color:var(--text); }
    .chart .bl { font-size:10px; color:var(--muted); }
    .loyal { display:flex; align-items:center; gap:12px; padding:11px 0; border-top:1px solid var(--line); }
    .loyal:first-of-type { border-top:none; }
    .loyal .rk { width:26px; height:26px; border-radius:50%; background:#eef3fb; color:var(--blue); font-weight:800; font-size:13px; display:flex; align-items:center; justify-content:center; }
    .loyal .rk.top { background:linear-gradient(135deg,var(--gold),var(--gold-d)); color:#3a2c00; }
    .loyal .nm { flex:1; font-weight:600; font-size:14px; }
    .loyal .pt { font-size:13px; color:var(--gold-d); font-weight:700; }
    .sec-h { font-size:15px; font-weight:700; margin:0 0 12px; display:flex; align-items:center; gap:8px; }
</style>

<div class="hero">
    <div class="label">Panel Owner · Outlet: <strong>{{ $branches->firstWhere('id', $selectedBranchId)?->name ?? 'Semua' }}</strong></div>
    <div class="big">{{ $merchant->name }}</div>
    <div class="label">{{ number_format($totalCustomers) }} pelanggan · {{ number_format($loyalCount) }} loyal · {{ $branches->count() }} outlet</div>
    <form method="GET" class="hero-ctrl">
        @if($branches->count() > 1)
        <select name="branch" onchange="this.form.submit()">
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $b->id == $selectedBranchId ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        @endif
        <input type="date" name="from" value="{{ $fromDate->format('Y-m-d') }}" onchange="this.form.submit()">
        <input type="date" name="to" value="{{ $toDate->format('Y-m-d') }}" onchange="this.form.submit()">
    </form>
</div>

{{-- KPI utama --}}
<div class="stats">
    <div class="stat">
        <div class="lbl">👥 Total Pelanggan</div>
        <div class="val">{{ number_format($totalCustomers) }}</div>
        <div class="dlt up">+{{ $newThisMonth }} bulan ini</div>
    </div>
    <div class="stat gold">
        <div class="lbl">⭐ Pelanggan Loyal</div>
        <div class="val">{{ number_format($loyalCount) }}</div>
        <div class="dlt gold">pernah tukar hadiah</div>
    </div>
    <div class="stat">
        <div class="lbl">🔁 Repeat Rate</div>
        <div class="val">{{ $repeatRate }}%</div>
        <div class="dlt muted">{{ $repeatCount }} pelanggan kembali</div>
    </div>
    <div class="stat">
        <div class="lbl">📈 Aktif 30 Hari</div>
        <div class="val">{{ number_format($active30) }}</div>
        <div class="dlt muted">bertransaksi terakhir</div>
    </div>
    <div class="stat">
        <div class="lbl">🏷️ Stempel (bln ini)</div>
        <div class="val">{{ number_format($stampsMonth) }}</div>
        <div class="dlt muted">total diberikan</div>
    </div>
    <div class="stat gold">
        <div class="lbl">🎁 Hadiah Ditukar</div>
        <div class="val">{{ number_format($redeemMonth) }}</div>
        <div class="dlt gold">bulan ini</div>
    </div>
</div>

{{-- Hari ini --}}
<div class="today mt">
    <div class="t g"><b>{{ $stampsToday }}</b><span>Stempel hari ini</span></div>
    <div class="t"><b>{{ $newToday }}</b><span>Pelanggan baru</span></div>
    <div class="t"><b>{{ $redeemToday }}</b><span>Hadiah ditukar</span></div>
</div>

{{-- Tren --}}
@php $maxNew = max(1, collect($trendNew)->max('count')); $maxStamp = max(1, collect($trendStamps)->max('count')); @endphp
<div class="card mt">
    <div class="sec-h">📊 Pelanggan baru — 7 hari terakhir</div>
    <div class="chart">
        <div class="bars">
            @foreach($trendNew as $d)
                <div class="bc">
                    <div class="bv">{{ $d['count'] }}</div>
                    <div class="bar" style="height:{{ round($d['count'] / $maxNew * 100) }}%"></div>
                    <div class="bl">{{ $d['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card">
    <div class="sec-h">🏷️ Stempel diberikan — 7 hari terakhir</div>
    <div class="chart blue">
        <div class="bars">
            @foreach($trendStamps as $d)
                <div class="bc">
                    <div class="bv">{{ $d['count'] }}</div>
                    <div class="bar" style="height:{{ round($d['count'] / $maxStamp * 100) }}%"></div>
                    <div class="bl">{{ $d['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Top loyal --}}
<div class="card">
    <div class="sec-h">🏆 Pelanggan Paling Loyal</div>
    @forelse($topLoyal as $i => $c)
        <div class="loyal">
            <div class="rk {{ $i === 0 ? 'top' : '' }}">{{ $i + 1 }}</div>
            <div class="nm">{{ $c->name }}</div>
            <div class="pt">★ {{ number_format($c->lifetime ?? 0) }} stempel</div>
        </div>
    @empty
        <p class="muted">Belum ada data pelanggan.</p>
    @endforelse
    @if($almostDone > 0)
        <div style="margin-top:12px; background:#fff7e0; border:1px solid var(--gold); border-radius:12px; padding:10px 12px; font-size:13px; color:#8a6d00;">
            💡 <b>{{ $almostDone }} pelanggan</b> tinggal 1–2 stempel lagi untuk dapat hadiah — dorong mereka kembali!
        </div>
    @endif
</div>

@endsection
