@extends('layouts.app')
@section('title', 'Pengaturan Printer')

@section('content')
<style>
    .toggle-row { display:flex; justify-content:space-between; align-items:center; padding:14px 0; border-bottom:1px solid var(--line); }
    .toggle-row:last-child { border-bottom:none; }
    .toggle-row .lbl b { display:block; font-size:15px; font-weight:700; }
    .toggle-row .lbl span { font-size:13px; color:var(--muted); }
    .toggle { position:relative; width:48px; height:28px; flex:none; }
    .toggle input { opacity:0; width:0; height:0; position:absolute; }
    .toggle-track { position:absolute; inset:0; background:#DDE3EF; border-radius:999px; cursor:pointer; transition:.2s; }
    .toggle input:checked + .toggle-track { background:var(--blue); }
    .toggle-track::after { content:''; position:absolute; top:3px; left:3px; width:22px; height:22px; border-radius:50%; background:#fff; transition:.2s; box-shadow:0 2px 4px rgba(0,0,0,.2); }
    .toggle input:checked + .toggle-track::after { transform:translateX(20px); }

    /* Struk Preview */
    .struk-preview { background:#fff; border:1.5px dashed var(--line); border-radius:16px; padding:20px; font-family:'Courier New', monospace; font-size:13px; line-height:1.6; color:#1a1a1a; max-width:320px; margin:0 auto; }
    .struk-preview .head { text-align:center; font-weight:700; font-size:15px; border-bottom:1px dashed #ccc; padding-bottom:10px; margin-bottom:10px; }
    .struk-preview .row { display:flex; justify-content:space-between; }
    .struk-preview .divider { border-bottom:1px dashed #ccc; margin:8px 0; }
    .struk-preview .total-row { display:flex; justify-content:space-between; font-weight:700; font-size:15px; }
    .struk-preview .footer { text-align:center; font-size:12px; color:#888; border-top:1px dashed #ccc; padding-top:10px; margin-top:10px; }
</style>

<div style="margin-bottom:18px;">
    <div style="font-size:18px; font-weight:800; letter-spacing:-.4px;">Pengaturan Printer</div>
    <div style="font-size:13px; color:var(--muted);">Atur tampilan struk & cetak otomatis</div>
</div>

{{-- Preview --}}
<div class="card" style="margin-bottom:18px;">
    <div style="font-size:13px; font-weight:700; color:var(--muted); margin-bottom:12px;">Preview Struk</div>
    <div class="struk-preview">
        <div class="head">
            <div id="pv-name">{{ $merchant->name }}</div>
            <div id="pv-address" style="font-size:12px; font-weight:400; color:#555; margin-top:2px;">{{ $merchant->address }}</div>
            <div id="pv-wa" style="font-size:12px; font-weight:400; color:#555;">{{ $merchant->whatsapp ? 'WA: '.$merchant->whatsapp : '' }}</div>
        </div>
        <div style="font-size:11px; color:#888; margin-bottom:8px;">No: POS-001 · 24 Jun 2026 08:30</div>
        <div class="row"><span>Kopi Susu ×2</span><span>Rp 18.000</span></div>
        <div class="row"><span>Croissant ×1</span><span>Rp 12.000</span></div>
        <div class="divider"></div>
        <div class="total-row"><span>TOTAL</span><span>Rp 30.000</span></div>
        <div class="row" style="margin-top:4px;"><span>Metode Bayar</span><span>Cash</span></div>
        <div class="row"><span>Kasir</span><span>Budi</span></div>
        <div class="footer" id="pv-footer">{{ $settings['footer_text'] }}</div>
    </div>
</div>

{{-- Form --}}
<form action="{{ route('owner.printer.update') }}" method="POST">
    @csrf
    <div class="card" style="margin-bottom:14px;">
        <div class="toggle-row">
            <div class="lbl">
                <b>Tampilkan Alamat</b>
                <span>Alamat toko muncul di header struk</span>
            </div>
            <label class="toggle">
                <input type="checkbox" name="show_address" value="1" onchange="updatePreview()" {{ $settings['show_address'] ? 'checked' : '' }}>
                <div class="toggle-track"></div>
            </label>
        </div>
        <div class="toggle-row">
            <div class="lbl">
                <b>Tampilkan No. WhatsApp</b>
                <span>Nomor WA toko muncul di struk</span>
            </div>
            <label class="toggle">
                <input type="checkbox" name="show_whatsapp" value="1" onchange="updatePreview()" {{ $settings['show_whatsapp'] ? 'checked' : '' }}>
                <div class="toggle-track"></div>
            </label>
        </div>
        <div class="toggle-row">
            <div class="lbl">
                <b>Cetak Otomatis</b>
                <span>Dialog cetak muncul langsung setelah transaksi</span>
            </div>
            <label class="toggle">
                <input type="checkbox" name="auto_print" value="1" {{ $settings['auto_print'] ? 'checked' : '' }}>
                <div class="toggle-track"></div>
            </label>
        </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
        <label style="font-size:13px; font-weight:700; color:var(--muted); display:block; margin-bottom:6px;">Pesan di Bawah Struk</label>
        <input type="text" name="footer_text" value="{{ $settings['footer_text'] }}"
               placeholder="Terima kasih sudah berbelanja!" maxlength="200"
               oninput="document.getElementById('pv-footer').textContent = this.value || 'Terima kasih sudah berbelanja!'"
               style="width:100%; padding:11px 14px; border:1.5px solid var(--line); border-radius:12px; font-size:14px; font-family:inherit;">
        <div style="font-size:12px; color:var(--muted); margin-top:6px;">Akan muncul di bagian bawah setiap struk.</div>
    </div>

    <button type="submit" class="btn primary" style="width:100%; justify-content:center; margin-bottom:12px;">
        Simpan Pengaturan
    </button>
</form>

<a href="{{ route('owner.settings') }}"
   style="display:flex; align-items:center; justify-content:center; gap:8px;
          width:100%; padding:16px; border-radius:16px;
          background:#fff; border:1.5px solid var(--line);
          font-size:15px; font-weight:700; color:var(--navy); text-decoration:none;">
    ← Kembali ke Atur
</a>
<div style="height:24px;"></div>

<script>
var ADDR = @json($merchant->address ?? '');
var WA   = @json($merchant->whatsapp ? 'WA: '.$merchant->whatsapp : '');

function updatePreview() {
    var showAddr = document.querySelector('[name=show_address]').checked;
    var showWa   = document.querySelector('[name=show_whatsapp]').checked;
    document.getElementById('pv-address').textContent = showAddr ? ADDR : '';
    document.getElementById('pv-wa').textContent      = showWa   ? WA   : '';
}
updatePreview();
</script>
@endsection
