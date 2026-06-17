@extends('layouts.superadmin')
@section('title', 'Kelola Owner')
@section('page-title', 'Kelola Owner')

@section('content')

<form method="GET" class="search-row">
    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama, nomor HP, atau email…">
    <button type="submit" class="btn primary">Cari</button>
    @if($q)<a href="{{ route('superadmin.owners') }}" class="btn muted">Reset</a>@endif
</form>

<div style="margin-bottom:12px; font-size:13px; color:var(--muted); font-weight:600;">
    {{ $owners->total() }} owner ditemukan
</div>

<div class="user-list">
    @forelse($owners as $u)
    <div class="user-row" id="row-{{ $u->id }}">
        <div class="avatar" style="background:{{ $u->is_active ? '#E3EDFF' : '#F0F2F7' }}">
            {{ mb_strtoupper(mb_substr($u->name,0,1)) }}
        </div>
        <div class="info">
            <div class="name">{{ $u->name }}</div>
            <div class="meta">
                📱 {{ $u->phone }}
                @if($u->email) · ✉️ {{ $u->email }}@endif
            </div>
            <div class="merchant-tag">
                🏪 {{ $u->merchants->count() }} toko
                @if($u->merchants->isNotEmpty())
                    · {{ $u->merchants->pluck('name')->take(2)->join(', ') }}
                    @if($u->merchants->count() > 2) +{{ $u->merchants->count() - 2 }} lainnya @endif
                @endif
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
                    <button type="submit" class="btn sm {{ $u->is_active ? 'muted' : 'success' }}"
                        title="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
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
        <div class="ico">👤</div>
        <p>{{ $q ? 'Tidak ada owner yang cocok.' : 'Belum ada owner terdaftar.' }}</p>
    </div>
    @endforelse
</div>

{{-- Pagination --}}
@if($owners->hasPages())
<div class="pager">
    @if($owners->onFirstPage())
        <span style="padding:8px 14px; color:var(--muted); font-size:13px; font-weight:700;">‹</span>
    @else
        <a href="{{ $owners->previousPageUrl() }}">‹</a>
    @endif

    @foreach($owners->getUrlRange(max(1,$owners->currentPage()-2), min($owners->lastPage(),$owners->currentPage()+2)) as $page => $url)
        @if($page == $owners->currentPage())
            <span class="active">{{ $page }}</span>
        @else
            <a href="{{ $url }}">{{ $page }}</a>
        @endif
    @endforeach

    @if($owners->hasMorePages())
        <a href="{{ $owners->nextPageUrl() }}">›</a>
    @else
        <span style="padding:8px 14px; color:var(--muted); font-size:13px; font-weight:700;">›</span>
    @endif
</div>
@endif

{{-- Confirm Delete Modal --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>Hapus Akun Owner?</h3>
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
