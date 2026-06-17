@extends('layouts.app')
@section('title', 'Program & Hadiah')

@section('content')
<style>
    .preview { border:1.5px solid var(--line); border-radius:20px; padding:16px; background:#fff; margin-bottom:16px; box-shadow:var(--shadow); }
    .preview .cap { font-size:12.5px; color:var(--muted); font-weight:600; text-align:center; margin-bottom:12px; }
    .pstamps { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; }
    .pstamp { aspect-ratio:1; border-radius:50%; border:2px solid var(--line); display:flex; align-items:center; justify-content:center; font-size:22px; color:#C3CEDF; background:#FAFCFF; }
    .pstamp.gift { border-color:var(--gold); background:#FFF8E6; }
    .toggle-row { display:flex; align-items:center; gap:10px; margin-top:14px; font-size:14px; color:var(--text); font-weight:600; }
    .toggle-row input { width:auto; }
    .saved { font-size:12px; color:var(--ok); font-weight:700; opacity:0; transition:opacity .3s; }
    .saved.show { opacity:1; }
    .rwrow { display:flex; align-items:center; gap:13px; padding:14px 0; border-top:1px solid var(--line); }
    .rwrow:first-of-type { border-top:none; }
    .rwrow .thumb { width:48px; height:48px; border-radius:13px; background:#FFF1C9; display:flex; align-items:center; justify-content:center; font-size:24px; flex:none; overflow:hidden; }
    .rwrow .thumb img { width:100%; height:100%; object-fit:cover; }
    .rwrow .info { flex:1; min-width:0; }
    .rwrow .info b { display:block; font-size:15px; font-weight:700; }
    .rwrow .info span { font-size:12.5px; color:var(--muted); }
    .rwrow .acts { display:flex; gap:7px; }
    .ibtn { padding:8px 13px; font-size:13px; border:1.5px solid var(--line); background:#fff; border-radius:11px; cursor:pointer; font-weight:600; color:var(--text); text-decoration:none; }
    .ibtn.del { border-color:#F4C2CB; color:var(--danger); }
    .editbox { background:var(--bg); border-radius:14px; padding:14px; margin-top:10px; display:none; }
    .editbox.open { display:block; }
    .note { font-size:12.5px; color:var(--muted); text-align:center; margin-top:14px; }
    .note b { color:var(--ok); }
</style>

<h1>Program &amp; Hadiah</h1>
<p class="sub">Atur jumlah stempel per kartu dan posisi hadiah.</p>

{{-- Preview kartu --}}
@php $milestones = $rewards->pluck('milestone')->all(); @endphp
<div class="preview">
    <div class="cap">Sampel tampilan kartu stempel yang akan muncul di pelanggan</div>
    <div class="pstamps">
        @for($i = 1; $i <= $program->card_size; $i++)
            <div class="pstamp {{ in_array($i, $milestones) ? 'gift' : '' }}">{{ in_array($i, $milestones) ? '🎁' : '☆' }}</div>
        @endfor
    </div>
</div>

{{-- Ukuran kartu (auto-simpan) --}}
<form action="{{ route('owner.program.update') }}" method="POST" class="card" id="cardForm">
    @csrf
    <div style="display:flex;align-items:center;justify-content:space-between">
        <h2 style="margin:0">Ukuran Kartu</h2>
        <span class="saved" id="savedTag">✓ Tersimpan</span>
    </div>
    <label for="card_size">Jumlah stempel dalam 1 kartu</label>
    <input type="number" id="card_size" name="card_size" value="{{ $program->card_size }}" min="1" max="100" required
           onchange="document.getElementById('cardForm').submit()">
    <label class="toggle-row">
        <input type="checkbox" name="carry_over" value="1" {{ $program->carry_over ? 'checked' : '' }}
               onchange="document.getElementById('cardForm').submit()">
        Sisa stempel dibawa ke kartu berikutnya
    </label>
</form>

{{-- Daftar hadiah --}}
<div class="card">
    <h2>Daftar Hadiah</h2>
    @forelse($rewards as $reward)
        <div class="rwrow">
            <div class="thumb">
                @if($reward->image_url)<img src="{{ $reward->image_url }}" alt="">@else🎁@endif
            </div>
            <div class="info">
                <b>{{ $reward->name }}</b>
                <span>Stempel ke-{{ $reward->milestone }}</span>
            </div>
            <div class="acts">
                <button type="button" class="ibtn" onclick="document.getElementById('edit-{{ $reward->id }}').classList.toggle('open')">Edit</button>
                <form action="{{ route('owner.program.reward.destroy', $reward) }}" method="POST"
                      onsubmit="return confirm('Hapus hadiah {{ $reward->name }}?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="ibtn del">Hapus</button>
                </form>
            </div>
        </div>
        <div class="editbox" id="edit-{{ $reward->id }}">
            <form action="{{ route('owner.program.reward.update', $reward) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <label>Nama Hadiah</label>
                <input type="text" name="name" value="{{ $reward->name }}" required>
                <label>Diberikan pada stempel ke- (1–{{ $program->card_size }})</label>
                <input type="number" name="milestone" min="1" max="{{ $program->card_size }}" value="{{ $reward->milestone }}" required>
                <label>Ganti Gambar (opsional)</label>
                <input type="file" name="image" accept="image/*">
                <label class="toggle-row">
                    <input type="checkbox" name="is_active" value="1" {{ $reward->is_active ? 'checked' : '' }}> Hadiah aktif
                </label>
                <button type="submit" class="btn mt">Simpan Perubahan</button>
            </form>
        </div>
    @empty
        <p class="muted">Belum ada hadiah. Tambahkan minimal satu di bawah.</p>
    @endforelse

    {{-- Tambah hadiah --}}
    <button type="button" class="btn secondary mt" onclick="document.getElementById('addBox').classList.toggle('open')">+ Tambah Hadiah</button>
    <div class="editbox" id="addBox">
        <form action="{{ route('owner.program.reward.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label>Nama Hadiah</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="mis. Kopi Gratis">
            <label>Diberikan pada stempel ke- (1–{{ $program->card_size }})</label>
            <input type="number" name="milestone" min="1" max="{{ $program->card_size }}" value="{{ old('milestone', $program->card_size) }}" required>
            <label>Gambar Hadiah (opsional)</label>
            <input type="file" name="image" accept="image/*">
            <button type="submit" class="btn gold mt">Tambah Hadiah</button>
        </form>
    </div>

    <p class="note">Semua perubahan kartu &amp; hadiah <b>otomatis tersimpan</b>.</p>
</div>

<a href="{{ route('owner.settings') }}" class="btn secondary">← Kembali ke Atur</a>

<script>
    @if(session('success'))
        (function () {
            var t = document.getElementById('savedTag');
            if (t) { t.classList.add('show'); setTimeout(function () { t.classList.remove('show'); }, 2500); }
        })();
    @endif
    @if($errors->any() && old('name'))
        document.getElementById('addBox').classList.add('open');
    @endif
</script>
@endsection
