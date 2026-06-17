@extends('layouts.app')
@section('title', 'Verifikasi OTP - Lupa Password')

@section('content')
<div style="text-align:center; margin:24px 0 18px">
    <img src="/logo.svg" alt="" style="height:54px; background:#fff; border-radius:14px; padding:6px; box-shadow:var(--shadow)">
    <h1 style="margin-top:14px">Verifikasi OTP</h1>
    <p class="sub" style="margin-bottom:0">Kode OTP dikirim ke WhatsApp Anda.</p>
</div>

<div class="card">
    <form method="POST" action="{{ route('forgot.otp.verify') }}">
        @csrf

        <p style="background:#F4F8FF; padding:12px; border-radius:12px; font-size:13px; text-align:center">
            <strong>{{ session('forgot_phone') }}</strong>
        </p>

        <label style="margin-top:16px">Kode OTP (6 digit)</label>
        <input
            type="text"
            name="otp"
            maxlength="6"
            inputmode="numeric"
            required
            placeholder="000000"
            style="font-size:32px; text-align:center; letter-spacing:8px; font-family:monospace"
            value="{{ old('otp') }}"
        >

        @if ($errors->has('otp'))
            <p style="color:var(--red); font-size:13px; margin-top:8px">{{ $errors->first('otp') }}</p>
        @endif

        <p style="color:var(--muted); font-size:13px; margin-top:12px; text-align:center">
            Kode berlaku selama 10 menit
        </p>

        <button type="submit" class="btn gold mt">Verifikasi & Masuk</button>
    </form>

    <hr style="margin:20px 0; border:none; border-top:1px solid var(--line)">

    <p style="text-align:center; font-size:13px">
        <a href="{{ route('password.request') }}" style="color:var(--blue); font-weight:700; text-decoration:none">Minta kode baru</a>
    </p>
</div>
@endsection
