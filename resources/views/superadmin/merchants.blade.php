@extends('layouts.superadmin')
@section('title', 'Kelola POS')
@section('page-title', 'Kelola POS Digital')

@section('content')

<form method="GET" class="search-row">
    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama atau alamat toko…">
    <button type="submit" class="btn primary">Cari</button>
    @if($q)<a href="{{ route('superadmin.merchants') }}" class="btn muted">Reset</a>@endif
</form>

<div style="margin-bottom:12px; font-size:13px; color:var(--muted); font-weight:600;">
    {{ $merchants->total() }} toko ditemukan
</div>

<div class="user-list">
    @forelse($merchants as $m)
    <div class="user-row">
        <div class="avatar" style="background:{{ $m->pos_granted_by_admin ? '#E4F6EC' : '#EEF2F9' }}; font-size:22px;">
            🏪
        </div>
        <div class="info">
            <div class="name">{{ $m->name }}</div>
            <div class="meta">
                👤 {{ $m->owner?->name ?? '—' }}
                @if($m->address) · 📍 {{ Str::limit($m->address, 40) }}@endif
            </div>
            <div style="margin-top:5px; display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                @if($m->pos_granted_by_admin)
                    <span class="badge ok">✓ Diaktifkan Admin</span>
                @endif
                @if($m->posSubscription && $m->posSubscription->isActive())
                    <span class="badge blue">💳 Berlangganan DOKU — s/d {{ $m->posSubscription->expires_at->format('d M Y') }}</span>
                @elseif($m->posSubscription && $m->posSubscription->status === 'pending')
                    <span class="badge gold">⏳ Menunggu Pembayaran</span>
                @else
                    <span class="badge off">Tidak Berlangganan</span>
                @endif
            </div>
            <div style="margin-top:3px; font-size:12px; color:var(--muted);">
                Dibuat {{ $m->created_at->diffForHumans() }}
            </div>
        </div>
        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
            <form method="POST" action="{{ route('superadmin.merchant.pos.toggle', $m) }}">
                @csrf
                <button type="submit"
                    class="btn sm {{ $m->pos_granted_by_admin ? 'danger' : 'success' }}"
                    title="{{ $m->pos_granted_by_admin ? 'Cabut akses POS' : 'Beri akses POS gratis' }}">
                    {{ $m->pos_granted_by_admin ? 'Cabut POS' : 'Aktifkan POS' }}
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="empty">
        <div class="ico">🏪</div>
        <p>{{ $q ? 'Tidak ada toko yang cocok.' : 'Belum ada toko terdaftar.' }}</p>
    </div>
    @endforelse
</div>

{{-- Pagination --}}
@if($merchants->hasPages())
<div class="pager">
    @if($merchants->onFirstPage())
        <span style="padding:8px 14px; color:var(--muted); font-size:13px; font-weight:700;">‹</span>
    @else
        <a href="{{ $merchants->previousPageUrl() }}">‹</a>
    @endif

    @foreach($merchants->getUrlRange(max(1,$merchants->currentPage()-2), min($merchants->lastPage(),$merchants->currentPage()+2)) as $page => $url)
        @if($page == $merchants->currentPage())
            <span class="active">{{ $page }}</span>
        @else
            <a href="{{ $url }}">{{ $page }}</a>
        @endif
    @endforeach

    @if($merchants->hasMorePages())
        <a href="{{ $merchants->nextPageUrl() }}">›</a>
    @else
        <span style="padding:8px 14px; color:var(--muted); font-size:13px; font-weight:700;">›</span>
    @endif
</div>
@endif

@endsection
