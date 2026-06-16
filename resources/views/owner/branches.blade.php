@extends('layouts.app')
@section('title', 'Outlet / Cabang')

@section('content')
    <h1>Outlet / Cabang</h1>
    <p class="sub">Kelola lebih dari satu lokasi toko.</p>

    {{-- Tambah outlet --}}
    <form action="{{ route('owner.branches.store') }}" method="POST" class="card">
        @csrf
        <h2>+ Tambah Outlet</h2>
        <label for="name">Nama Outlet</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="mis. Cabang Mall">
        <label for="address">Alamat</label>
        <input type="text" id="address" name="address" value="{{ old('address') }}">
        <label for="phone">Telepon</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
        <button type="submit" class="btn mt">Tambah Outlet</button>
    </form>

    {{-- Daftar outlet --}}
    @forelse($branches as $branch)
        <div class="card">
            <form action="{{ route('owner.branches.update', $branch) }}" method="POST">
                @csrf
                @method('PUT')
                <label>Nama Outlet</label>
                <input type="text" name="name" value="{{ $branch->name }}" required>
                <label>Alamat</label>
                <input type="text" name="address" value="{{ $branch->address }}">
                <label>Telepon</label>
                <input type="text" name="phone" value="{{ $branch->phone }}">
                <label style="display:flex; align-items:center; gap:8px; color:var(--text)">
                    <input type="checkbox" name="is_active" value="1" {{ $branch->is_active ? 'checked' : '' }} style="width:auto"> Aktif
                </label>
                <div class="row mt">
                    <button type="submit" class="btn sm">Simpan</button>
                </div>
            </form>
            <form action="{{ route('owner.branches.destroy', $branch) }}" method="POST"
                  onsubmit="return confirm('Hapus outlet {{ $branch->name }}?');" style="margin-top:8px">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn sm danger">Hapus Outlet</button>
            </form>
        </div>
    @empty
        <p class="muted">Belum ada outlet.</p>
    @endforelse

    <a href="{{ route('owner.dashboard') }}" class="btn secondary">← Kembali</a>
@endsection
