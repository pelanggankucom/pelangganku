@extends('layouts.app')
@section('title', 'Program & Hadiah')

@section('content')
    <h1>Program & Hadiah</h1>
    <p class="sub">Atur jumlah stempel per kartu dan posisi hadiah.</p>

    {{-- Pengaturan kartu --}}
    <form action="{{ route('owner.program.update') }}" method="POST" class="card">
        @csrf
        <h2>Ukuran Kartu</h2>
        <label for="card_size">Jumlah stempel dalam 1 kartu</label>
        <input type="number" id="card_size" name="card_size" value="{{ old('card_size', $program->card_size) }}" min="1" max="100" required>
        <label style="display:flex; align-items:center; gap:8px; color:var(--text); margin-top:14px">
            <input type="checkbox" name="carry_over" value="1" {{ $program->carry_over ? 'checked' : '' }} style="width:auto">
            Sisa stempel dibawa ke kartu berikutnya
        </label>
        <button type="submit" class="btn mt">Simpan Ukuran Kartu</button>
    </form>

    {{-- Tambah hadiah --}}
    <form action="{{ route('owner.program.reward.store') }}" method="POST" enctype="multipart/form-data" class="card">
        @csrf
        <h2>+ Tambah Hadiah</h2>
        <label for="rname">Nama Hadiah</label>
        <input type="text" id="rname" name="name" value="{{ old('name') }}" required placeholder="mis. Kopi Gratis">
        <label for="milestone">Diberikan pada stempel ke- (1–{{ $program->card_size }})</label>
        <input type="number" id="milestone" name="milestone" min="1" max="{{ $program->card_size }}" value="{{ old('milestone', $program->card_size) }}" required>
        <label for="terms">Ketentuan (opsional)</label>
        <textarea id="terms" name="terms" placeholder="mis. Berlaku untuk ukuran reguler">{{ old('terms') }}</textarea>
        <label for="image">Gambar Produk (opsional)</label>
        <input type="file" id="image" name="image" accept="image/*">
        <button type="submit" class="btn mt">Tambah Hadiah</button>
    </form>

    {{-- Daftar hadiah --}}
    <h2 style="margin:18px 4px 10px">Daftar Hadiah</h2>
    @forelse($rewards as $reward)
        <div class="card">
            <form action="{{ route('owner.program.reward.update', $reward) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="rwd" style="padding:0 0 8px">
                    @if($reward->image_url)
                        <img src="{{ $reward->image_url }}" alt="">
                    @else
                        <div class="ph">🎁</div>
                    @endif
                    <div class="info">
                        <b>{{ $reward->name }}</b>
                        <span class="muted">Stempel ke-{{ $reward->milestone }}</span>
                    </div>
                </div>
                <label>Nama Hadiah</label>
                <input type="text" name="name" value="{{ $reward->name }}" required>
                <label>Diberikan pada stempel ke-</label>
                <input type="number" name="milestone" min="1" max="{{ $program->card_size }}" value="{{ $reward->milestone }}" required>
                <label>Ketentuan</label>
                <textarea name="terms">{{ $reward->terms }}</textarea>
                <label>Ganti Gambar</label>
                <input type="file" name="image" accept="image/*">
                <label style="display:flex; align-items:center; gap:8px; color:var(--text)">
                    <input type="checkbox" name="is_active" value="1" {{ $reward->is_active ? 'checked' : '' }} style="width:auto"> Aktif
                </label>
                <button type="submit" class="btn sm mt">Simpan</button>
            </form>
            <form action="{{ route('owner.program.reward.destroy', $reward) }}" method="POST"
                  onsubmit="return confirm('Hapus hadiah {{ $reward->name }}?');" style="margin-top:8px">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn sm danger">Hapus</button>
            </form>
        </div>
    @empty
        <p class="muted">Belum ada hadiah. Tambahkan minimal satu.</p>
    @endforelse

    <a href="{{ route('owner.settings') }}" class="btn secondary">← Kembali ke Atur</a>
@endsection
