@extends('layouts.app')
@section('title', 'Owner · Dashboard')

@section('content')
    <div class="hero">
        <div class="label">Panel Owner</div>
        <div class="big">{{ $merchant->name }}</div>
        <div class="label">{{ $branchCount }} outlet · {{ $program?->card_size ?? '-' }} stempel/kartu</div>
    </div>

    <div class="tiles">
        <a href="{{ route('owner.store') }}">
            <div class="ico">🏪</div>
            <b>Profil Toko</b>
            <small>Logo, alamat, sosmed</small>
        </a>
        <a href="{{ route('owner.branches') }}">
            <div class="ico">📍</div>
            <b>Outlet</b>
            <small>{{ $branchCount }} cabang</small>
        </a>
        <a href="{{ route('owner.program') }}" class="gold">
            <div class="ico">🎯</div>
            <b>Program &amp; Hadiah</b>
            <small>Stempel & reward</small>
        </a>
        <a href="{{ route('kasir') }}">
            <div class="ico">🧾</div>
            <b>Buka Kasir</b>
            <small>Mulai melayani</small>
        </a>
    </div>
@endsection
