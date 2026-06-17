@extends('layouts.app')
@section('title', 'Kartu Loyalty Saya')

@section('content')
<style>
    .mtop { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
    .mtop .brand { display:flex; align-items:center; gap:8px; font-weight:800; font-size:16px; color:var(--text); }
    .mtop .brand img { height:28px; width:28px; background:#fff; border-radius:8px; padding:3px; box-shadow:var(--shadow); }
    .mtop button { background:none; border:1.5px solid var(--line); color:var(--muted); font-weight:700; font-size:13px; padding:8px 14px; border-radius:11px; cursor:pointer; }
    .savings { background:var(--grad-gold); color:#3A2A00; border-radius:22px; padding:22px; text-align:center; margin-bottom:18px; box-shadow:0 10px 26px rgba(246,185,49,.32); }
    .savings .label { font-size:13px; font-weight:600; opacity:.85; }
    .savings .amount { font-size:38px; font-weight:800; letter-spacing:-1px; margin:4px 0; }
    .cardhead { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
    .cardhead .mname { font-size:17px; font-weight:800; }
    .cardhead .mstamp { font-size:13px; font-weight:700; color:var(--blue); background:#EAF1FF; padding:5px 11px; border-radius:999px; }
    .rwlist { margin-top:14px; border-top:1px solid var(--line); padding-top:6px; }
    .rw { display:flex; gap:12px; align-items:flex-start; padding:12px 0; border-top:1px solid var(--line); }
    .rw:first-child { border-top:none; }
    .rwic { width:42px; height:42px; border-radius:11px; background:#FFF1C9; display:flex; align-items:center; justify-content:center; font-size:22px; flex:none; }
    .rwinfo { flex:1; min-width:0; }
    .rwinfo b { display:block; font-size:14px; }
    .rwinfo span { display:block; font-size:12px; color:var(--muted); }
    .rwinfo .terms { font-size:11.5px; color:var(--muted); font-style:italic; margin-top:2px; }
    .sec-title { font-size:17px; font-weight:800; margin:6px 2px 12px; }
</style>

<div class="mtop">
    <span class="brand"><img src="/logo.svg" alt=""> pelangganku</span>
    <form method="POST" action="{{ route('member.logout') }}">
        @csrf
        <button type="submit">Keluar</button>
    </form>
</div>

<div class="savings">
    <div class="label">💰 Total penghematan kamu</div>
    <div class="amount">Rp {{ number_format($savings, 0, ',', '.') }}</div>
    <div class="label">dari hadiah yang sudah kamu tukarkan</div>
</div>

<div class="sec-title">Kartu Loyalty ({{ count($cards) }})</div>

@forelse($cards as $card)
    @php $ms = collect($card['rewards'])->pluck('reward.milestone')->all(); @endphp
    <div class="card">
        <div class="cardhead">
            <div class="mname">{{ $card['merchant']->name }}</div>
            <div class="mstamp">★ {{ $card['current'] }}/{{ $card['cardSize'] }}</div>
        </div>

        <div class="stamps">
            @for($i = 1; $i <= $card['cardSize']; $i++)
                @php $isM = in_array($i, $ms); $isF = $i <= $card['current']; @endphp
                <div class="stamp {{ $isF ? 'filled' : '' }} {{ $isM ? 'milestone' : '' }}">{{ $isM ? '🎁' : ($isF ? '★' : $i) }}</div>
            @endfor
        </div>

        <div class="rwlist">
            @foreach($card['rewards'] as $s)
                @php
                    $r = $s['reward'];
                    if ($s['claimed']) { $badge = 'Sudah ditukar'; $bclass = 'grey'; }
                    elseif ($s['claimable']) { $badge = 'Bisa ditukar'; $bclass = 'gold'; }
                    else { $badge = ($r->milestone - $card['current']) . ' lagi'; $bclass = 'grey'; }
                @endphp
                <div class="rw">
                    <div class="rwic">🎁</div>
                    <div class="rwinfo">
                        <b>{{ $r->name }}</b>
                        <span>Stempel ke-{{ $r->milestone }}{{ $r->value ? ' · senilai Rp ' . number_format($r->value, 0, ',', '.') : '' }}</span>
                        @if($r->terms)
                            <span class="terms">{{ $r->terms }}</span>
                        @endif
                    </div>
                    <span class="badge {{ $bclass }}">{{ $badge }}</span>
                </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="card">
        <p class="muted">Kamu belum punya kartu loyalty. Kunjungi toko yang memakai pelangganku dan minta stempel dengan nomor HP <b>{{ '0' . substr($account->phone_canonical, 2) }}</b>.</p>
    </div>
@endforelse
@endsection
