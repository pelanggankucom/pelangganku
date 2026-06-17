@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
<style>
    .switcher { display:inline-flex; align-items:center; gap:7px; background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.28); color:#fff; padding:7px 13px; border-radius:999px; font-size:13px; font-weight:700; text-decoration:none; position:relative; z-index:1; }
    .periode { display:flex; gap:7px; margin-bottom:10px; }
    .periode label { flex:1; }
    .periode input { position:absolute; opacity:0; pointer-events:none; }
    .periode span { display:block; text-align:center; padding:11px 4px; border-radius:13px; font-size:12.5px; font-weight:700; background:#fff; border:1.5px solid var(--line); color:var(--muted); cursor:pointer; }
    .periode input:checked + span { background:var(--grad-blue); color:#fff; border-color:transparent; box-shadow:0 4px 12px rgba(13,71,161,.22); }
    .pdates { margin-bottom:12px; }
    .pdates .two { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .pdates label { margin-top:0; margin-bottom:5px; display:block; }
    .periode-info { font-size:13.5px; color:var(--text); font-weight:700; margin:0 4px 16px; }
    .periode-info b { color:var(--blue); }
    .big-stat { background:var(--grad-gold); color:#3A2A00; border-radius:20px; padding:18px 20px; margin-bottom:14px; display:flex; align-items:center; gap:16px; box-shadow:0 8px 20px rgba(246,185,49,.28); }
    .big-stat .n { font-size:42px; font-weight:800; line-height:1; letter-spacing:-1px; }
    .big-stat .t b { font-size:15px; display:block; } .big-stat .t span { font-size:12.5px; opacity:.8; }
    .grid2 { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:14px; }
    .mini { background:#fff; border:1px solid var(--line); border-radius:16px; padding:14px 10px; text-align:center; box-shadow:var(--shadow); }
    .mini .n { font-size:26px; font-weight:800; letter-spacing:-.5px; }
    .mini .l { font-size:11.5px; color:var(--muted); font-weight:600; margin-top:2px; line-height:1.25; }
    .callout { display:flex; align-items:center; gap:12px; background:#FFF6DF; border:1px solid var(--gold); border-radius:16px; padding:14px 16px; margin-bottom:14px; }
    .callout .ic { font-size:26px; } .callout b { color:#8A6A00; } .callout p { font-size:13.5px; color:#8A6A00; margin:0; }
    .rank { display:flex; align-items:center; gap:13px; padding:12px 0; border-top:1px solid var(--line); }
    .rank:first-of-type { border-top:none; }
    .rank .medal { width:30px; height:30px; border-radius:50%; background:#EEF3FB; color:var(--blue); font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center; flex:none; }
    .rank .medal.top { background:var(--grad-gold); color:#fff; }
    .rank .nm { flex:1; font-weight:700; font-size:15px; }
    .rank .pt { font-size:13px; color:var(--gold-d); font-weight:800; }
    .sec-title { font-size:16px; font-weight:800; margin:4px 4px 12px; letter-spacing:-.3px; }
</style>

<div class="hero">
    <div class="label" style="position:relative;z-index:1">Halo {{ auth()->user()->name }} <b style="font-weight:700;opacity:.9">owner</b> 👋</div>
    <div class="big">{{ $merchant->name }}</div>
    @if($storeCount > 1)
        <a href="{{ route('merchant.select') }}" class="switcher" style="margin-top:8px">🔄 Ganti toko</a>
    @endif
</div>

{{-- Pelanggan setia (angka paling penting) --}}
<div class="big-stat">
    <div class="n">{{ number_format($loyalCount) }}</div>
    <div class="t">
        <b>Pelanggan Setia</b>
        <span>Sudah pernah menukar hadiah</span>
    </div>
</div>

{{-- Pilih waktu --}}
<form method="GET" id="periodeForm">
    <div class="periode">
        <label><input type="radio" name="periode" value="hari" {{ $period === 'hari' ? 'checked' : '' }} onchange="this.form.submit()"><span>Hari Ini</span></label>
        <label><input type="radio" name="periode" value="minggu" {{ $period === 'minggu' ? 'checked' : '' }} onchange="this.form.submit()"><span>Minggu Ini</span></label>
        <label><input type="radio" name="periode" value="bulan" {{ $period === 'bulan' ? 'checked' : '' }} onchange="this.form.submit()"><span>Bulan Ini</span></label>
        <label><input type="radio" name="periode" value="kustom" {{ $period === 'kustom' ? 'checked' : '' }} onchange="document.getElementById('pdates').style.display='block'"><span>Kustom</span></label>
    </div>
    <div class="pdates" id="pdates" style="{{ $period === 'kustom' ? '' : 'display:none' }}">
        <div class="two">
            <div>
                <label>Dari</label>
                <input type="date" name="dari" value="{{ $dari }}">
            </div>
            <div>
                <label>Sampai</label>
                <input type="date" name="sampai" value="{{ $sampai }}">
            </div>
        </div>
        <button type="submit" class="btn" style="margin-top:10px">Terapkan</button>
    </div>
</form>

<p class="periode-info">Menampilkan data <b>{{ $periodLabel }}</b></p>

{{-- 3 angka sederhana --}}
<div class="grid2">
    <div class="mini">
        <div class="n">{{ number_format($newCustomers) }}</div>
        <div class="l">Pelanggan<br>Baru</div>
    </div>
    <div class="mini">
        <div class="n">{{ number_format($visits) }}</div>
        <div class="l">Kali Diberi<br>Stempel</div>
    </div>
    <div class="mini">
        <div class="n">{{ number_format($rewardsGiven) }}</div>
        <div class="l">Hadiah<br>Ditukar</div>
    </div>
</div>

{{-- Total pelanggan --}}
<div class="card" style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
    <div style="font-size:30px">👥</div>
    <div style="flex:1">
        <div style="font-size:13px;color:var(--muted);font-weight:600">Total pelanggan terdaftar</div>
        <div style="font-size:24px;font-weight:800;letter-spacing:-.5px">{{ number_format($totalCustomers) }} orang</div>
    </div>
</div>

{{-- Ajakan --}}
@if($almostDone > 0)
<div class="callout">
    <div class="ic">🎁</div>
    <p><b>{{ $almostDone }} pelanggan</b> sebentar lagi dapat hadiah. Ajak mereka datang lagi!</p>
</div>
@endif

{{-- Pelanggan paling rajin --}}
<div class="card">
    <div class="sec-title">🏆 Pelanggan Paling Rajin</div>
    @forelse($topLoyal as $i => $c)
        <div class="rank">
            <div class="medal {{ $i === 0 ? 'top' : '' }}">{{ $i + 1 }}</div>
            <div class="nm">{{ $c->name }}</div>
            <div class="pt">★ {{ number_format($c->lifetime ?? 0) }}</div>
        </div>
    @empty
        <p class="muted">Belum ada pelanggan. Mulai beri stempel lewat menu <b>Kasir</b>.</p>
    @endforelse
</div>
@endsection
