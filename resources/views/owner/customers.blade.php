@extends('layouts.app')
@section('title', 'Pelanggan')

@section('content')
<style>
    .filterbar { background:#fff; border:1px solid var(--line); border-radius:16px; padding:14px; margin-bottom:16px; box-shadow:var(--shadow); }
    .filterbar label { margin-top:0; }
    .filterbar .row { display:flex; gap:8px; align-items:flex-end; }
    .filterbar .row > div { flex:1; }
    .filterbar .clear { font-size:13px; color:var(--muted); text-decoration:none; display:inline-block; margin-top:8px; }
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
    .count { font-size:13px; color:var(--muted); margin:0 4px 12px; font-weight:600; }
</style>

<h1>Pelanggan</h1>
<p class="sub">Siapa yang rajin datang, dan siapa yang sudah lama menghilang.</p>

{{-- Filter tanggal --}}
<form method="GET" class="filterbar">
    <div class="row">
        <div>
            <label for="sebelum">Tampilkan yang tidak hadir sejak</label>
            <input type="date" id="sebelum" name="sebelum" value="{{ $sebelum }}" onchange="this.form.submit()">
        </div>
        <button type="submit" class="btn" style="width:auto;padding:14px 18px">Cari</button>
    </div>
    @if($sebelum)
        <a href="{{ route('owner.customers') }}" class="clear">&times; Hapus filter</a>
    @endif
</form>

@php
    $countNote = $sebelum
        ? 'belum hadir sejak ' . \Carbon\Carbon::parse($sebelum)->isoFormat('D MMM Y')
        : '';
@endphp
<p class="count">{{ $customers->count() }} pelanggan {{ $countNote }}</p>

<div class="card">
    @forelse($customers as $c)
        @php
            $last = $c->last_visit ? \Carbon\Carbon::parse($c->last_visit) : null;
            $days = $last ? (int) $last->diffInDays(now()) : null;
            $cold = ($last === null) || ($days >= 30);

            $phone = $c->phone_raw ?: ('0' . substr($c->phone_canonical, 2));

            $tone = '';
            $whenText = 'Belum hadir';
            $whenDate = '';
            if ($last) {
                $whenText = ($days == 0) ? 'Hari ini' : ($days . ' hari lalu');
                $whenDate = $last->isoFormat('D MMM');
                $tone = ($days >= 30) ? 'warn' : (($days <= 3) ? 'ok' : '');
            } else {
                $tone = 'warn';
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
        <p class="muted">Belum ada data pelanggan.</p>
    @endforelse
</div>
@endsection
