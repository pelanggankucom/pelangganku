@extends('layouts.app')
@section('title', 'Owner · Pengaturan')

@section('content')
<style>
    .sec-h { font-size:16px; font-weight:700; margin:20px 0 14px; display:flex; align-items:center; gap:8px; }
    .sec-h:first-of-type { margin-top:0; }
    .hero { background:linear-gradient(135deg,var(--blue) 0%,var(--blue-l) 100%); color:#fff; border-radius:20px; padding:20px; margin-bottom:16px; }
    .hero .label { font-size:13px; opacity:.85; }
    .hero .big { font-size:28px; font-weight:800; margin:4px 0; }
    .member-item { display:flex; align-items:center; gap:12px; padding:12px; background:var(--panel); border:1px solid var(--line); border-radius:12px; margin-bottom:10px; }
    .member-item .avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,var(--blue),var(--blue-l)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; flex:none; }
    .member-item .info { flex:1; }
    .member-item .info b { display:block; font-size:14px; }
    .member-item .info span { font-size:12px; color:var(--muted); }
    .member-item .actions { display:flex; gap:6px; }
    .member-item .actions button { padding:6px 10px; font-size:11px; border:1px solid var(--line); background:#fff; border-radius:8px; cursor:pointer; }
    .member-item .actions .btn-danger { border-color:var(--danger); color:var(--danger); }
</style>

<div class="hero">
    <div class="label">Pengaturan Akun</div>
    <div class="big">{{ $merchant->name }}</div>
</div>

{{-- Profil Toko --}}
<div class="sec-h">🏪 Profil Toko</div>
<div class="card">
    <form action="{{ route('owner.settings.profile') }}" method="POST">
        @csrf
        <label>Nama Toko</label>
        <input type="text" name="name" value="{{ $merchant->name }}" required>

        <label>Alamat</label>
        <textarea name="address">{{ $merchant->address }}</textarea>

        <label>No. Telepon</label>
        <input type="tel" name="phone" value="{{ $merchant->phone }}">

        <label>Instagram</label>
        <input type="text" name="instagram" value="{{ $merchant->instagram }}" placeholder="@username">

        <label>WhatsApp</label>
        <input type="tel" name="whatsapp" value="{{ $merchant->whatsapp }}">

        <label>Facebook</label>
        <input type="text" name="facebook" value="{{ $merchant->facebook }}" placeholder="nama_halaman">

        <label>TikTok</label>
        <input type="text" name="tiktok" value="{{ $merchant->tiktok }}" placeholder="@username">

        <label>Website</label>
        <input type="url" name="website" value="{{ $merchant->website }}">

        <button type="submit" class="btn mt">Simpan Perubahan</button>
    </form>
</div>

{{-- Kelola Kasir --}}
<div class="sec-h">👥 Kelola Kasir</div>
<div class="card">
    @forelse($cashiers as $c)
        <div class="member-item">
            <div class="avatar">{{ substr($c->name, 0, 1) }}</div>
            <div class="info">
                <b>{{ $c->name }}</b>
                <span>{{ $c->email }} · Outlet: {{ $c->branch?->name ?? '-' }}</span>
            </div>
            <div class="actions">
                <form action="{{ route('owner.settings.cashier.destroy', $c->id) }}" method="POST" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger" onclick="return confirm('Yakin hapus kasir ini?')">Hapus</button>
                </form>
            </div>
        </div>
    @empty
        <p class="muted">Belum ada kasir terdaftar.</p>
    @endforelse

    <form action="{{ route('owner.settings.cashier.store') }}" method="POST" class="mt">
        @csrf
        <label>Nama Kasir</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>PIN (4 digit)</label>
        <input type="text" name="pin" inputmode="numeric" minlength="4" maxlength="4" required>

        <label>Assign ke Outlet</label>
        <select name="branch_id" required>
            <option value="">Pilih outlet...</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn gold mt">+ Tambah Kasir</button>
    </form>
</div>

{{-- Akun Pemilik --}}
<div class="sec-h">🔐 Akun Pemilik</div>
<div class="card">
    <p class="muted" style="font-size:14px; margin-bottom:16px;">Email: <b>{{ auth()->user()->email }}</b></p>
    <p class="muted" style="font-size:13px;">Untuk mengubah password atau email, hubungi dukungan.</p>
</div>

@endsection
