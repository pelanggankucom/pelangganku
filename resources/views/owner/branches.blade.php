@extends('layouts.app')
@section('title', 'Outlet & Pegawai')

@section('content')
<style>
    .outlet { border:1.5px solid var(--line); border-radius:20px; padding:18px; background:#fff; margin-bottom:16px; box-shadow:var(--shadow); }
    .outlet .nm { font-size:21px; font-weight:800; letter-spacing:-.4px; margin-bottom:8px; }
    .outlet .ln { font-size:14px; color:var(--text); margin:4px 0; }
    .outlet .ln .k { color:var(--muted); }
    .outlet .foot { display:flex; align-items:center; justify-content:space-between; margin-top:12px; }
    .outlet .kasir-count { font-size:14px; font-weight:700; color:var(--blue); }
    .outlet .acts { display:flex; gap:7px; }
    .ibtn { padding:9px 14px; font-size:13px; border:1.5px solid var(--line); background:#fff; border-radius:11px; cursor:pointer; font-weight:600; color:var(--text); }
    .ibtn.del { border-color:#F4C2CB; color:var(--danger); }
    .editbox { background:var(--bg); border-radius:14px; padding:15px; margin-top:14px; display:none; }
    .editbox.open { display:block; }
    .toggle-row { display:flex; align-items:center; gap:10px; margin-top:12px; font-size:14px; color:var(--text); font-weight:600; }
    .toggle-row input { width:auto; }
    .subsec { font-size:13px; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; margin:18px 0 10px; }
    .kasir-line { display:flex; align-items:center; gap:11px; padding:9px 0; border-top:1px solid var(--line); }
    .kasir-line:first-of-type { border-top:none; }
    .kasir-line .av { width:36px; height:36px; border-radius:50%; background:var(--grad-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; flex:none; }
    .kasir-line .info { flex:1; min-width:0; }
    .kasir-line .info b { display:block; font-size:14px; }
    .kasir-line .info span { font-size:12px; color:var(--muted); }
    .note { font-size:12.5px; color:var(--muted); text-align:center; margin:6px 0 16px; }
    .note b { color:var(--ok); }
    .add-outlet { display:block; width:100%; padding:18px; border:1.5px dashed var(--blue-l); border-radius:18px; background:#F4F8FF; color:var(--blue); font-weight:700; font-size:15px; text-align:center; cursor:pointer; }
</style>

<h1>Outlet Cabang &amp; Pegawai</h1>
<p class="sub">Atur jumlah outlet dan pegawai kasir pada setiap outlet.</p>

@forelse($branches as $branch)
    @php $kasirs = $cashiersByBranch[$branch->id] ?? collect(); @endphp
    <div class="outlet">
        <div class="nm">{{ $branch->name }}</div>
        <div class="ln"><span class="k">Alamat:</span> {{ $branch->address ?: '-' }}</div>
        <div class="ln"><span class="k">Telp:</span> {{ $branch->phone ?: '-' }}</div>
        <div class="foot">
            <div class="kasir-count">👥 {{ $kasirs->count() }} Kasir @unless($branch->is_active)<span class="muted">· nonaktif</span>@endunless</div>
            <div class="acts">
                <button type="button" class="ibtn" onclick="document.getElementById('edit-{{ $branch->id }}').classList.toggle('open')">Edit</button>
                <form action="{{ route('owner.branches.destroy', $branch) }}" method="POST"
                      onsubmit="return confirm('Hapus outlet {{ $branch->name }}? Kasir di outlet ini ikut terhapus.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="ibtn del">Hapus</button>
                </form>
            </div>
        </div>

        {{-- Edit outlet + kelola kasir --}}
        <div class="editbox" id="edit-{{ $branch->id }}">
            <form action="{{ route('owner.branches.update', $branch) }}" method="POST">
                @csrf @method('PUT')
                <label>Nama Outlet</label>
                <input type="text" name="name" value="{{ $branch->name }}" required>
                <label>Alamat</label>
                <input type="text" name="address" value="{{ $branch->address }}">
                <label>Telepon</label>
                <input type="text" name="phone" value="{{ $branch->phone }}">
                <label class="toggle-row">
                    <input type="checkbox" name="is_active" value="1" {{ $branch->is_active ? 'checked' : '' }}> Outlet aktif
                </label>
                <button type="submit" class="btn mt">Simpan Outlet</button>
            </form>

            <div class="subsec">Pegawai Kasir di outlet ini</div>
            @forelse($kasirs as $k)
                <div class="kasir-line">
                    <div class="av">{{ strtoupper(substr($k->name, 0, 1)) }}</div>
                    <div class="info">
                        <b>{{ $k->name }}</b>
                        <span>{{ $k->phone ? '0' . substr($k->phone, 2) : '-' }} · PIN aktif</span>
                    </div>
                    <form action="{{ route('owner.cashiers.destroy', $k) }}" method="POST"
                          onsubmit="return confirm('Hapus kasir {{ $k->name }}?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="ibtn del">Hapus</button>
                    </form>
                </div>
            @empty
                <p class="muted">Belum ada kasir di outlet ini.</p>
            @endforelse

            <form action="{{ route('owner.cashiers.store') }}" method="POST" style="margin-top:12px">
                @csrf
                <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                <label>Tambah Kasir — Nama</label>
                <input type="text" name="name" required placeholder="mis. Andi">
                <label>No HP (untuk login)</label>
                <input type="tel" name="phone" required placeholder="08xxxxxxxxxx">
                <label>PIN 4 Angka</label>
                <input type="text" name="pin" inputmode="numeric" maxlength="4" minlength="4" pattern="[0-9]{4}" required placeholder="1234">
                <button type="submit" class="btn gold mt">+ Tambah Kasir ke {{ $branch->name }}</button>
            </form>
        </div>
    </div>
@empty
    <p class="muted">Belum ada outlet. Tambahkan outlet pertama di bawah.</p>
@endforelse

<p class="note">Semua perubahan <b>otomatis tersimpan</b>.</p>

{{-- Tambah outlet baru --}}
<div onclick="document.getElementById('addOutlet').classList.toggle('open')" class="add-outlet">+ Tambah Outlet Cabang &amp; Pegawai</div>
<div class="editbox" id="addOutlet" style="margin-top:14px">
    <form action="{{ route('owner.branches.store') }}" method="POST">
        @csrf
        <label>Nama Outlet</label>
        <input type="text" name="name" value="{{ old('name') }}" required placeholder="mis. Cabang Mall">
        <label>Alamat</label>
        <input type="text" name="address" value="{{ old('address') }}">
        <label>Telepon</label>
        <input type="text" name="phone" value="{{ old('phone') }}">
        <button type="submit" class="btn mt">Simpan Outlet</button>
        <p class="muted" style="margin-top:10px;text-align:center">Kasir ditambahkan lewat tombol <b>Edit</b> setelah outlet dibuat.</p>
    </form>
</div>

<a href="{{ route('owner.settings') }}" class="btn secondary" style="margin-top:8px">← Kembali ke Atur</a>

<script>
    @if($errors->any() && old('name') && !old('email'))
        document.getElementById('addOutlet').classList.add('open');
    @endif
</script>
@endsection
