@extends('layouts.app')
@section('title', 'Masuk')

@section('content')
<div style="text-align:center; margin:24px 0 18px">
    <img src="/logo.svg" alt="" style="height:54px; background:#fff; border-radius:14px; padding:6px; box-shadow:var(--shadow)">
    <h1 style="margin-top:14px">Masuk</h1>
    <p class="sub" style="margin-bottom:0">Owner, kasir, & pelanggan — masuk dengan nomor HP.</p>
</div>

<div class="card">
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Nomor HP</label>
        <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="08xxxxxxxxxx" autofocus>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" class="btn mt">Masuk</button>
    </form>
    <p style="text-align:center; margin-top:14px">
        <a href="{{ route('password.request') }}" class="muted" style="text-decoration:none">Lupa password?</a>
    </p>
</div>

<p class="muted" style="text-align:center">Belum punya akun?
    <a href="{{ route('register') }}" style="color:var(--blue); font-weight:700; text-decoration:none">Daftar di sini</a>
</p>
@endsection
