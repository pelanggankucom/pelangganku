@extends('layouts.app')
@section('title', 'Owner · Program, Outlet & Toko')

@section('content')
<style>
    .tabs { display:flex; gap:10px; margin-bottom:16px; overflow-x:auto; }
    .tabs button { flex:0 0 auto; padding:12px 16px; border:none; border-radius:12px; background:var(--line); color:var(--text); font-weight:600; cursor:pointer; font-size:13px; white-space:nowrap; }
    .tabs button.active { background:var(--blue); color:#fff; }
    .tab-content { display:none; }
    .tab-content.active { display:block; }
    .cards-grid { display:grid; gap:14px; }
    .card-item { background:var(--panel); border:1px solid var(--line); border-radius:16px; padding:16px; position:relative; }
    .card-item.active { border-color:var(--blue); background:#fff; box-shadow:0 0 0 2px rgba(13,71,161,.15); }
    .card-title { font-size:16px; font-weight:700; margin-bottom:8px; }
    .card-stat { display:flex; justify-content:space-between; padding:8px 0; font-size:13px; color:var(--muted); border-bottom:1px solid var(--line); }
    .card-stat:last-of-type { border-bottom:none; }
    .card-stat b { color:var(--text); font-weight:700; }
    .card-actions { display:flex; gap:8px; margin-top:12px; }
    .card-actions button, .card-actions a { flex:1; padding:8px; font-size:12px; border:1px solid var(--line); background:#fff; border-radius:10px; cursor:pointer; text-align:center; text-decoration:none; color:var(--text); }
    .card-actions button:active, .card-actions a:active { background:var(--bg); }
    .card-actions .btn-danger { border-color:var(--danger); color:var(--danger); }
    .rewards-list { margin-top:12px; }
    .reward-item { display:flex; gap:10px; align-items:center; padding:10px; background:var(--bg); border-radius:10px; margin-bottom:6px; }
    .reward-item .mi { width:36px; height:36px; border-radius:8px; background:linear-gradient(135deg,var(--gold),var(--gold-d)); color:#3a2c00; display:flex; align-items:center; justify-content:center; flex:none; }
    .reward-item .info { flex:1; }
    .reward-item .info b { display:block; font-size:13px; }
    .reward-item .info span { font-size:12px; color:var(--muted); }
    .btn-add { display:flex; align-items:center; justify-content:center; gap:6px; margin-top:14px; padding:14px; background:linear-gradient(135deg,var(--blue),var(--blue-l)); color:#fff; border:none; border-radius:14px; font-weight:600; font-size:14px; cursor:pointer; width:100%; }
    .hero { background:linear-gradient(135deg,var(--blue) 0%,var(--blue-l) 100%); color:#fff; border-radius:20px; padding:20px; margin-bottom:16px; }
    .hero .label { font-size:13px; opacity:.85; }
    .hero .big { font-size:28px; font-weight:800; margin:4px 0; }
    .member-item { display:flex; align-items:center; gap:12px; padding:12px; background:var(--panel); border:1px solid var(--line); border-radius:12px; margin-bottom:10px; }
    .member-item .avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,var(--blue),var(--blue-l)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; flex:none; }
    .member-item .info { flex:1; }
    .member-item .info b { display:block; font-size:14px; }
    .member-item .info span { font-size:12px; color:var(--muted); }
    .member-item .actions button { padding:6px 10px; font-size:11px; border:1px solid var(--line); background:#fff; border-radius:8px; cursor:pointer; }
    .member-item .actions .btn-danger { border-color:var(--danger); color:var(--danger); }
    .sec-h { font-size:15px; font-weight:700; margin:16px 0 12px; display:flex; align-items:center; gap:8px; }
</style>

<div class="hero">
    <div class="label">Program, Outlet & Toko</div>
    <div class="big">{{ $merchant->name }}</div>
</div>

<div class="tabs">
    <button class="active" onclick="switchTab('program')">📋 Program</button>
    <button onclick="switchTab('outlets')">📍 Outlet</button>
    <button onclick="switchTab('store')">🏪 Toko</button>
</div>

{{-- Tab Program --}}
<div id="program" class="tab-content active">
    <div class="cards-grid">
        @if($program)
        <div class="card-item active">
            <div class="card-title">{{ $program->name }}</div>
            <div class="card-stat">
                <span>Per Kartu</span>
                <b>{{ $program->card_size }} stempel</b>
            </div>
            <div class="card-stat">
                <span>Hadiah Terdaftar</span>
                <b>{{ $program->rewards->count() }}</b>
            </div>
            <div class="rewards-list">
                @forelse($program->rewards as $r)
                    <div class="reward-item">
                        <div class="mi">🎁</div>
                        <div class="info">
                            <b>{{ $r->name }}</b>
                            <span>Milestone stempel ke-{{ $r->milestone }}</span>
                        </div>
                    </div>
                @empty
                    <p class="muted" style="font-size:13px;">Belum ada hadiah terdaftar.</p>
                @endforelse
            </div>
            <div class="card-actions">
                <a href="{{ route('owner.program') }}" class="btn-edit">Edit Program</a>
            </div>
        </div>
        @else
        <p class="muted">Belum ada program loyalitas.</p>
        @endif
    </div>
</div>

{{-- Tab Outlets --}}
<div id="outlets" class="tab-content">
    <div class="cards-grid">
        @forelse($branches as $b)
        <div class="card-item {{ $b->is_active ? 'active' : '' }}">
            <div class="card-title">{{ $b->name }}</div>
            <div class="card-stat">
                <span>Status</span>
                <b>{{ $b->is_active ? 'Aktif' : 'Nonaktif' }}</b>
            </div>
            <div class="card-stat">
                <span>Alamat</span>
                <b style="text-align:right;">{{ $b->address ?? '-' }}</b>
            </div>
            <div class="card-actions">
                <a href="{{ route('owner.branches.update', $b->id) }}" class="btn-edit">Edit</a>
                <form action="{{ route('owner.branches.destroy', $b->id) }}" method="POST" style="flex:1;">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger" onclick="return confirm('Yakin hapus outlet ini?')">Hapus</button>
                </form>
            </div>
        </div>
        @empty
        <p class="muted">Belum ada outlet.</p>
        @endforelse
    </div>

    <form action="{{ route('owner.branches.store') }}" method="POST" class="mt">
        @csrf
        <button type="submit" class="btn-add">+ Tambah Outlet Baru</button>
    </form>
</div>

{{-- Tab Toko (Profile + Kelola Kasir) --}}
<div id="store" class="tab-content">
    <div class="sec-h">Profil Toko</div>
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

    <div class="sec-h">Kelola Kasir</div>
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

        <div class="sec-h" style="margin-top:20px; margin-bottom:12px;">Tambah Kasir Baru</div>
        <form action="{{ route('owner.settings.cashier.store') }}" method="POST">
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
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tabs button').forEach(el => el.classList.remove('active'));
    document.getElementById(tab).classList.add('active');
    event.target.classList.add('active');
}
</script>

@endsection
