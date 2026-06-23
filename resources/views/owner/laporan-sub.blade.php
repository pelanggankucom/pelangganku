@extends('layouts.app')
@section('title', 'Laporan Keuangan')

@section('content')
<style>
    .fin-hero { background:linear-gradient(135deg,#1A237E,#283593); color:#fff; border-radius:24px; padding:24px 22px 22px; margin-bottom:18px; position:relative; overflow:hidden; box-shadow:0 12px 30px rgba(10,20,80,.30); }
    .fin-hero::after { content:""; position:absolute; top:-60px; right:-40px; width:180px; height:180px; border-radius:50%; background:radial-gradient(circle,rgba(246,185,49,.35),transparent 70%); }
    .fin-hero .label { font-size:13px; opacity:.88; font-weight:500; position:relative; z-index:1; }
    .fin-hero .big { font-size:26px; font-weight:800; margin:4px 0 6px; letter-spacing:-.5px; position:relative; z-index:1; }
    .badge-active { display:inline-flex; align-items:center; gap:6px; background:rgba(30,158,90,.25); border:1px solid rgba(30,158,90,.5); color:#6effa8; padding:5px 12px; border-radius:999px; font-size:12px; font-weight:700; }
    .badge-inactive { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.3); color:rgba(255,255,255,.75); padding:5px 12px; border-radius:999px; font-size:12px; font-weight:700; }
    .badge-pending { display:inline-flex; align-items:center; gap:6px; background:rgba(246,185,49,.2); border:1px solid rgba(246,185,49,.5); color:var(--gold-l); padding:5px 12px; border-radius:999px; font-size:12px; font-weight:700; }
    .info-card { background:#fff; border:1.5px solid var(--line); border-radius:18px; margin-bottom:14px; box-shadow:var(--shadow); overflow:hidden; }
    .info-card .row { display:flex; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid var(--line); }
    .info-card .row:last-child { border-bottom:none; }
    .info-card .lbl { font-size:14px; color:var(--text); font-weight:500; }
    .info-card .val { font-size:14px; font-weight:700; }
    .feature-list { list-style:none; margin:0 0 14px; }
    .feature-list li { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--line); font-size:14.5px; }
    .feature-list li:last-child { border-bottom:none; }
    .feature-list .ck { color:var(--ok); font-size:16px; font-weight:700; }
    .price-box { background:rgba(255,255,255,.09); border:1px solid rgba(255,255,255,.18); border-radius:16px; padding:18px; margin:16px 0; text-align:center; }
    .price-box .amount { font-size:36px; font-weight:800; letter-spacing:-1px; color:var(--gold-l); }
    .price-box .period { font-size:14px; opacity:.75; }
    .info-box { border-radius:14px; padding:14px 16px; font-size:13.5px; line-height:1.6; }

    /* Modal */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:80; align-items:center; justify-content:center; padding:20px; }
    .modal-overlay.open { display:flex; }
    .modal { background:#fff; border-radius:24px; padding:24px 20px 28px; width:100%; max-width:440px; }
    .modal h3 { font-size:17px; font-weight:800; margin-bottom:16px; }
    .form-col { display:flex; flex-direction:column; gap:12px; }
    .form-col label { font-size:13px; font-weight:700; color:var(--muted); margin-bottom:3px; display:block; }
    .form-col input, .form-col textarea { width:100%; padding:11px 14px; border:1.5px solid var(--line); border-radius:12px; font-size:14px; font-family:inherit; color:var(--text); background:#fff; }
    .form-col input:focus { outline:none; border-color:var(--blue-l); }
    .btn-row { display:flex; gap:10px; margin-top:16px; }
    .btn-row .btn { flex:1; justify-content:center; }
</style>

<div class="fin-hero">
    <div class="label">Fitur Tambahan</div>
    <div class="big">📊 Laporan Keuangan</div>
    @if($merchant->hasFinanceAccess())
        <span class="badge-active">✓ Aktif</span>
    @elseif($sub && $sub->status === 'pending')
        <span class="badge-pending">⏳ Menunggu Pembayaran</span>
    @else
        <span class="badge-inactive">Belum Aktif</span>
    @endif
</div>

@if(session('success'))<div class="flash ok">{{ session('success') }}</div>@endif
@if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
@if(session('info'))<div class="flash" style="background:#E8F4FF;border:1px solid #90CAF9;color:#1565C0;">{{ session('info') }}</div>@endif

@if($merchant->hasFinanceAccess())

    {{-- Info akses --}}
    @if($merchant->finance_granted_by_admin)
    <div class="info-card">
        <div class="row">
            <div class="lbl">Akses Laporan</div>
            <div class="val" style="color:var(--ok)">✓ Diaktifkan oleh Admin</div>
        </div>
        <div class="row">
            <div class="lbl">Berlaku</div>
            <div class="val">{{ $merchant->finance_admin_expires_at ? $merchant->finance_admin_expires_at->format('d M Y') : 'Selamanya' }}</div>
        </div>
    </div>
    @elseif($sub && $sub->isActive())
    <div class="info-card">
        <div class="row">
            <div class="lbl">Masa aktif berakhir</div>
            <div class="val">{{ $sub->expires_at->format('d M Y') }}</div>
        </div>
        <div class="row">
            <div class="lbl">Sisa hari</div>
            <div class="val" style="color:var(--blue)">{{ $sub->daysLeft() }} hari</div>
        </div>
    </div>
    @endif

    <div class="info-box" style="background:#F0FFF4; border:1px solid #A7EFC5; color:#1E5C38; margin-bottom:14px;">
        📊 Lihat laporan lengkap pada menu <b>Laporan Keuangan</b> di dashboard.
    </div>

    <button onclick="openModal('expense')" class="btn" style="width:100%; justify-content:center; margin-bottom:10px; padding:14px; font-size:15px; background:#FFF0F0; color:#C62828; border:1.5px solid #FFCDD2;">
        ➕ Tambah Pengeluaran
    </button>
    <button onclick="openModal('income')" class="btn" style="width:100%; justify-content:center; padding:14px; font-size:15px; background:#F0FFF4; color:#1B5E20; border:1.5px solid #A5D6A7;">
        ➕ Tambah Pemasukan
    </button>

    @if(!$merchant->finance_granted_by_admin)
    <form action="{{ route('owner.laporan.subscribe') }}" method="POST" style="margin-top:12px">
        @csrf
        <button type="submit" class="btn secondary" style="width:100%; justify-content:center;">Perpanjang (Rp 25.000)</button>
    </form>
    @endif

@elseif($sub && $sub->status === 'pending')

    <div class="info-box" style="background:#FFF9E6; border:1px solid #FFE082; color:#7A5800; margin-bottom:16px;">
        ⏳ Pembayaran sedang menunggu konfirmasi. Laporan akan aktif otomatis setelah pembayaran berhasil.
    </div>
    @if($sub->doku_payment_url)
        <a href="{{ $sub->doku_payment_url }}" class="btn gold" style="margin-bottom:10px">Lanjutkan Pembayaran →</a>
    @endif
    <form action="{{ route('owner.laporan.subscribe') }}" method="POST">
        @csrf
        <button type="submit" class="btn secondary">Buat Pembayaran Baru</button>
    </form>

@else

    <div class="card" style="margin-bottom:14px">
        <h2 style="margin-bottom:4px">Apa itu Laporan Keuangan?</h2>
        <p class="sub" style="margin-bottom:14px">Pantau pemasukan & pengeluaran toko kamu dalam satu halaman.</p>

        <ul class="feature-list">
            <li><span class="ck">✓</span> Total pendapatan dari POS (otomatis)</li>
            <li><span class="ck">✓</span> Input pemasukan di luar POS</li>
            <li><span class="ck">✓</span> Input pengeluaran operasional</li>
            <li><span class="ck">✓</span> Hitung laba bersih otomatis</li>
            <li><span class="ck">✓</span> Filter per hari, minggu, bulan, atau kustom</li>
        </ul>

        <div class="fin-hero" style="margin:0 0 14px; border-radius:16px; padding:16px 18px;">
            <div class="price-box" style="margin:0">
                <div class="amount">Rp 25.000</div>
                <div class="period">per bulan · bayar via DOKU</div>
            </div>
        </div>

        <form action="{{ route('owner.laporan.subscribe') }}" method="POST">
            @csrf
            <button type="submit" class="btn gold">Aktifkan Laporan Keuangan →</button>
        </form>
        <p class="muted" style="margin-top:12px;text-align:center">Diarahkan ke halaman pembayaran DOKU yang aman.</p>
    </div>

@endif

<a href="{{ route('owner.settings') }}"
   style="display:flex; align-items:center; justify-content:center; gap:8px;
          width:100%; padding:16px; margin-top:8px; border-radius:16px;
          background:#fff; border:1.5px solid var(--line);
          font-size:15px; font-weight:700; color:var(--navy); text-decoration:none;">
    ← Kembali ke Atur
</a>
<div style="height:24px;"></div>

{{-- Modal Pengeluaran / Pemasukan --}}
<div class="modal-overlay" id="entryModal">
    <div class="modal">
        <h3 id="modal-title">Tambah</h3>
        <form action="{{ route('owner.laporan.entry.store') }}" method="POST">
            @csrf
            <input type="hidden" name="type" id="entry-type" value="expense">
            <div class="form-col">
                <div>
                    <label>Keterangan</label>
                    <input type="text" name="description" placeholder="Contoh: Beli bahan baku, Gaji karyawan…" required maxlength="200">
                </div>
                <div>
                    <label>Jumlah (Rp)</label>
                    <input type="number" name="amount" placeholder="50000" min="1" required>
                </div>
                <div>
                    <label>Tanggal</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
            <div class="btn-row">
                <button type="button" class="btn muted" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn primary" id="modal-submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(type) {
    document.getElementById('entry-type').value = type;
    var isExpense = type === 'expense';
    document.getElementById('modal-title').textContent = isExpense ? '➕ Tambah Pengeluaran' : '➕ Tambah Pemasukan';
    document.getElementById('modal-submit').style.background = isExpense ? '#C62828' : '#1B5E20';
    document.getElementById('entryModal').classList.add('open');
}
function closeModal() {
    document.getElementById('entryModal').classList.remove('open');
}
document.getElementById('entryModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endsection
