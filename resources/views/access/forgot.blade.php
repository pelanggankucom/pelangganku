@extends('layouts.app')
@section('title', 'Lupa Password')

@section('content')
<div style="text-align:center; margin:24px 0 18px">
    <img src="/logo.svg" alt="" style="height:54px; background:#fff; border-radius:14px; padding:6px; box-shadow:var(--shadow)">
    <h1 style="margin-top:14px">Lupa Password</h1>
    <p class="sub" style="margin-bottom:0">Atur ulang password pakai nomor HP kamu.</p>
</div>

<div class="card">
    <form method="POST" action="{{ route('password.request') }}">
        @csrf
        <label>Nomor HP</label>
        <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="08xxxxxxxxxx" autofocus>
        <p class="muted" style="margin-top:6px">Verifikasi OTP via WhatsApp (sementara dilewati).</p>

        <label>Password Baru</label>
        <input type="password" name="password" minlength="6" required placeholder="Minimal 6 karakter">

        <label>Ulangi Password Baru</label>
        <input type="password" name="password_confirmation" minlength="6" required>

        <button type="submit" class="btn mt">Simpan Password Baru</button>
    </form>
</div>

<p class="muted" style="text-align:center">
    <a href="{{ route('login') }}" style="color:var(--blue); font-weight:700; text-decoration:none">← Kembali ke Masuk</a>
</p>
@endsection
