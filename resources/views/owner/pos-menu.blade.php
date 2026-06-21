@extends('layouts.app')
@section('title', 'Menu POS')

@section('content')
<style>
    .section-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
    .section-head h2 { font-size:17px; font-weight:800; }
    .menu-grid { display:flex; flex-direction:column; gap:8px; margin-bottom:20px; }
    .menu-item { background:#fff; border:1px solid var(--line); border-radius:16px; padding:14px 16px; box-shadow:var(--shadow); display:flex; align-items:center; gap:12px; }
    .menu-item .ico { width:42px; height:42px; border-radius:12px; background:var(--bg); display:flex; align-items:center; justify-content:center; font-size:20px; flex:none; }
    .menu-item .info { flex:1; min-width:0; }
    .menu-item .info .nm { font-weight:700; font-size:15px; }
    .menu-item .info .cat { font-size:12px; color:var(--muted); font-weight:500; margin-top:1px; }
    .menu-item .price { font-size:16px; font-weight:800; color:var(--blue); white-space:nowrap; }
    .menu-item .acts { display:flex; gap:6px; flex:none; }
    .badge-off { display:inline-block; font-size:11px; padding:2px 8px; background:#F0F2F7; color:var(--muted); border-radius:999px; font-weight:700; margin-left:6px; }
    .cat-label { font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; margin:14px 0 6px; padding-left:2px; }
    .add-card { background:#fff; border:1.5px dashed var(--line); border-radius:18px; padding:18px; margin-bottom:20px; }
    .add-card h3 { font-size:15px; font-weight:800; margin-bottom:14px; }
    .form-row { display:flex; flex-direction:column; gap:10px; }
    .form-row label { font-size:13px; font-weight:700; color:var(--muted); margin-bottom:2px; display:block; }
    .form-row input, .form-row select { width:100%; padding:11px 14px; border:1.5px solid var(--line); border-radius:12px; font-size:14px; font-family:inherit; color:var(--text); background:#fff; }
    .form-row input:focus, .form-row select:focus { outline:none; border-color:var(--blue-l); box-shadow:0 0 0 3px rgba(30,102,208,.1); }
    .form-row .row2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }

    /* Edit modal */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50; align-items:flex-end; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal { background:#fff; border-radius:24px 24px 0 0; padding:24px 20px 32px; width:100%; max-width:480px; max-height:90vh; overflow-y:auto; }
    .modal h3 { font-size:17px; font-weight:800; margin-bottom:16px; }
    .modal .btn-row { display:flex; gap:10px; margin-top:16px; }
    .modal .btn-row .btn { flex:1; justify-content:center; }
</style>

<div style="margin-bottom:18px;">
    <div style="font-size:18px; font-weight:800; letter-spacing:-.4px;">📋 Menu POS</div>
    <div style="font-size:13px; color:var(--muted);">{{ $merchant->name }}</div>
</div>

@if(session('success'))
    <div class="flash ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="flash err">{{ session('error') }}</div>
@endif

{{-- Form tambah menu --}}
<div class="add-card">
    <h3>+ Tambah Item Menu</h3>
    <form action="{{ route('owner.pos.menu.store') }}" method="POST">
        @csrf
        <div class="form-row">
            <div>
                <label>Nama Item</label>
                <input type="text" name="name" placeholder="Contoh: Kopi Susu, Nasi Goreng…"
                       value="{{ old('name') }}" required maxlength="120">
            </div>
            <div class="row2">
                <div>
                    <label>Kategori <span style="font-weight:400">(opsional)</span></label>
                    <input type="text" name="category" placeholder="Minuman, Makanan…"
                           value="{{ old('category') }}"
                           list="cat-suggestions" maxlength="60">
                    <datalist id="cat-suggestions">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label>Harga (Rp)</label>
                    <input type="number" name="price" placeholder="15000"
                           value="{{ old('price') }}" min="0" max="99999999" required>
                </div>
            </div>
            <button type="submit" class="btn primary" style="width:100%; justify-content:center;">
                Tambah Menu
            </button>
        </div>
    </form>
    @if($errors->any())
        <p style="color:var(--danger); font-size:13px; margin-top:8px;">{{ $errors->first() }}</p>
    @endif
</div>

{{-- Daftar menu --}}
@if($items->isEmpty())
    <div style="text-align:center; padding:32px 20px; color:var(--muted);">
        <div style="font-size:40px; margin-bottom:10px;">🍽️</div>
        <p style="font-weight:600;">Belum ada item menu. Tambahkan di atas!</p>
    </div>
@else
    @php $currentCat = null; @endphp
    @foreach($items as $item)
        @if($item->category !== $currentCat)
            @php $currentCat = $item->category; @endphp
            <div class="cat-label">{{ $currentCat ?: 'Tanpa Kategori' }}</div>
        @endif
        <div class="menu-item">
            <div class="ico">🍽️</div>
            <div class="info">
                <div class="nm">
                    {{ $item->name }}
                    @if(!$item->is_active)<span class="badge-off">Nonaktif</span>@endif
                </div>
                @if($item->category)<div class="cat">{{ $item->category }}</div>@endif
            </div>
            <div class="price">Rp {{ number_format($item->price, 0, ',', '.') }}</div>
            <div class="acts">
                <button type="button" class="btn sm"
                        style="background:#F0F4FF; color:var(--blue); border:1.5px solid #C5D8FF;"
                        onclick="openEdit({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ addslashes($item->category ?? '') }}', {{ $item->price }}, {{ $item->is_active ? 1 : 0 }})">
                    Edit
                </button>
                <form method="POST" action="{{ route('owner.pos.menu.destroy', $item) }}"
                      onsubmit="return confirm('Hapus item ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn sm danger">Hapus</button>
                </form>
            </div>
        </div>
    @endforeach
@endif

<a href="{{ route('owner.pos') }}"
   style="display:flex; align-items:center; justify-content:center; gap:8px;
          width:100%; padding:16px; margin-top:8px; border-radius:16px;
          background:#fff; border:1.5px solid var(--line);
          font-size:15px; font-weight:700; color:var(--navy); text-decoration:none;">
    ← Kembali ke POS
</a>

<div style="height:24px;"></div>

{{-- Edit Modal --}}
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h3>✏️ Edit Item Menu</h3>
        <form id="editForm" method="POST">
            @csrf @method('PUT')
            <div class="form-row">
                <div>
                    <label>Nama Item</label>
                    <input type="text" name="name" id="edit-name" required maxlength="120">
                </div>
                <div class="row2">
                    <div>
                        <label>Kategori</label>
                        <input type="text" name="category" id="edit-category"
                               list="cat-suggestions" maxlength="60">
                    </div>
                    <div>
                        <label>Harga (Rp)</label>
                        <input type="number" name="price" id="edit-price" min="0" required>
                    </div>
                </div>
                <div>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="is_active" id="edit-active" value="1" style="width:auto;">
                        Tampilkan di POS (aktif)
                    </label>
                </div>
            </div>
            <div class="btn-row">
                <button type="button" class="btn muted" onclick="closeEdit()">Batal</button>
                <button type="submit" class="btn primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, name, category, price, isActive) {
    document.getElementById('editForm').action = '/owner/pos/menu/' + id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-category').value = category;
    document.getElementById('edit-price').value = price;
    document.getElementById('edit-active').checked = isActive === 1;
    document.getElementById('editModal').classList.add('open');
}
function closeEdit() {
    document.getElementById('editModal').classList.remove('open');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>
@endsection
