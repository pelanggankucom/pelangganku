@extends('layouts.superadmin')
@section('title', 'Kelola Fitur')
@section('page-title', 'Kelola POS & Laporan Keuangan')

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
    <div class="user-row" style="flex-wrap:wrap; gap:12px;">
        <div class="avatar" style="background:{{ $m->hasPosAccess() ? '#E4F6EC' : '#EEF2F9' }}; font-size:22px;">
            🏪
        </div>
        <div class="info" style="min-width:0; flex:1;">
            <div class="name">{{ $m->name }}</div>
            <div class="meta">
                👤 {{ $m->owner?->name ?? '—' }}
                @if($m->address) · 📍 {{ Str::limit($m->address, 40) }}@endif
            </div>
            <div style="margin-top:5px; display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                @if($m->pos_granted_by_admin && $m->hasPosAccess())
                    <span class="badge ok">✓ Admin Grant
                        @if($m->pos_admin_expires_at)
                            · s/d {{ $m->pos_admin_expires_at->format('d M Y') }}
                        @else
                            · Selamanya
                        @endif
                    </span>
                @elseif($m->pos_granted_by_admin && !$m->hasPosAccess())
                    <span class="badge off">⌛ Grant Kedaluwarsa</span>
                @endif
                @if($m->posSubscription && $m->posSubscription->isActive())
                    <span class="badge blue">💳 DOKU s/d {{ $m->posSubscription->expires_at->format('d M Y') }}</span>
                @elseif($m->posSubscription && $m->posSubscription->status === 'pending')
                    <span class="badge gold">⏳ DOKU Pending</span>
                @endif
                @if(!$m->hasPosAccess() && (!$m->posSubscription || $m->posSubscription->status !== 'pending'))
                    <span class="badge off">Tidak Aktif</span>
                @endif
            </div>
        </div>

        {{-- Aksi POS --}}
        <div style="width:100%; border-top:1px solid var(--line); padding-top:10px;">
            <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px;">🖥️ POS Digital</div>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                @if($m->pos_granted_by_admin)
                    <form method="POST" action="{{ route('superadmin.merchant.pos.toggle', $m) }}">
                        @csrf
                        <button type="submit" class="btn sm danger">Cabut POS</button>
                    </form>
                    <form method="POST" action="{{ route('superadmin.merchant.pos.expiry', $m) }}"
                          style="display:flex; gap:6px; align-items:center;">
                        @csrf @method('PUT')
                        <input type="date" name="expires_at"
                               value="{{ $m->pos_admin_expires_at?->format('Y-m-d') }}"
                               min="{{ now()->addDay()->format('Y-m-d') }}"
                               style="padding:6px 10px; border:1.5px solid var(--line); border-radius:9px; font-size:12px; font-family:inherit; color:var(--text);">
                        <button type="submit" class="btn sm muted">Ubah Tanggal</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('superadmin.merchant.pos.toggle', $m) }}"
                          style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                        @csrf
                        <input type="date" name="expires_at"
                               style="padding:6px 10px; border:1.5px solid var(--line); border-radius:9px; font-size:12px; font-family:inherit; color:var(--text);">
                        <button type="submit" class="btn sm success">Aktifkan POS</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Aksi Laporan Keuangan --}}
        <div style="width:100%; border-top:1px solid var(--line); padding-top:10px;">
            <div style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px;">
                📊 Laporan Keuangan
                @if($m->finance_granted_by_admin && $m->hasFinanceAccess())
                    <span class="badge ok" style="margin-left:6px; text-transform:none;">✓ Aktif{{ $m->finance_admin_expires_at ? ' s/d '.$m->finance_admin_expires_at->format('d M Y') : ' · Selamanya' }}</span>
                @elseif($m->finance_granted_by_admin && !$m->hasFinanceAccess())
                    <span class="badge off" style="margin-left:6px; text-transform:none;">⌛ Kedaluwarsa</span>
                @elseif($m->financeSubscription?->isActive())
                    <span class="badge blue" style="margin-left:6px; text-transform:none;">💳 DOKU s/d {{ $m->financeSubscription->expires_at->format('d M Y') }}</span>
                @endif
            </div>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                @if($m->finance_granted_by_admin)
                    <form method="POST" action="{{ route('superadmin.merchant.finance.toggle', $m) }}">
                        @csrf
                        <button type="submit" class="btn sm danger">Cabut Laporan</button>
                    </form>
                    <form method="POST" action="{{ route('superadmin.merchant.finance.expiry', $m) }}"
                          style="display:flex; gap:6px; align-items:center;">
                        @csrf @method('PUT')
                        <input type="date" name="expires_at"
                               value="{{ $m->finance_admin_expires_at?->format('Y-m-d') }}"
                               min="{{ now()->addDay()->format('Y-m-d') }}"
                               style="padding:6px 10px; border:1.5px solid var(--line); border-radius:9px; font-size:12px; font-family:inherit; color:var(--text);">
                        <button type="submit" class="btn sm muted">Ubah Tanggal</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('superadmin.merchant.finance.toggle', $m) }}"
                          style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                        @csrf
                        <input type="date" name="expires_at"
                               style="padding:6px 10px; border:1.5px solid var(--line); border-radius:9px; font-size:12px; font-family:inherit; color:var(--text);">
                        <button type="submit" class="btn sm" style="background:#1A237E; color:#fff; border-color:#1A237E;">Aktifkan Laporan</button>
                    </form>
                @endif
            </div>
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
