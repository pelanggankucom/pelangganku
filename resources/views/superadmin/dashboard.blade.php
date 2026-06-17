@extends('layouts.superadmin')
@section('title', 'Dashboard Super Admin')
@section('page-title', 'Dashboard')

@section('content')

<div class="stat-grid">
    <div class="stat-card">
        <div class="ico">👤</div>
        <div class="n blue">{{ $totalOwners }}</div>
        <div class="lbl">Total Owner</div>
        <div class="sub-n">{{ $activeOwners }} aktif</div>
    </div>
    <div class="stat-card">
        <div class="ico">🧾</div>
        <div class="n blue">{{ $totalKasir }}</div>
        <div class="lbl">Total Kasir</div>
        <div class="sub-n">{{ $activeKasir }} aktif</div>
    </div>
    <div class="stat-card">
        <div class="ico">🏪</div>
        <div class="n gold">{{ $totalMerchants }}</div>
        <div class="lbl">Total Merchant</div>
    </div>
    <div class="stat-card">
        <div class="ico">👥</div>
        <div class="n ok">{{ number_format($totalCustomers) }}</div>
        <div class="lbl">Total Pelanggan</div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

    <div>
        <div class="section-title">Owner Terbaru</div>
        <div class="user-list">
            @forelse($recentOwners as $u)
            <div class="user-row">
                <div class="avatar">{{ mb_strtoupper(mb_substr($u->name,0,1)) }}</div>
                <div class="info">
                    <div class="name">{{ $u->name }}</div>
                    <div class="meta">{{ $u->phone }}</div>
                    <div class="merchant-tag">
                        {{ $u->merchants->count() }} toko
                        @if($u->merchants->first()) · {{ $u->merchants->first()->name }}@endif
                    </div>
                </div>
                <span class="badge {{ $u->is_active ? 'ok' : 'off' }}">
                    {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            @empty
            <div class="empty"><div class="ico">👤</div><p>Belum ada owner</p></div>
            @endforelse
        </div>
        <div style="margin-top:12px">
            <a href="{{ route('superadmin.owners') }}" class="btn primary">Lihat semua owner →</a>
        </div>
    </div>

    <div>
        <div class="section-title">Merchant Terbaru</div>
        <div class="user-list">
            @forelse($recentMerchants as $m)
            <div class="user-row">
                <div class="avatar">🏪</div>
                <div class="info">
                    <div class="name">{{ $m->name }}</div>
                    <div class="meta">{{ $m->phone ?: 'Belum ada no. HP' }}</div>
                    <div class="merchant-tag">
                        Owner: {{ $m->owner?->name ?? '—' }}
                    </div>
                </div>
                <span class="badge {{ $m->is_active ? 'ok' : 'off' }}">
                    {{ $m->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            @empty
            <div class="empty"><div class="ico">🏪</div><p>Belum ada merchant</p></div>
            @endforelse
        </div>
        <div style="margin-top:12px">
            <a href="{{ route('superadmin.kasir') }}" class="btn primary">Lihat semua kasir →</a>
        </div>
    </div>

</div>

@endsection
