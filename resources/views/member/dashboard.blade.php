@extends('layouts.app')
@section('title', 'Kartu Loyalty Saya')

@section('content')
<style>
    .savings { display:block; text-decoration:none; background:var(--grad-gold); color:#3A2A00; border-radius:22px; padding:22px; text-align:center; margin-bottom:18px; box-shadow:0 10px 26px rgba(246,185,49,.32); }
    .savings .label { font-size:13px; font-weight:600; opacity:.85; }
    .savings .amount { font-size:38px; font-weight:800; letter-spacing:-1px; margin:4px 0; }
    .savings .more { display:inline-block; margin-top:8px; font-size:12.5px; font-weight:700; background:rgba(58,42,0,.12); padding:5px 12px; border-radius:999px; }
    .searchbox { width:100%; padding:13px 16px; font-size:15px; border-radius:14px; border:1.5px solid var(--line); background:#fff; margin-bottom:14px; }
    .cardhead { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
    .cardhead .mname { font-size:17px; font-weight:800; }
    .cardhead .mstamp { font-size:13px; font-weight:700; color:var(--blue); background:#EAF1FF; padding:5px 11px; border-radius:999px; }
    .rwhint { text-align:center; font-size:12.5px; color:var(--muted); font-weight:600; margin-top:12px; cursor:pointer; }
    .rwlist { margin-top:12px; border-top:1px solid var(--line); padding-top:6px; }
    .rw { display:flex; gap:12px; align-items:flex-start; padding:12px 0; border-top:1px solid var(--line); }
    .rw:first-child { border-top:none; }
    .rwic { width:42px; height:42px; border-radius:11px; background:#FFF1C9; display:flex; align-items:center; justify-content:center; font-size:22px; flex:none; }
    .rwinfo { flex:1; min-width:0; }
    .rwinfo b { display:block; font-size:14px; }
    .rwinfo span { display:block; font-size:12px; color:var(--muted); }
    .rwinfo .terms { font-size:11.5px; color:var(--muted); font-style:italic; margin-top:2px; }
    .sec-title { font-size:17px; font-weight:800; margin:6px 2px 12px; }
    .stamp.gift-clickable { cursor:pointer; }
    .noresult { text-align:center; color:var(--muted); padding:20px; display:none; }
</style>

<a href="{{ route('member.history') }}" class="savings">
    <div class="label">💰 Total penghematan kamu</div>
    <div class="amount">Rp {{ number_format($savings, 0, ',', '.') }}</div>
    <div class="label">dari hadiah yang sudah kamu tukarkan</div>
    <span class="more">Lihat riwayat ›</span>
</a>

<div class="sec-title">Kartu Loyalty ({{ count($cards) }})</div>

@if(count($cards) > 0)
    <input type="search" id="cardSearch" class="searchbox" placeholder="🔎 Cari toko…" oninput="filterCards()">
@endif

<div id="cardList">
@forelse($cards as $card)
    @php $ci = $loop->index; $ms = collect($card['rewards'])->pluck('reward.milestone')->all(); @endphp
    <div class="card lcard" data-name="{{ strtolower($card['merchant']->name) }}">
        <div class="cardhead">
            <div class="mname">{{ $card['merchant']->name }}</div>
            <div class="mstamp">★ {{ $card['current'] }}/{{ $card['cardSize'] }}</div>
        </div>

        <div class="stamps">
            @for($i = 1; $i <= $card['cardSize']; $i++)
                @php $isM = in_array($i, $ms); $isF = $i <= $card['current']; @endphp
                @if($isM)
                    <div class="stamp gift-clickable {{ $isF ? 'filled' : '' }} milestone" onclick="toggleRw({{ $ci }})">🎁</div>
                @else
                    <div class="stamp {{ $isF ? 'filled' : '' }}">{{ $isF ? '★' : $i }}</div>
                @endif
            @endfor
        </div>

        <div class="rwhint" onclick="toggleRw({{ $ci }})">🎁 Ketuk ikon kado untuk lihat hadiah</div>

        <div class="rwlist" id="rw-{{ $ci }}" style="display:none">
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
</div>
<p class="noresult" id="noResult">Toko tidak ditemukan.</p>

<script>
    function toggleRw(i) {
        var el = document.getElementById('rw-' + i);
        if (el) el.style.display = (el.style.display === 'none' || !el.style.display) ? 'block' : 'none';
    }
    function filterCards() {
        var q = document.getElementById('cardSearch').value.toLowerCase().trim();
        var cards = document.querySelectorAll('#cardList .lcard');
        var shown = 0;
        cards.forEach(function (c) {
            var match = c.dataset.name.indexOf(q) !== -1;
            c.style.display = match ? '' : 'none';
            if (match) shown++;
        });
        document.getElementById('noResult').style.display = (shown === 0 && q !== '') ? 'block' : 'none';
    }
</script>
@endsection
