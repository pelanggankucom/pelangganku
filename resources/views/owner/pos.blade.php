@extends('layouts.app')
@section('title', 'POS · pelangganku.com')

@section('content')
<style>
    .pos-hero { background:var(--grad-blue); color:#fff; border-radius:24px; padding:24px 22px 22px; margin-bottom:18px; position:relative; overflow:hidden; box-shadow:0 12px 30px rgba(10,42,92,.30); }
    .pos-hero::after { content:""; position:absolute; top:-60px; right:-40px; width:180px; height:180px; border-radius:50%; background:radial-gradient(circle,rgba(246,185,49,.35),transparent 70%); }
    .pos-hero .label { font-size:13px; opacity:.88; font-weight:500; position:relative; z-index:1; }
    .pos-hero .big { font-size:26px; font-weight:800; margin:4px 0 6px; letter-spacing:-.5px; position:relative; z-index:1; }
    .badge-active { display:inline-flex; align-items:center; gap:6px; background:rgba(30,158,90,.25); border:1px solid rgba(30,158,90,.5); color:#6effa8; padding:5px 12px; border-radius:999px; font-size:12px; font-weight:700; }
    .badge-inactive { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.3); color:rgba(255,255,255,.75); padding:5px 12px; border-radius:999px; font-size:12px; font-weight:700; }
    .badge-pending { display:inline-flex; align-items:center; gap:6px; background:rgba(246,185,49,.2); border:1px solid rgba(246,185,49,.5); color:var(--gold-l); padding:5px 12px; border-radius:999px; font-size:12px; font-weight:700; }
    .price-box { background:rgba(255,255,255,.09); border:1px solid rgba(255,255,255,.18); border-radius:16px; padding:18px; margin:16px 0; text-align:center; }
    .price-box .amount { font-size:36px; font-weight:800; letter-spacing:-1px; color:var(--gold-l); }
    .price-box .period { font-size:14px; opacity:.75; }
    .feature-list { list-style:none; margin:0 0 14px; }
    .feature-list li { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--line); font-size:14.5px; }
    .feature-list li:last-child { border-bottom:none; }
    .feature-list .ck { color:var(--ok); font-size:16px; font-weight:700; }
    .info-box { background:#FFF9E6; border:1px solid #FFE082; border-radius:14px; padding:14px 16px; font-size:13.5px; color:#7A5800; line-height:1.6; }
    .exp-card { background:var(--panel); border:1px solid var(--line); border-radius:18px; padding:18px; margin-bottom:14px; box-shadow:var(--shadow); }
    .exp-card .row { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
    .exp-card .key { font-size:13px; color:var(--muted); }
    .exp-card .val { font-size:15px; font-weight:700; }
    .exp-card .days { font-size:28px; font-weight:800; color:var(--blue); letter-spacing:-1px; }
</style>

<div class="pos-hero">
    <div class="label">Fitur Tambahan</div>
    <div class="big">🖥️ POS Digital</div>
    @if($sub && $sub->isActive())
        <span class="badge-active">✓ Aktif</span>
    @elseif($sub && $sub->status === 'pending')
        <span class="badge-pending">⏳ Menunggu Pembayaran</span>
    @else
        <span class="badge-inactive">Belum Aktif</span>
    @endif
</div>

@if(session('success'))
    <div class="flash ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="flash err">{{ session('error') }}</div>
@endif
@if(session('info'))
    <div class="flash" style="background:#E8F4FF;border:1px solid #90CAF9;color:#1565C0;">{{ session('info') }}</div>
@endif

@if($sub && $sub->isActive())
    {{-- Status aktif --}}
    <div class="exp-card">
        <div class="row">
            <div class="key">Masa aktif berakhir</div>
            <div class="val">{{ $sub->expires_at->format('d M Y') }}</div>
        </div>
        <div class="row">
            <div class="key">Sisa hari</div>
            <div class="days">{{ $sub->daysLeft() }} <span style="font-size:14px;color:var(--muted)">hari</span></div>
        </div>
    </div>

    <a href="{{ route('kasir.pos') }}" class="btn gold" style="margin-bottom:12px">
        🖥️ Buka POS
    </a>
    <form action="{{ route('owner.pos.subscribe') }}" method="POST">
        @csrf
        <button type="submit" class="btn secondary">Perpanjang Sekarang (Rp 25.000)</button>
    </form>

@elseif($sub && $sub->status === 'pending')
    {{-- Pembayaran tertunda --}}
    <div class="info-box" style="margin-bottom:16px">
        ⏳ Pembayaran sedang menunggu konfirmasi dari DOKU. POS akan aktif otomatis setelah pembayaran berhasil.<br><br>
        Jika sudah bayar tapi belum aktif, silakan tunggu beberapa menit atau hubungi kami.
    </div>
    @if($sub->doku_payment_url)
        <a href="{{ $sub->doku_payment_url }}" class="btn gold" style="margin-bottom:12px">
            Lanjutkan Pembayaran →
        </a>
    @endif
    <form action="{{ route('owner.pos.subscribe') }}" method="POST">
        @csrf
        <button type="submit" class="btn secondary">Buat Pembayaran Baru</button>
    </form>

@else
    {{-- Belum berlangganan --}}
    <div class="card" style="margin-bottom:14px">
        <h2 style="margin-bottom:4px">Apa itu POS Digital?</h2>
        <p class="sub" style="margin-bottom:14px">Sistem kasir digital terintegrasi langsung dengan program stempel loyalti kamu.</p>

        <ul class="feature-list">
            <li><span class="ck">✓</span> Input item & harga dengan cepat</li>
            <li><span class="ck">✓</span> Hitung total & kembalian otomatis</li>
            <li><span class="ck">✓</span> Pilih metode bayar: Cash, QRIS, Transfer</li>
            <li><span class="ck">✓</span> Beri stempel otomatis dari transaksi POS</li>
            <li><span class="ck">✓</span> Struk digital bisa di-share ke pelanggan</li>
            <li><span class="ck">✓</span> Riwayat transaksi tersimpan</li>
        </ul>

        <div class="price-box">
            <div class="amount">Rp 25.000</div>
            <div class="period">per bulan · bayar via DOKU</div>
        </div>

        <form action="{{ route('owner.pos.subscribe') }}" method="POST">
            @csrf
            <button type="submit" class="btn gold">
                Aktifkan POS Sekarang →
            </button>
        </form>

        <p class="muted" style="margin-top:12px;text-align:center">
            Kamu akan diarahkan ke halaman pembayaran DOKU yang aman.
        </p>
    </div>
@endif

<div style="height:20px"></div>
@endsection
