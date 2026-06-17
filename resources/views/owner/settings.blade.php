@extends('layouts.app')
@section('title', 'Atur')

@section('content')
<style>
    .menu { background:#fff; border:1px solid var(--line); border-radius:20px; overflow:hidden; box-shadow:var(--shadow); margin-bottom:16px; }
    .menu a, .menu button { display:flex; align-items:center; gap:14px; width:100%; padding:16px 18px; text-decoration:none; color:var(--text); background:none; border:none; border-top:1px solid var(--line); cursor:pointer; text-align:left; font-family:inherit; }
    .menu a:first-child, .menu button:first-child { border-top:none; }
    .menu a:active, .menu button:active { background:var(--bg); }
    .menu .ic { width:46px; height:46px; border-radius:14px; background:var(--grad-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; flex:none; }
    .menu .ic.gold { background:var(--grad-gold); }
    .menu .tx { flex:1; }
    .menu .tx b { display:block; font-size:15.5px; font-weight:700; }
    .menu .tx span { font-size:13px; color:var(--muted); }
    .menu .chev { color:var(--muted); font-size:20px; }
    .menu .ic.danger { background:#FCE8EB; color:var(--danger); }
</style>

<div class="hero">
    <div class="label">Halo {{ auth()->user()->name }} <b style="font-weight:700;opacity:.9">owner</b> 👋</div>
    <div class="big">{{ $merchant->name }}</div>
    <div class="label">Atur toko, hadiah, outlet, dan kasir.</div>
</div>

<div class="menu">
    <a href="{{ route('owner.profile') }}">
        <div class="ic">👤</div>
        <div class="tx"><b>Profil Saya</b><span>Nama, nomor telepon, password</span></div>
        <div class="chev">›</div>
    </a>
    <a href="{{ route('owner.store') }}">
        <div class="ic">🏪</div>
        <div class="tx"><b>Profil Toko</b><span>Nama, alamat, logo, media sosial</span></div>
        <div class="chev">›</div>
    </a>
    <a href="{{ route('owner.branches') }}">
        <div class="ic">📍</div>
        <div class="tx"><b>Outlet &amp; Pegawai</b><span>{{ $branchCount }} outlet · {{ $cashierCount }} kasir</span></div>
        <div class="chev">›</div>
    </a>
    <a href="{{ route('owner.program') }}">
        <div class="ic gold">🎁</div>
        <div class="tx"><b>Hadiah &amp; Stempel</b><span>{{ $rewardCount }} hadiah · atur jumlah stempel</span></div>
        <div class="chev">›</div>
    </a>
</div>

<div class="menu">
    @if($storeCount > 1)
    <a href="{{ route('merchant.select') }}">
        <div class="ic gold">🔄</div>
        <div class="tx"><b>Ganti Toko</b><span>Pindah ke toko kamu yang lain</span></div>
        <div class="chev">›</div>
    </a>
    @endif
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit">
            <div class="ic danger">🚪</div>
            <div class="tx"><b style="color:var(--danger)">Keluar</b><span>Logout dari akun</span></div>
        </button>
    </form>
</div>

<p class="muted" style="text-align:center">pelangganku.com</p>
@endsection
