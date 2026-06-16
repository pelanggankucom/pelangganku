@extends('layouts.app')
@section('title', 'Pegawai Kasir')

@section('content')
<style>
    .person { display:flex; align-items:center; gap:13px; padding:13px 0; border-top:1px solid var(--line); }
    .person:first-of-type { border-top:none; }
    .person .av { width:44px; height:44px; border-radius:50%; background:var(--grad-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:17px; flex:none; }
    .person .info { flex:1; min-width:0; }
    .person .info b { display:block; font-size:15px; }
    .person .info span { font-size:12.5px; color:var(--muted); }
</style>

<a href="{{ route('owner.settings') }}" class="muted" style="text-decoration:none;display:inline-block;margin-bottom:10px">‹ Kembali ke Atur</a>
<h1>Pegawai Kasir</h1>
<p class="sub">Orang yang boleh memberi stempel di toko ini.</p>

<div class="card">
    <h2>Daftar Kasir</h2>
    @forelse($cashiers as $c)
        <div class="person">
            <div class="av">{{ strtoupper(substr($c->name, 0, 1)) }}</div>
            <div class="info">
                <b>{{ $c->name }}</b>
                <span>{{ $c->branch?->name ?? 'Semua outlet' }} · PIN aktif</span>
            </div>
            <form action="{{ route('owner.cashiers.destroy', $c) }}" method="POST"
                  onsubmit="return confirm('Hapus kasir {{ $c->name }}?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn sm danger">Hapus</button>
            </form>
        </div>
    @empty
        <p class="muted">Belum ada kasir. Tambahkan di bawah.</p>
    @endforelse
</div>

<div class="card">
    <h2>+ Tambah Kasir Baru</h2>
    <form action="{{ route('owner.cashiers.store') }}" method="POST">
        @csrf
        <label>Nama Kasir</label>
        <input type="text" name="name" value="{{ old('name') }}" required placeholder="mis. Andi">

        <label>Email (untuk login)</label>
        <input type="email" name="email" value="{{ old('email') }}" required placeholder="andi@toko.com">

        <label>PIN 4 Angka (untuk masuk)</label>
        <input type="text" name="pin" inputmode="numeric" maxlength="4" minlength="4" pattern="[0-9]{4}" required placeholder="1234">

        <label>Ditempatkan di Outlet</label>
        <select name="branch_id" required>
            <option value="">Pilih outlet…</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn gold mt">Tambah Kasir</button>
    </form>
</div>
@endsection
