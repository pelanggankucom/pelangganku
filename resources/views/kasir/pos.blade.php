@extends('layouts.app')
@section('title', 'POS · {{ $merchant->name }}')

@section('content')
<style>
    .pos-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
    .pos-header h1 { margin:0; font-size:20px; }
    /* Cart */
    #cart-list { list-style:none; margin:0 0 10px; }
    #cart-list li { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--line); }
    #cart-list li:last-child { border-bottom:none; }
    .cart-name { flex:1; font-size:14.5px; font-weight:600; }
    .cart-info { font-size:13px; color:var(--muted); }
    .cart-sub { font-size:15px; font-weight:700; min-width:80px; text-align:right; }
    .rm-btn { background:none; border:none; font-size:18px; color:var(--muted); cursor:pointer; padding:4px; line-height:1; }
    .rm-btn:active { color:var(--danger); }
    /* Total bar */
    #total-bar { background:var(--grad-blue); color:#fff; border-radius:16px; padding:14px 18px; display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
    #total-bar .lbl { font-size:13px; opacity:.85; }
    #total-bar .amt { font-size:26px; font-weight:800; letter-spacing:-1px; color:var(--gold-l); }
    /* Add item form */
    .add-form { background:var(--panel); border:1px solid var(--line); border-radius:18px; padding:16px; margin-bottom:14px; box-shadow:var(--shadow); }
    .add-form h2 { font-size:15px; margin-bottom:12px; }
    .add-row { display:grid; grid-template-columns:1fr 90px 60px; gap:8px; margin-bottom:10px; }
    .add-row input { padding:12px; font-size:15px; border-radius:12px; border:1.5px solid var(--line); background:#fff; color:var(--text); font-family:inherit; font-weight:500; }
    .add-row input:focus { outline:none; border-color:var(--blue-l); box-shadow:0 0 0 3px rgba(30,102,208,.12); }
    .add-row input::placeholder { color:#b0bcd0; }
    /* Payment section */
    .pay-section { background:var(--panel); border:1px solid var(--line); border-radius:18px; padding:16px; margin-bottom:14px; box-shadow:var(--shadow); }
    .method-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin:8px 0 14px; }
    .method-btn { padding:12px 6px; border:2px solid var(--line); border-radius:12px; background:#fff; font-size:13px; font-weight:700; color:var(--muted); cursor:pointer; text-align:center; transition:.1s; }
    .method-btn.active { border-color:var(--blue); color:var(--blue); background:#EEF4FF; }
    /* Discount */
    .disc-row { display:flex; gap:8px; align-items:center; margin-bottom:10px; }
    .disc-row label { font-size:13px; color:var(--muted); white-space:nowrap; margin:0; }
    /* Receipt modal */
    #receipt-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:100; overflow-y:auto; padding:20px 0; }
    #receipt-overlay.open { display:flex; align-items:flex-start; justify-content:center; }
    #receipt { background:#fff; border-radius:20px; width:calc(100% - 36px); max-width:400px; margin:auto; padding:24px; box-shadow:0 24px 60px rgba(0,0,0,.35); }
    .receipt-logo { text-align:center; font-weight:800; font-size:18px; color:var(--navy); margin-bottom:4px; }
    .receipt-no { text-align:center; font-size:12px; color:var(--muted); margin-bottom:16px; }
    .receipt-line { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed #ddd; font-size:14px; }
    .receipt-line:last-child { border-bottom:none; }
    .receipt-total { display:flex; justify-content:space-between; padding:12px 0 0; font-size:17px; font-weight:800; }
    .receipt-footer { text-align:center; font-size:12px; color:var(--muted); margin-top:16px; border-top:1px dashed #ddd; padding-top:14px; }
    .cash-row { margin-top:12px; }
    #change-display { background:#F0FFF4; border:1px solid #A7EFC5; border-radius:12px; padding:11px 14px; font-size:15px; font-weight:700; color:#1E7A45; display:none; margin-top:8px; }
    .empty-cart { text-align:center; padding:30px 0; color:var(--muted); font-size:14px; }
</style>

<div class="pos-header">
    <h1>🖥️ POS</h1>
    <a href="{{ route('kasir') }}" class="btn secondary sm">← Kasir</a>
</div>

{{-- Add Item --}}
<div class="add-form">
    <h2>Tambah Item</h2>
    <div class="add-row">
        <input type="text"   id="item-name"  placeholder="Nama item…" maxlength="80">
        <input type="number" id="item-price" placeholder="Harga" min="0" step="500">
        <input type="number" id="item-qty"   placeholder="Qty" min="1" value="1">
    </div>
    <button class="btn sm" id="add-btn" onclick="addItem()">+ Tambah</button>
</div>

{{-- Cart --}}
<div class="card" style="margin-bottom:14px">
    <ul id="cart-list">
        <li class="empty-cart" id="empty-msg">Belum ada item</li>
    </ul>
</div>

{{-- Total --}}
<div id="total-bar">
    <div class="lbl">Total</div>
    <div class="amt" id="total-display">Rp 0</div>
</div>

{{-- Payment --}}
<div class="pay-section">
    <label style="margin:0 0 6px">Metode Pembayaran</label>
    <div class="method-row">
        <button class="method-btn active" data-method="cash"     onclick="setMethod(this)">💵 Cash</button>
        <button class="method-btn"        data-method="qris"     onclick="setMethod(this)">📱 QRIS</button>
        <button class="method-btn"        data-method="transfer" onclick="setMethod(this)">🏦 Transfer</button>
    </div>

    <div class="disc-row">
        <label for="discount">Diskon (Rp)</label>
        <input type="number" id="discount" placeholder="0" min="0" step="500" style="width:140px;padding:10px 12px;font-size:14px;border-radius:11px;border:1.5px solid var(--line);font-family:inherit;" oninput="recalc()">
    </div>

    <div class="cash-row" id="cash-section">
        <label for="cash-paid">Uang Diterima (Rp)</label>
        <input type="number" id="cash-paid" placeholder="0" min="0" step="500" style="width:100%;padding:12px;font-size:16px;border-radius:12px;border:1.5px solid var(--line);font-family:inherit;font-weight:600;" oninput="calcChange()">
        <div id="change-display"></div>
    </div>

    <label for="phone-input" style="margin-top:10px">No. HP Pelanggan (opsional · untuk stempel)</label>
    <input type="tel" id="phone-input" placeholder="08xxx" style="margin-bottom:10px">

    <label for="note-input">Catatan</label>
    <input type="text" id="note-input" placeholder="Opsional…" maxlength="255" style="margin-bottom:14px">

    <button class="btn gold" id="pay-btn" onclick="processPayment()" disabled>
        Proses Pembayaran
    </button>
</div>

{{-- Receipt Modal --}}
<div id="receipt-overlay">
    <div id="receipt">
        <div class="receipt-logo">{{ $merchant->name }}</div>
        <div class="receipt-no" id="r-no"></div>
        <div id="r-items"></div>
        <div class="receipt-total">
            <span>TOTAL</span>
            <span id="r-total"></span>
        </div>
        <div class="receipt-total" id="r-disc-row" style="font-size:14px;font-weight:600;color:var(--ok);display:none">
            <span>Diskon</span>
            <span id="r-disc"></span>
        </div>
        <div class="receipt-line" style="margin-top:10px">
            <span>Metode Bayar</span>
            <span id="r-method"></span>
        </div>
        <div class="receipt-line" id="r-change-row" style="display:none">
            <span>Kembalian</span>
            <span id="r-change"></span>
        </div>
        <div class="receipt-line">
            <span>Kasir</span>
            <span id="r-kasir"></span>
        </div>
        <div class="receipt-line" style="border:none">
            <span>Waktu</span>
            <span id="r-time"></span>
        </div>
        <div class="receipt-footer">
            Terima kasih sudah berbelanja!<br>
            Struk ini dihasilkan oleh pelangganku.com
        </div>
        <button class="btn" style="margin-top:18px" onclick="closeReceipt()">Transaksi Selesai ✓</button>
    </div>
</div>

<script>
var cart = [];
var method = 'cash';
var cashPaid = 0;

function fmt(n) {
    return 'Rp ' + parseInt(n).toLocaleString('id-ID');
}

function addItem() {
    var name  = document.getElementById('item-name').value.trim();
    var price = parseInt(document.getElementById('item-price').value) || 0;
    var qty   = parseInt(document.getElementById('item-qty').value)   || 1;

    if (!name)  { document.getElementById('item-name').focus();  return; }
    if (!price) { document.getElementById('item-price').focus(); return; }

    cart.push({ name: name, price: price, qty: qty });
    document.getElementById('item-name').value  = '';
    document.getElementById('item-price').value = '';
    document.getElementById('item-qty').value   = '1';
    document.getElementById('item-name').focus();
    renderCart();
}

function removeItem(i) {
    cart.splice(i, 1);
    renderCart();
}

function renderCart() {
    var ul = document.getElementById('cart-list');
    ul.innerHTML = '';

    if (cart.length === 0) {
        ul.innerHTML = '<li class="empty-cart" id="empty-msg">Belum ada item</li>';
        document.getElementById('pay-btn').disabled = true;
        recalc();
        return;
    }

    cart.forEach(function(item, i) {
        var li = document.createElement('li');
        li.innerHTML =
            '<button class="rm-btn" onclick="removeItem(' + i + ')">✕</button>' +
            '<div class="cart-name">' + escHtml(item.name) +
                '<div class="cart-info">' + item.qty + ' × ' + fmt(item.price) + '</div>' +
            '</div>' +
            '<div class="cart-sub">' + fmt(item.qty * item.price) + '</div>';
        ul.appendChild(li);
    });

    document.getElementById('pay-btn').disabled = false;
    recalc();
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function getSubtotal() {
    return cart.reduce(function(s, it) { return s + it.qty * it.price; }, 0);
}

function getDiscount() {
    return Math.max(0, parseInt(document.getElementById('discount').value) || 0);
}

function getTotal() {
    return Math.max(0, getSubtotal() - getDiscount());
}

function recalc() {
    document.getElementById('total-display').textContent = fmt(getTotal());
    calcChange();
}

function setMethod(btn) {
    document.querySelectorAll('.method-btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    method = btn.dataset.method;
    document.getElementById('cash-section').style.display = method === 'cash' ? 'block' : 'none';
}

function calcChange() {
    if (method !== 'cash') return;
    cashPaid = parseInt(document.getElementById('cash-paid').value) || 0;
    var total = getTotal();
    var box = document.getElementById('change-display');
    if (cashPaid > 0 && cashPaid >= total) {
        box.style.display = 'block';
        box.textContent = 'Kembalian: ' + fmt(cashPaid - total);
    } else {
        box.style.display = 'none';
    }
}

function processPayment() {
    if (cart.length === 0) return;

    var btn = document.getElementById('pay-btn');
    btn.disabled = true;
    btn.textContent = 'Memproses…';

    var payload = {
        items:          cart,
        discount:       getDiscount(),
        payment_method: method,
        phone:          document.getElementById('phone-input').value,
        note:           document.getElementById('note-input').value,
        _token:         document.querySelector('meta[name=csrf-token]').content,
    };

    fetch('{{ route("kasir.pos.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token },
        body: JSON.stringify(payload),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error) {
            alert(data.error);
            btn.disabled = false;
            btn.textContent = 'Proses Pembayaran';
            return;
        }
        showReceipt(data);
    })
    .catch(function() {
        alert('Terjadi kesalahan. Coba lagi.');
        btn.disabled = false;
        btn.textContent = 'Proses Pembayaran';
    });
}

function showReceipt(data) {
    // Items
    var html = '';
    data.items.forEach(function(it) {
        html += '<div class="receipt-line"><span>' + escHtml(it.name) + ' ×' + it.qty + '</span><span>' + fmt(it.subtotal) + '</span></div>';
    });
    document.getElementById('r-items').innerHTML = html;
    document.getElementById('r-no').textContent = data.order_number + ' · ' + data.created_at;
    document.getElementById('r-total').textContent = fmt(data.total);
    document.getElementById('r-kasir').textContent = data.kasir_name;
    document.getElementById('r-time').textContent = data.created_at;

    if (data.discount > 0) {
        document.getElementById('r-disc-row').style.display = 'flex';
        document.getElementById('r-disc').textContent = '- ' + fmt(data.discount);
    }

    var methodLabel = { cash: '💵 Cash', qris: '📱 QRIS', transfer: '🏦 Transfer' };
    document.getElementById('r-method').textContent = methodLabel[data.payment_method] || data.payment_method;

    if (data.payment_method === 'cash' && cashPaid > 0) {
        document.getElementById('r-change-row').style.display = 'flex';
        document.getElementById('r-change').textContent = fmt(cashPaid - data.total);
    }

    document.getElementById('receipt-overlay').classList.add('open');
}

function closeReceipt() {
    document.getElementById('receipt-overlay').classList.remove('open');
    // Reset
    cart = [];
    cashPaid = 0;
    document.getElementById('discount').value = '';
    document.getElementById('cash-paid').value = '';
    document.getElementById('phone-input').value = '';
    document.getElementById('note-input').value = '';
    document.getElementById('r-disc-row').style.display = 'none';
    document.getElementById('r-change-row').style.display = 'none';
    document.getElementById('change-display').style.display = 'none';
    renderCart();
    document.getElementById('pay-btn').textContent = 'Proses Pembayaran';
    document.getElementById('item-name').focus();
}

// Enter key on item fields
['item-name','item-price','item-qty'].forEach(function(id) {
    document.getElementById(id).addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); addItem(); }
    });
});

// Init
setMethod(document.querySelector('.method-btn.active'));
</script>

<div style="height:30px"></div>
@endsection
