@extends('layouts.app')
@section('title', 'Laporan Keuangan')

@section('content')
<style>
    .periode { display:flex; gap:7px; margin-bottom:10px; flex-wrap:wrap; }
    .periode label { flex:1; min-width:70px; }
    .periode input { position:absolute; opacity:0; pointer-events:none; }
    .periode span { display:block; text-align:center; padding:10px 4px; border-radius:13px; font-size:12px; font-weight:700; background:#fff; border:1.5px solid var(--line); color:var(--muted); cursor:pointer; }
    .periode input:checked + span { background:var(--grad-blue); color:#fff; border-color:transparent; box-shadow:0 4px 12px rgba(13,71,161,.22); }
    .pdates { margin-bottom:12px; }
    .pdates .two { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .pdates label { margin-top:0; margin-bottom:5px; display:block; }
    .stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px; }
    .stat-card { background:#fff; border:1.5px solid var(--line); border-radius:18px; padding:16px; box-shadow:var(--shadow); }
    .stat-card .n { font-size:20px; font-weight:800; letter-spacing:-.5px; line-height:1.2; }
    .stat-card .l { font-size:11.5px; color:var(--muted); font-weight:600; margin-top:6px; }
    .stat-card.profit { background:var(--grad-blue); border:none; }
    .stat-card.profit .n { color:var(--gold-l); }
    .stat-card.profit .l { color:rgba(255,255,255,.75); }
    .section-head { font-size:15px; font-weight:800; margin:18px 0 8px; display:flex; justify-content:space-between; align-items:center; }
    .entry-row { display:flex; align-items:center; gap:10px; padding:12px 16px; border-bottom:1px solid var(--line); }
    .entry-row:last-child { border-bottom:none; }
    .entry-info { flex:1; min-width:0; }
    .entry-info .desc { font-size:14px; font-weight:600; }
    .entry-info .date { font-size:12px; color:var(--muted); margin-top:1px; }
    .entry-amount { font-size:14px; font-weight:800; min-width:90px; text-align:right; }
    .empty-sec { text-align:center; padding:24px; color:var(--muted); font-size:13px; font-weight:600; }
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:80; align-items:flex-end; justify-content:center; }
    .modal-overlay.open { display:flex; }
</style>

<div style="margin-bottom:18px; display:flex; justify-content:space-between; align-items:center;">
    <div>
        <div style="font-size:18px; font-weight:800; letter-spacing:-.4px;">Laporan Keuangan</div>
        <div style="font-size:13px; color:var(--muted);">{{ $merchant->name }}</div>
    </div>
    @php
        $exportUrl = route('owner.laporan.export', array_filter(['periode' => $period, 'dari' => $dari, 'sampai' => $sampai]));
    @endphp
    <a href="{{ $exportUrl }}" style="padding:10px 16px; background:#0D47A1; color:#fff; border-radius:12px; font-size:13px; font-weight:700; text-decoration:none; white-space:nowrap; display:inline-block;">
        📥 Export
    </a>
</div>

{{-- Filter periode --}}
<form method="GET" id="periodeForm">
    <div class="periode">
        <label><input type="radio" name="periode" value="bulan" {{ $period === 'bulan' ? 'checked' : '' }} onchange="this.form.submit()"><span>Bulan ini</span></label>
        <label><input type="radio" name="periode" value="hari" {{ $period === 'hari' ? 'checked' : '' }} onchange="this.form.submit()"><span>Hari ini</span></label>
        <label><input type="radio" name="periode" value="minggu" {{ $period === 'minggu' ? 'checked' : '' }} onchange="this.form.submit()"><span>Minggu ini</span></label>
        <label><input type="radio" name="periode" value="kustom" {{ $period === 'kustom' ? 'checked' : '' }} onclick="document.getElementById('pdates').style.display='block'"><span>Kustom</span></label>
    </div>
    <div class="pdates" id="pdates" style="display:{{ $period === 'kustom' ? 'block' : 'none' }}">
        <div class="two">
            <div><label>Dari</label><input type="date" name="dari" value="{{ $dari }}"></div>
            <div><label>Sampai</label><input type="date" name="sampai" value="{{ $sampai }}"></div>
        </div>
        <button type="submit" class="btn" style="margin-top:10px">Terapkan</button>
    </div>
</form>
<p style="font-size:13px; color:var(--muted); font-weight:600; margin:0 2px 14px;">Menampilkan data <b style="color:var(--blue)">{{ $periodLabel }}</b></p>

{{-- Summary cards --}}
<div class="stat-grid">
    <div class="stat-card">
        <div class="n" style="color:var(--ok)">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
        <div class="l">Total Pemasukan</div>
    </div>
    <div class="stat-card">
        <div class="n" style="color:var(--danger)">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
        <div class="l">Total Pengeluaran</div>
    </div>
</div>
<div class="stat-card profit" style="margin-bottom:18px; border-radius:18px; padding:18px;">
    <div class="l" style="margin-bottom:4px;">Laba Bersih</div>
    <div class="n" style="font-size:28px;">Rp {{ number_format($netProfit, 0, ',', '.') }}</div>
    @if($posIncome > 0)
    <div style="font-size:12px; color:rgba(255,255,255,.65); margin-top:6px;">
        Termasuk Rp {{ number_format($posIncome, 0, ',', '.') }} dari POS
    </div>
    @endif
</div>

{{-- Pengeluaran --}}
<div class="section-head">
    <span>Pengeluaran</span>
    <button onclick="openModal('expense')" style="font-size:12px; font-weight:700; color:var(--danger); background:#FFF0F0; border:1.5px solid #FFCDD2; border-radius:10px; padding:6px 12px; cursor:pointer;">+ Tambah</button>
</div>
<div class="card" style="padding:0; overflow:hidden; margin-bottom:8px;">
    @forelse($expenseEntries as $entry)
    <div class="entry-row">
        <div class="entry-info">
            <div class="desc">{{ $entry->description }}</div>
            <div class="date">{{ $entry->date->format('d M Y') }}</div>
        </div>
        <div class="entry-amount" style="color:var(--danger)">- Rp {{ number_format($entry->amount, 0, ',', '.') }}</div>
        <form method="POST" action="{{ route('owner.laporan.entry.destroy', $entry) }}" onsubmit="return confirm('Hapus item ini?')">
            @csrf @method('DELETE')
            <input type="hidden" name="_periode" value="{{ $period }}">
            <input type="hidden" name="_dari" value="{{ $dari }}">
            <input type="hidden" name="_sampai" value="{{ $sampai }}">
            <button type="submit" style="background:none; border:none; color:var(--muted); font-size:18px; cursor:pointer; line-height:1; padding:4px;">✕</button>
        </form>
    </div>
    @empty
    <div class="empty-sec">Belum ada pengeluaran.</div>
    @endforelse
</div>

{{-- Pemasukan --}}
<div class="section-head">
    <span>Pemasukan</span>
    <button onclick="openModal('income')" style="font-size:12px; font-weight:700; color:#1B5E20; background:#F0FFF4; border:1.5px solid #A5D6A7; border-radius:10px; padding:6px 12px; cursor:pointer;">+ Tambah</button>
</div>

@if($posIncome > 0)
<a href="{{ route('owner.pos.history') }}" class="card" style="padding:12px 16px; margin-bottom:8px; background:#F8FFFC; border-color:#A7EFC5; display:flex; justify-content:space-between; align-items:center; text-decoration:none;">
    <div>
        <div style="font-size:14px; font-weight:700;">Pendapatan POS</div>
        <div style="font-size:12px; color:var(--muted);">Otomatis dari transaksi kasir · Lihat riwayat →</div>
    </div>
    <div style="font-size:14px; font-weight:800; color:var(--ok);">+ Rp {{ number_format($posIncome, 0, ',', '.') }}</div>
</a>
@endif

<div class="card" style="padding:0; overflow:hidden; margin-bottom:18px;">
    @forelse($incomeEntries as $entry)
    <div class="entry-row">
        <div class="entry-info">
            <div class="desc">{{ $entry->description }}</div>
            <div class="date">{{ $entry->date->format('d M Y') }}</div>
        </div>
        <div class="entry-amount" style="color:var(--ok)">+ Rp {{ number_format($entry->amount, 0, ',', '.') }}</div>
        <form method="POST" action="{{ route('owner.laporan.entry.destroy', $entry) }}" onsubmit="return confirm('Hapus item ini?')">
            @csrf @method('DELETE')
            <input type="hidden" name="_periode" value="{{ $period }}">
            <input type="hidden" name="_dari" value="{{ $dari }}">
            <input type="hidden" name="_sampai" value="{{ $sampai }}">
            <button type="submit" style="background:none; border:none; color:var(--muted); font-size:18px; cursor:pointer; line-height:1; padding:4px;">✕</button>
        </form>
    </div>
    @empty
    <div class="empty-sec">Belum ada pemasukan manual.</div>
    @endforelse
</div>

<a href="{{ route('owner.dashboard') }}"
   style="display:flex; align-items:center; justify-content:center; gap:8px;
          width:100%; padding:16px; border-radius:16px;
          background:#fff; border:1.5px solid var(--line);
          font-size:15px; font-weight:700; color:var(--navy); text-decoration:none;">
    ← Kembali ke Dashboard
</a>
<div style="height:24px;"></div>

{{-- Modal --}}
<div class="modal-overlay" id="entryModal" onclick="if(event.target===this)closeModal()">
    <div style="background:#fff; border-radius:24px 24px 0 0; padding:20px 20px calc(32px + env(safe-area-inset-bottom)); width:100%; max-width:480px;">
        <div style="width:40px; height:4px; background:var(--line); border-radius:4px; margin:0 auto 18px;"></div>
        <h3 id="modal-title" style="font-size:17px; font-weight:800; margin-bottom:16px;">Tambah</h3>
        <form action="{{ route('owner.laporan.entry.store') }}" method="POST">
            @csrf
            <input type="hidden" name="type" id="entry-type" value="expense">
            <input type="hidden" name="_periode" value="{{ $period }}">
            <input type="hidden" name="_dari" value="{{ $dari }}">
            <input type="hidden" name="_sampai" value="{{ $sampai }}">
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div>
                    <label style="font-size:13px; font-weight:700; color:var(--muted); display:block; margin-bottom:3px;">Keterangan</label>
                    <input type="text" name="description" placeholder="Contoh: Beli bahan baku…" required maxlength="200"
                           style="width:100%; padding:11px 14px; border:1.5px solid var(--line); border-radius:12px; font-size:14px; font-family:inherit;">
                </div>
                <div>
                    <label style="font-size:13px; font-weight:700; color:var(--muted); display:block; margin-bottom:3px;">Jumlah (Rp)</label>
                    <input type="number" name="amount" placeholder="50000" min="1" required
                           style="width:100%; padding:11px 14px; border:1.5px solid var(--line); border-radius:12px; font-size:14px; font-family:inherit;">
                </div>
                <div>
                    <label style="font-size:13px; font-weight:700; color:var(--muted); display:block; margin-bottom:3px;">Tanggal</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" required
                           style="width:100%; padding:11px 14px; border:1.5px solid var(--line); border-radius:12px; font-size:14px; font-family:inherit;">
                </div>
            </div>
            <div style="display:flex; gap:10px; margin-top:16px;">
                <button type="button" onclick="closeModal()" class="btn muted" style="flex:1; justify-content:center;">Batal</button>
                <button type="submit" id="modal-submit" class="btn primary" style="flex:1; justify-content:center;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(type) {
    document.getElementById('entry-type').value = type;
    var isExpense = type === 'expense';
    document.getElementById('modal-title').textContent = isExpense ? '💸 Tambah Pengeluaran' : '💰 Tambah Pemasukan';
    document.getElementById('modal-submit').style.background = isExpense ? '#C62828' : '#1B5E20';
    document.getElementById('entryModal').classList.add('open');
}
function closeModal() {
    document.getElementById('entryModal').classList.remove('open');
}
</script>
@endsection
