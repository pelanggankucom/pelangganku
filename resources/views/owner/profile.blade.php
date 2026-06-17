@extends('layouts.app')
@section('title', 'Profil Saya')

@section('content')
<a href="{{ route('owner.settings') }}" class="muted" style="text-decoration:none;display:inline-block;margin-bottom:10px">‹ Kembali ke Atur</a>
<h1>Profil Saya</h1>
<p class="sub">Akun pemilik toko — nama, nomor telepon, dan password.</p>

<div class="card">
    <form action="{{ route('owner.profile.update') }}" method="POST">
        @csrf

        <label>Nama</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>

        <label>Nomor Telepon</label>
        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">

        <label>Email (untuk login)</label>
        <input type="email" value="{{ $user->email }}" disabled style="background:#F4F6FB;color:var(--muted)">

        <div style="height:1px;background:var(--line);margin:20px 0"></div>

        <label>Password Baru <span class="muted">(kosongkan jika tidak diubah)</span></label>
        <input type="password" name="password" minlength="4" placeholder="Minimal 4 karakter">

        <label>Ulangi Password Baru</label>
        <input type="password" name="password_confirmation" minlength="4" placeholder="Ketik ulang password">

        <button type="submit" class="btn mt">Simpan Profil</button>
    </form>
</div>
@endsection
