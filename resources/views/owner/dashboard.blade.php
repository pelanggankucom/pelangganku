@extends('layouts.app')
@section('title', 'Owner · Dashboard')

@section('content')
    <h1>Panel Owner</h1>
    <p class="sub">{{ $merchant->name }}</p>

    <div class="menu">
        <a href="{{ route('owner.store') }}"><span><span class="ico">🏪</span> Profil Toko</span> <span class="muted">Logo, alamat, sosmed ›</span></a>
        <a href="{{ route('owner.branches') }}"><span><span class="ico">📍</span> Outlet / Cabang</span> <span class="muted">{{ $branchCount }} outlet ›</span></a>
        <a href="{{ route('owner.program') }}"><span><span class="ico">🎯</span> Program & Hadiah</span> <span class="muted">{{ $program?->card_size ?? '-' }} stempel ›</span></a>
        <a href="{{ route('kasir') }}"><span><span class="ico">🧾</span> Buka Kasir</span> <span class="muted">›</span></a>
    </div>
@endsection
