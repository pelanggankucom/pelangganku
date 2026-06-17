@extends('layouts.superadmin')
@section('title', 'Kelola Kasir')
@section('page-title', 'Kelola Kasir')

@section('content')

<form method="GET" class="search-row">
    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama atau nomor HP kasir…">
    <button type="submit" class="btn primary">Cari</button>
    @if($q)<a href="{{ route('superadmin.kasir') }}" class="btn muted">Reset</a>@endif
</form>

<div style="margin-bottom:12px; font-size:13px; color:var(--muted); font-weight:600;">
    {{ $kasirList->total() }} kasir ditemukan
</div>

<div class="user-list">
    @forelse($kasirList as $u)
    <div class="user-row">
        <div class="avatar" style="background:{{ $u->is_active ? '#E4F6EC' : '#F0F2F7' }}">
            {{ mb_strtoupper(mb_substr($u->name,0,1)) }}
        </div>
        <div class="info">
            <div class="name">{{ $u->name }}</div>
            <div class="meta">📱 {{ $u->phone ?: '—' }}</div>
            <div class="merchant-tag">
                🏪 {{ $u->merchant?->name ?? 'Belum ada merchant' }}
                @if($u->branch) · 📍 {{ $u->branch->name }}@endif
            </div>
            <div style="margin-top:4px; font-size:12px; color:var(--muted);">
                Daftar {{ $u->created_at->diffForHumans() }}
            </div>
        </div>
        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
            <span class="badge {{ $u->is_active ? 'ok' : 'off' }}">
                {{ $u->is_active ? '✓ Aktif' : '✗ Nonaktif' }}
            </span>
            <div class="actions">
                <form method="POST" action="{{ route('superadmin.user.toggle', $u) }}">
                    @csrf
                    <button type="submit" class="btn sm {{ $u->is_active ? 'muted' : 'success' }}">
                        {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                <button type="button" class="btn sm danger" onclick="confirmDelete({{ $u->id }}, '{{ addslashes($u->name) }}')">
                    Hapus
                </button>
            </div>
        </div>
    </div>
    @empty
    <div class="empty">
        <div class="ico">🧾</div>
        <p>{{ $q ? 'Tidak ada kasir yang cocok.' : 'Belum ada kasir terdaftar.' }}</p>
    </div>
    @endforelse
</div>

{{-- Pagination --}}
@if($kasirList->hasPages())
<div class="pager">
    @if($kasirList->onFirstPage())
        <span style="padding:8px 14px; color:var(--muted); font-size:13px; font-weight:700;">‹</span>
    @else
        <a href="{{ $kasirList->previousPageUrl() }}">‹</a>
    @endif

    @foreach($kasirList->getUrlRange(max(1,$kasirList->currentPage()-2), min($kasirList->lastPage(),$kasirList->currentPage()+2)) as $page => $url)
        @if($page == $kasirList->currentPage())
            <span class="active">{{ $page }}</span>
        @else
            <a href="{{ $url }}">{{ $page }}</a>
        @endif
    @endforeach

    @if($kasirList->hasMorePages())
        <a href="{{ $kasirList->nextPageUrl() }}">›</a>
    @else
        <span style="padding:8px 14px; color:var(--muted); font-size:13px; font-weight:700;">›</span>
    @endif
</div>
@endif

{{-- Confirm Delete Modal --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>Hapus Akun Kasir?</h3>
        <p>Akun <strong id="deleteUserName"></strong> akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.</p>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="row">
                <button type="button" class="btn muted" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(id, name) {
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteForm').action = '/superadmin/user/' + id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
    document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endpush

@endsection
