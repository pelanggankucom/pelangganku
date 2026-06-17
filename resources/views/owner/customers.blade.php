@extends('layouts.app')
@section('title', 'Pelanggan')

@section('content')
<style>
    .ctrls { display:flex; gap:10px; margin-bottom:12px; }
    .ctrls .cbtn { flex:1; padding:14px 12px; border:1.5px solid var(--line); background:#fff; border-radius:14px; font-weight:700; font-size:13.5px; color:var(--text); cursor:pointer; text-align:center; box-shadow:var(--shadow); }
    .ctrls .cbtn.on { border-color:var(--blue-l); color:var(--blue); background:#F4F8FF; }
    .panel { background:#fff; border:1px solid var(--line); border-radius:16px; padding:14px; margin-bottom:12px; box-shadow:var(--shadow); display:none; }
    .panel.open { display:block; }
    .panel .ttl { font-size:12px; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px; }
    .pills { display:flex; gap:8px; flex-wrap:wrap; }
    .pills label { flex:1; min-width:90px; }
    .pills input { position:absolute; opacity:0; pointer-events:none; }
    .pills span { display:block; text-align:center; padding:11px 8px; border:1.5px solid var(--line); border-radius:12px; font-size:13.5px; font-weight:700; color:var(--muted); cursor:pointer; }
    .pills input:checked + span { background:var(--grad-blue); color:#fff; border-color:transparent; }
    .dates { display:flex; gap:8px; align-items:flex-end; margin-top:12px; }
    .dates > div { flex:1; }
    .count { font-size:13.5px; color:var(--text); font-weight:700; margin:2px 4px 12px; }
    .cust { display:flex; align-items:center; gap:13px; padding:14px 0; border-top:1px solid var(--line); }
    .cust:first-of-type { border-top:none; }
    .cust .av { width:44px; height:44px; border-radius:50%; background:var(--grad-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:17px; flex:none; }
    .cust .av.cold { background:#C3CEDF; }
    .cust .main { flex:1; min-width:0; }
    .cust .main b { display:block; font-size:15px; font-weight:700; }
    .cust .main .ph { font-size:13px; color:var(--muted); }
    .cust .main .chips { display:flex; gap:8px; margin-top:5px; }
    .cust .main .chip { font-size:11.5px; font-weight:700; padding:3px 9px; border-radius:999px; }
    .chip.star { background:#FFF1C9; color:#8A6A00; }
    .chip.redeem { background:#E8F1FF; color:var(--blue); }
    .cust .when { text-align:right; flex:none; }
    .cust .when .t { font-size:12.5px; font-weight:700; }
    .cust .when .t.warn { color:var(--danger); }
    .cust .when .t.ok { color:var(--ok); }
    .cust .when .d { font-size:10.5px; color:var(--muted); }
</style>

<h1>Pelanggan</h1>
<p class="sub">Siapa yang rajin datang, dan siapa yang sudah lama menghilang.</p>

<form method="GET" id="filterForm">
    {{-- Dua tombol kontrol --}}
    <div class="ctrls">
        <button type="button" class="cbtn {{ $range === 'kustom' ? 'on' : '' }}" onclick="togglePanel('rangePanel')">📅 Range Tanggal</button>
        <button type="button" class="cbtn {{ $hadir ? 'on' : '' }}" onclick="togglePanel('hadirPanel')">🔎 Filter Kehadiran</button>
    </div>

    {{-- Panel range tanggal --}}
    <div class="panel" id="rangePanel">
        <div class="ttl">Lihat data dalam</div>
        <div class="pills">
            <label><input type="radio" name="range" value="minggu" {{ $range === 'minggu' ? 'checked' : '' }} onchange="submitForm()"><span>1 Minggu</span></label>
            <label><input type="radio" name="range" value="bulan" {{ $range === 'bulan' ? 'checked' : '' }} onchange="submitForm()"><span>1 Bulan</span></label>
            <label><input type="radio" name="range" value="kustom" {{ $range === 'kustom' ? 'checked' : '' }} onchange="showDates()"><span>Kustom</span></label>
        </div>
        <div class="dates" id="customDates" style="{{ $range === 'kustom' ? '' : 'display:none' }}">
            <div>
                <label>Dari</label>
                <input type="date" name="dari" value="{{ $dari }}">
            </div>
            <div>
                <label>Sampai</label>
                <input type="date" name="sampai" value="{{ $sampai }}">
            </div>
            <button type="submit" class="btn" style="width:auto;padding:14px 16px">OK</button>
        </div>
    </div>

    {{-- Panel filter kehadiran --}}
    <div class="panel" id="hadirPanel">
        <div class="ttl">Tampilkan pelanggan</div>
        <div class="pills">
            <label><input type="radio" name="hadir" value="" {{ $hadir === '' ? 'checked' : '' }} onchange="submitForm()"><span>Semua</span></label>
            <label><input type="radio" name="hadir" value="hadir" {{ $hadir === 'hadir' ? 'checked' : '' }} onchange="submitForm()"><span>Hadir</span></label>
            <label><input type="radio" name="hadir" value="lama" {{ $hadir === 'lama' ? 'checked' : '' }} onchange="submitForm()"><span>Lama Hilang</span></label>
            <label><input type="radio" name="hadir" value="belum" {{ $hadir === 'belum' ? 'checked' : '' }} onchange="submitForm()"><span>Belum Pernah</span></label>
        </div>
    </div>
</form>

<p class="count">{{ $countText }}</p>

<div class="card">
    @forelse($customers as $c)
        @php
            $last = $c->last_visit ? \Carbon\Carbon::parse($c->last_visit) : null;
            $days = $last ? (int) $last->diffInDays(now()) : null;
            $cold = ($last === null) || ($days >= 30);
            $phone = $c->phone_raw ?: ('0' . substr($c->phone_canonical, 2));

            $tone = 'warn';
            $whenText = 'Belum hadir';
            $whenDate = '';
            if ($last) {
                $whenText = ($days == 0) ? 'Hari ini' : ($days . ' hari lalu');
                $whenDate = $last->isoFormat('D MMM');
                $tone = ($days >= 30) ? 'warn' : (($days <= 3) ? 'ok' : '');
            }
        @endphp
        <div class="cust">
            <div class="av {{ $cold ? 'cold' : '' }}">{{ strtoupper(substr($c->name, 0, 1)) }}</div>
            <div class="main">
                <b>{{ $c->name }}</b>
                <div class="ph">{{ $phone }}</div>
                <div class="chips">
                    <span class="chip star">&#9733; {{ (int) $c->stamps_total }} stempel</span>
                    <span class="chip redeem">&#127873; {{ (int) $c->redeem_count }}x tukar</span>
                </div>
            </div>
            <div class="when">
                <div class="t {{ $tone }}">{{ $whenText }}</div>
                @if($whenDate)
                    <div class="d">{{ $whenDate }}</div>
                @endif
            </div>
        </div>
    @empty
        <p class="muted">Tidak ada pelanggan yang cocok dengan filter.</p>
    @endforelse
</div>

<script>
    function togglePanel(id) {
        var el = document.getElementById(id);
        var other = id === 'rangePanel' ? 'hadirPanel' : 'rangePanel';
        document.getElementById(other).classList.remove('open');
        el.classList.toggle('open');
    }
    function submitForm() { document.getElementById('filterForm').submit(); }
    function showDates() { document.getElementById('customDates').style.display = 'flex'; }
    // Buka panel otomatis kalau ada pilihan non-default.
    @if($range === 'kustom')
        document.getElementById('rangePanel').classList.add('open');
    @endif
    @if($hadir)
        document.getElementById('hadirPanel').classList.add('open');
    @endif
</script>
@endsection
