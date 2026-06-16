@extends('layouts.app')
@section('title', 'Daftar Pelanggan Baru')

@section('content')
    <h1>Pelanggan Baru</h1>
    <p class="sub">Nomor belum terdaftar. Daftarkan dengan cepat.</p>

    <form action="{{ route('kasir.register') }}" method="POST" class="card">
        @csrf
        <input type="hidden" name="phone" value="{{ $phone_canonical }}">

        <label>Nomor Telepon</label>
        <input type="tel" value="{{ $phone_display }}" disabled>

        <label for="name">Nama Pelanggan</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus placeholder="mis. Budi">

        <button type="submit" class="btn mt">Daftar &amp; Lanjut</button>
        <a href="{{ route('kasir') }}" class="btn secondary mt">Batal</a>
    </form>
@endsection
