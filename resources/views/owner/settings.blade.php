@extends('layouts.app')
@section('title', 'Pengaturan Program')

@section('content')
    <h1>Pengaturan Program Loyalitas</h1>
    <p class="sub">Atur jumlah stempel & hadiah. Berlaku untuk semua kasir.</p>

    <form action="{{ route('owner.settings.update') }}" method="POST" class="card">
        @csrf
        <label for="stamps_per_reward">Jumlah stempel untuk 1 hadiah</label>
        <input type="number" id="stamps_per_reward" name="stamps_per_reward"
               value="{{ old('stamps_per_reward', $program->stamps_per_reward) }}" min="1" max="100" required>

        <label for="reward_name">Nama hadiah</label>
        <input type="text" id="reward_name" name="reward_name"
               value="{{ old('reward_name', $reward?->name ?? '1 Produk Gratis') }}" maxlength="100" required>

        <label style="display:flex; align-items:center; gap:10px; margin-top:16px; color:var(--text)">
            <input type="checkbox" name="carry_over" value="1" {{ $program->carry_over ? 'checked' : '' }} style="width:auto">
            Sisa stempel dibawa setelah tukar hadiah (carry over)
        </label>

        <button type="submit" class="btn mt">Simpan</button>
    </form>

    <a href="{{ route('kasir') }}" class="btn secondary mt">← Kembali ke Kasir</a>
@endsection
