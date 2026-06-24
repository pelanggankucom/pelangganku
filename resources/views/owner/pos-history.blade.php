@extends('layouts.app')
@section('title', 'Riwayat Transaksi POS')

@section('content')
<style>
    .trx-row { display:flex; align-items:center; gap:10px; padding:13px 16px; border-bottom:1px solid var(--line); cursor:pointer; transition:background .15s; }
    .trx-row:last-child { border-bottom:none; }
    .trx-row:active { background:var(--bg); }
    .trx-method { font-size:11.5px; color:var(--muted); font-weight:700; text-transform:uppercase; min-width:52px; text-align:right; }
    .trx-amount { font-size:14px; font-weight:800; color:var(--blue); min-width:90px; text-align:right; }

    /* Receipt popup */
    #hist-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:100; overflow-y:auto; padding:20px 0; }
    #hist-overlay.open { display:flex; align-items:flex-start; justify-content:center; }
    #hist-receipt { background:#fff; border-radius:20px; padding:24px 20px; width:calc(100% - 32px); max-width:400px; margin:auto; font-size:14px; position:relative; }

    .hr-logo { text-align:center; font-weight:800; font-size:17px; color:var(--navy); margin-bottom:3px; }
    .hr-sub  { text-align:center; font-size:12px; color:var(--muted); margin-bottom:10px; }
    .hr-no   { text-align:center; font-size:12px; color:var(--muted); margin:6px 0 14px; }
    .hr-row  { display:flex; justify-content:space-between; align-items:baseline; padding:5px 0; border-bottom:1px dashed #eee; font-size:14px; }
    .hr-row:last-of-type { border-bottom:none; }
    .hr-total{ display:flex; justify-content:space-between; font-weight:800; font-size:16px; padding:8px 0; border-top:1.5px solid var(--line); border-bottom:1.5px solid var(--line); margin:4px 0; }
    .hr-meta { font-size:13px; color:var(--muted); display:flex; justify-content:space-between; padding:4px 0; }
    .hr-loyalty { border-top:1px dashed #ddd; margin-top:10px; padding-top:10px; font-size:13px; }
    .hr-footer { text-align:center; font-size:12px; color:var(--muted); border-top:1px dashed #ddd; padding-top:10px; margin-top:10px; }

    /* Print */
    #hist-print-area { display:none; }
    @media print {
        body > * { display:none !important; }
        #hist-print-area { display:block !important; visibility:visible; font-family:'Courier New',monospace; font-size:12px; width:100%; }
        #hist-print-area .ph  { text-align:center; font-weight:700; font-size:14px; margin-bottom:4px; }
        #hist-print-area .ps  { text-align:center; font-size:11px; margin-bottom:8px; border-bottom:1px dashed #000; padding-bottom:6px; }
        #hist-print-area .pno { text-align:center; font-size:10px; margin-bottom:6px; }
        #hist-print-area .pr  { display:flex; justify-content:space-between; margin:2px 0; }
        #hist-print-area .pdiv{ border-bottom:1px dashed #000; margin:6px 0; }
        #hist-print-area .ptot{ display:flex; justify-content:space-between; font-weight:700; font-size:13px; margin:4px 0; }
        #hist-print-area .pft { text-align:center; font-size:11px; border-top:1px dashed #000; padding-top:6px; margin-top:8px; }
    }
</style>

<div style="margin-bottom:18px; display:flex; justify-content:space-between; align-items:center;">
    <div>
        <div style="font-size:18px; font-weight:800; letter-spacing:-.4px;">Riwayat Transaksi</div>
        <div style="font-size:13px; color:var(--muted);">{{ $merchant->name }}</div>
    </div>
    <a href="{{ route('owner.pos.history.export') }}" style="padding:10px 16px; background:#0D47A1; color:#fff; border-radius:12px; font-size:13px; font-weight:700; text-decoration:none; white-space:nowrap; display:inline-block;">
        📥 Export
    </a>
</div>

@if($orders->isEmpty())
    <div style="text-align:center; padding:48px 20px; color:var(--muted);">
        <div style="font-size:40px; margin-bottom:10px;">🧾</div>
        <p style="font-weight:600;">Belum ada transaksi.</p>
    </div>
@else
    <div class="card" style="padding:0; overflow:hidden; margin-bottom:14px;">
        @foreach($orders as $order)
        <div class="trx-row" onclick="openOrder({{ $order->id }})">
            <div style="flex:1; min-width:0;">
                <div style="font-size:13.5px; font-weight:700;">{{ $order->order_number }}</div>
                <div style="font-size:12px; color:var(--muted);">{{ $order->created_at->format('d M Y · H:i') }}</div>
            </div>
            <div class="trx-method">{{ $order->payment_method }}</div>
            <div class="trx-amount">Rp {{ number_format($order->total, 0, ',', '.') }}</div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
        @if($orders->onFirstPage())
            <span style="padding:8px 14px; border-radius:10px; border:1.5px solid var(--line); color:var(--muted); font-size:13px; font-weight:700;">‹</span>
        @else
            <a href="{{ $orders->previousPageUrl() }}" style="padding:8px 14px; border-radius:10px; border:1.5px solid var(--line); color:var(--blue); font-size:13px; font-weight:700; text-decoration:none;">‹</a>
        @endif
        <span style="padding:8px 14px; font-size:13px; color:var(--muted); font-weight:600;">
            Hal {{ $orders->currentPage() }} / {{ $orders->lastPage() }}
        </span>
        @if($orders->hasMorePages())
            <a href="{{ $orders->nextPageUrl() }}" style="padding:8px 14px; border-radius:10px; border:1.5px solid var(--line); color:var(--blue); font-size:13px; font-weight:700; text-decoration:none;">›</a>
        @else
            <span style="padding:8px 14px; border-radius:10px; border:1.5px solid var(--line); color:var(--muted); font-size:13px; font-weight:700;">›</span>
        @endif
    </div>
    @endif
@endif {{-- end isEmpty --}}

<button onclick="history.back()"
   style="display:flex; align-items:center; justify-content:center; gap:8px;
          width:100%; padding:16px; border-radius:16px;
          background:#fff; border:1.5px solid var(--line);
          font-size:15px; font-weight:700; color:var(--navy); cursor:pointer; font-family:inherit;">
    ← Kembali
</button>

<div style="height:24px;"></div>

{{-- Receipt Popup --}}
<div id="hist-overlay">
    <div id="hist-receipt">
        {{-- Header --}}
        <div class="hr-logo">{{ $merchant->name }}</div>
        <div id="hr-addr" class="hr-sub"></div>
        <div id="hr-no" class="hr-no"></div>

        {{-- Items --}}
        <div id="hr-items"></div>

        {{-- Totals --}}
        <div id="hr-disc-row" class="hr-row" style="display:none;">
            <span>Diskon</span><span id="hr-disc" style="color:var(--danger)"></span>
        </div>
        <div class="hr-total">
            <span>TOTAL</span><span id="hr-total"></span>
        </div>

        {{-- Meta --}}
        <div class="hr-meta"><span>Metode Bayar</span><span id="hr-method"></span></div>
        <div class="hr-meta"><span>Kasir</span><span id="hr-kasir"></span></div>
        <div class="hr-meta"><span>Waktu</span><span id="hr-time"></span></div>

        {{-- Loyalty / Customer --}}
        <div id="hr-loyalty" class="hr-loyalty" style="display:none;">
            <div id="hr-loyalty-header" style="font-weight:700; margin-bottom:3px;"></div>
            <div id="hr-loyalty-phone" style="color:var(--muted); font-size:12px; margin-bottom:3px;"></div>
        </div>

        <div id="hr-note" style="display:none; font-size:12px; color:var(--muted); border-top:1px dashed #eee; padding-top:8px; margin-top:8px;"></div>
        <div id="hr-footer" class="hr-footer"></div>

        {{-- Buttons --}}
        <div style="display:flex; gap:10px; margin-top:18px;">
            <button onclick="printHistReceipt()"
                style="flex:1; padding:14px; border-radius:14px; background:#F5F7FC; color:var(--navy); border:1.5px solid var(--line); font-size:15px; font-weight:700; cursor:pointer; font-family:inherit;">
                🖨️ Cetak
            </button>
            <button onclick="closeHistReceipt()"
                style="flex:1; padding:14px; border-radius:14px; background:var(--blue); color:#fff; border:none; font-size:15px; font-weight:700; cursor:pointer; font-family:inherit;">
                Tutup ✕
            </button>
        </div>
    </div>
</div>

{{-- Print target --}}
<div id="hist-print-area"></div>

<script>
@php
$ordersJs = collect($orders->items())->mapWithKeys(function($o) {
    return [$o->id => [
        'id'             => $o->id,
        'order_number'   => $o->order_number,
        'subtotal'       => $o->subtotal,
        'discount'       => $o->discount,
        'total'          => $o->total,
        'payment_method' => $o->payment_method,
        'note'           => $o->note,
        'kasir_name'     => $o->user ? $o->user->name : '—',
        'created_at'     => $o->created_at->format('d M Y H:i'),
        'customer_name'  => $o->customer ? $o->customer->name : null,
        'customer_phone' => $o->customer ? $o->customer->phone_raw : null,
        'items'          => $o->items->map(function($i) {
            return ['name' => $i->name, 'qty' => $i->qty, 'price' => $i->price, 'subtotal' => $i->subtotal];
        })->values(),
    ]];
});
@endphp
var ORDERS = @json($ordersJs);

var MERCHANT_NAME = @json($merchant->name);
var MERCHANT_ADDR = @json($merchant->address ?? '');
var MERCHANT_WA   = @json($merchant->whatsapp ?? '');
var PS            = @json($merchant->printerSettings());
var FOOTER_TEXT   = PS.footer_text ?? '';

var currentOrder = null;

function fmt(n) {
    return 'Rp ' + Number(n).toLocaleString('id-ID');
}
function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function openOrder(id) {
    var o = ORDERS[id];
    if (!o) return;
    currentOrder = o;

    // Addr
    var addrParts = [];
    if (PS.show_address && MERCHANT_ADDR) addrParts.push(MERCHANT_ADDR);
    if (PS.show_whatsapp && MERCHANT_WA)  addrParts.push('WA: ' + MERCHANT_WA);
    document.getElementById('hr-addr').textContent = addrParts.join(' · ');

    document.getElementById('hr-no').textContent = o.order_number + ' · ' + o.created_at;

    // Items
    var itemsHtml = '';
    o.items.forEach(function(it) {
        itemsHtml += '<div class="hr-row"><span>' + esc(it.name) + ' ×' + it.qty + '</span><span>' + fmt(it.subtotal) + '</span></div>';
    });
    document.getElementById('hr-items').innerHTML = itemsHtml;

    // Discount
    if (o.discount > 0) {
        document.getElementById('hr-disc').textContent = '- ' + fmt(o.discount);
        document.getElementById('hr-disc-row').style.display = 'flex';
    } else {
        document.getElementById('hr-disc-row').style.display = 'none';
    }

    document.getElementById('hr-total').textContent = fmt(o.total);

    var ml = { cash:'💵 Cash', qris:'📱 QRIS', transfer:'🏦 Transfer' };
    document.getElementById('hr-method').textContent = ml[o.payment_method] || o.payment_method;
    document.getElementById('hr-kasir').textContent  = o.kasir_name;
    document.getElementById('hr-time').textContent   = o.created_at;

    // Customer
    var loyaltyEl = document.getElementById('hr-loyalty');
    if (o.customer_name) {
        document.getElementById('hr-loyalty-header').textContent = '👤 ' + o.customer_name;
        document.getElementById('hr-loyalty-phone').textContent  = o.customer_phone ? '📱 ' + o.customer_phone : '';
        loyaltyEl.style.display = 'block';
    } else {
        loyaltyEl.style.display = 'none';
    }

    // Note
    var noteEl = document.getElementById('hr-note');
    if (o.note) {
        noteEl.textContent = '📝 ' + o.note;
        noteEl.style.display = 'block';
    } else {
        noteEl.style.display = 'none';
    }

    document.getElementById('hr-footer').textContent = FOOTER_TEXT;

    document.getElementById('hist-overlay').classList.add('open');
}

function closeHistReceipt() {
    document.getElementById('hist-overlay').classList.remove('open');
    currentOrder = null;
}

document.getElementById('hist-overlay').addEventListener('click', function(e) {
    if (e.target === this) closeHistReceipt();
});

function printHistReceipt() {
    if (!currentOrder) return;
    var o = currentOrder;
    var h = '';
    h += '<div class="ph">' + esc(MERCHANT_NAME) + '</div>';
    var sub = [];
    if (PS.show_address && MERCHANT_ADDR) sub.push(esc(MERCHANT_ADDR));
    if (PS.show_whatsapp && MERCHANT_WA)  sub.push('WA: ' + esc(MERCHANT_WA));
    if (sub.length) h += '<div class="ps">' + sub.join('<br>') + '</div>';
    h += '<div class="pno">' + esc(o.order_number) + ' · ' + esc(o.created_at) + '</div>';
    o.items.forEach(function(it) {
        h += '<div class="pr"><span>' + esc(it.name) + ' ×' + it.qty + '</span><span>' + fmt(it.subtotal) + '</span></div>';
    });
    h += '<div class="pdiv"></div>';
    if (o.discount > 0) h += '<div class="pr"><span>Diskon</span><span>- ' + fmt(o.discount) + '</span></div>';
    h += '<div class="ptot"><span>TOTAL</span><span>' + fmt(o.total) + '</span></div>';
    var ml = { cash:'Cash', qris:'QRIS', transfer:'Transfer' };
    h += '<div class="pr"><span>Metode</span><span>' + (ml[o.payment_method] || o.payment_method) + '</span></div>';
    h += '<div class="pr"><span>Kasir</span><span>' + esc(o.kasir_name) + '</span></div>';
    if (o.customer_name) {
        h += '<div class="pdiv"></div>';
        h += '<div style="font-size:12px;">';
        h += '<div>👤 ' + esc(o.customer_name) + '</div>';
        if (o.customer_phone) h += '<div>📱 ' + esc(o.customer_phone) + '</div>';
        h += '</div>';
    }
    if (o.note) h += '<div class="pr" style="font-size:11px;"><span>Catatan</span><span>' + esc(o.note) + '</span></div>';
    if (FOOTER_TEXT) h += '<div class="pft">' + esc(FOOTER_TEXT) + '</div>';
    document.getElementById('hist-print-area').innerHTML = h;
    window.print();
}
</script>
@endsection
