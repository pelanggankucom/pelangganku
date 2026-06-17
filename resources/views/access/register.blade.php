@extends('layouts.app')
@section('title', 'Daftar')

@section('content')
<style>
    .roles { display:flex; gap:10px; margin-bottom:6px; }
    .roles label { flex:1; }
    .roles input { position:absolute; opacity:0; pointer-events:none; }
    .roles .opt { display:block; text-align:center; padding:16px 10px; border:1.5px solid var(--line); border-radius:14px; background:#fff; cursor:pointer; }
    .roles .opt .ic { font-size:26px; }
    .roles .opt b { display:block; font-size:14px; margin-top:4px; }
    .roles .opt small { color:var(--muted); font-size:11.5px; }
    .roles input:checked + .opt { border-color:var(--blue-l); background:#F4F8FF; box-shadow:0 0 0 2px rgba(30,102,208,.15); }
</style>

<div style="text-align:center; margin:24px 0 18px">
    <img src="/logo.svg" alt="" style="height:54px; background:#fff; border-radius:14px; padding:6px; box-shadow:var(--shadow)">
    <h1 style="margin-top:14px">Buat Akun</h1>
    <p class="sub" style="margin-bottom:0">Daftar pakai nomor HP — tanpa email.</p>
</div>

<div class="card">
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label>Daftar sebagai</label>
        <div class="roles">
            <label>
                <input type="radio" name="peran" value="pelanggan" {{ old('peran', 'pelanggan') === 'pelanggan' ? 'checked' : '' }} onchange="document.getElementById('storeRow').style.display='none'">
                <span class="opt"><span class="ic">🙋</span><b>Pelanggan</b><small>Kumpulkan stempel</small></span>
            </label>
            <label>
                <input type="radio" name="peran" value="owner" {{ old('peran') === 'owner' ? 'checked' : '' }} onchange="document.getElementById('storeRow').style.display='block'">
                <span class="opt"><span class="ic">🏪</span><b>Pemilik Toko</b><small>Kelola loyalti</small></span>
            </label>
        </div>

        <label>Nama</label>
        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Nama kamu">

        <div id="storeRow" style="display:{{ old('peran') === 'owner' ? 'block' : 'none' }}">
            <label>Nama Toko</label>
            <input type="text" name="store" value="{{ old('store') }}" placeholder="mis. Toko Baju Andi">
        </div>

        <label>Nomor HP</label>
        <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="08xxxxxxxxxx">
        <p class="muted" style="margin-top:6px">Verifikasi OTP via WhatsApp.</p>

        <label>Password</label>
        <input type="password" name="password" minlength="6" required placeholder="Minimal 6 karakter">

        <label>Ulangi Password</label>
        <input type="password" name="password_confirmation" minlength="6" required>

        <button type="submit" class="btn gold mt">Daftar</button>
    </form>
</div>

<p class="muted" style="text-align:center">Sudah punya akun?
    <a href="{{ route('login') }}" style="color:var(--blue); font-weight:700; text-decoration:none">Masuk</a>
</p>
@endsection
