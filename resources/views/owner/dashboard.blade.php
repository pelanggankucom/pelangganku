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
    .bars { display:flex; align-items:flex-end; gap:6px; height:150px; }
    .col { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; gap:5px; height:100%; }
    .pair { display:flex; align-items:flex-end; gap:3px; height:100%; width:100%; justify-content:center; }
    .b { width:44%; border-radius:5px 5px 0 0; min-height:3px; }
    .b.baru { background:linear-gradient(180deg,var(--blue-l),var(--blue)); }
    .b.lama { background:linear-gradient(180deg,var(--gold-l),var(--gold-d)); }
    .lab { font-size:9.5px; color:var(--muted); font-weight:600; }
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
    <div class="pdates" id="pdates" style="display:none">
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

{{-- Grafik lama vs baru --}}
@php $cmax = max(1, collect($chart)->max(fn ($b) => max($b['baru'], $b['lama']))); @endphp
<div class="chartcard">
    <div class="ttl">Pelanggan Lama vs Baru</div>
    <div class="legend">
        <span><i class="gold"></i>Lama</span>
        <span><i class="blue"></i>Baru</span>
    </div>
    <div class="bars">
        @forelse($chart as $b)
            <div class="col">
                <div class="pair">
                    <div class="b lama" style="height:{{ round($b['lama'] / $cmax * 100) }}%" title="Lama: {{ $b['lama'] }}"></div>
                    <div class="b baru" style="height:{{ round($b['baru'] / $cmax * 100) }}%" title="Baru: {{ $b['baru'] }}"></div>
                </div>
                <div class="lab">{{ $b['label'] }}</div>
            </div>
        @empty
            <p class="muted">Belum ada data.</p>
        @endforelse
    </div>
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
@endsection
