@extends('layouts.app')
@section('title', 'POS · {{ $merchant->name }}')

@section('content')
<style>
    /* Cart */
    #cart-list { list-style:none; margin:0 0 10px; }
    #cart-list li { display:flex; align-items:center; gap:10px; padding:12px 0; border-bottom:1px solid var(--line); }
    #cart-list li:last-child { border-bottom:none; }
    .cart-name { flex:1; font-size:14.5px; font-weight:600; }
    .cart-info { font-size:13px; color:var(--muted); }
    .cart-sub { font-size:15px; font-weight:700; min-width:80px; text-align:right; }
    .rm-btn { background:none; border:none; font-size:18px; color:var(--muted); cursor:pointer; padding:4px; line-height:1; }
    .rm-btn:active { color:var(--danger); }
    .empty-cart { text-align:center; padding:28px 0; color:var(--muted); font-size:14px; }

    /* Total bar */
    #total-bar { background:var(--grad-blue); color:#fff; border-radius:16px; padding:14px 18px; display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
    #total-bar .lbl { font-size:13px; opacity:.85; }
    #total-bar .amt { font-size:26px; font-weight:800; letter-spacing:-1px; color:var(--gold-l); }

    /* Payment section */
    .pay-section { background:var(--panel); border:1px solid var(--line); border-radius:18px; padding:16px; margin-bottom:14px; box-shadow:var(--shadow); }
    .method-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin:8px 0 14px; }
    .method-btn { padding:12px 6px; border:2px solid var(--line); border-radius:12px; background:#fff; font-size:13px; font-weight:700; color:var(--muted); cursor:pointer; text-align:center; }
    .method-btn.active { border-color:var(--blue); color:var(--blue); background:#EEF4FF; }
    .disc-row { display:flex; gap:8px; align-items:center; margin-bottom:10px; }
    .disc-row label { font-size:13px; color:var(--muted); white-space:nowrap; margin:0; }
    .cash-row { margin-top:12px; }
    #change-display { background:#F0FFF4; border:1px solid #A7EFC5; border-radius:12px; padding:11px 14px; font-size:15px; font-weight:700; color:#1E7A45; display:none; margin-top:8px; }

    /* ── Menu Picker Modal ── */
    #menu-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:80; align-items:flex-end; justify-content:center; }
    #menu-overlay.open { display:flex; }
    #menu-sheet { background:#fff; border-radius:24px 24px 0 0; width:100%; max-width:480px; max-height:92vh; display:flex; flex-direction:column; overflow:hidden; }
    .sheet-head { padding:16px 18px 12px; border-bottom:1px solid var(--line); flex:none; }
    .sheet-head .drag { width:40px; height:4px; background:#DDE3EF; border-radius:999px; margin:0 auto 14px; }
    .sheet-head h3 { font-size:17px; font-weight:800; margin:0 0 12px; }

    /* Search */
    .search-wrap { position:relative; margin-bottom:10px; }
    .search-wrap input { width:100%; padding:11px 14px 11px 38px; border:1.5px solid var(--line); border-radius:50px; font-size:14px; font-family:inherit; color:var(--text); background:#F5F7FC; }
    .search-wrap input:focus { outline:none; border-color:var(--blue-l); background:#fff; }
    .search-wrap .ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:16px; color:var(--muted); pointer-events:none; }

    /* Category tabs */
    .cat-tabs { display:flex; gap:6px; overflow-x:auto; padding-bottom:2px; }
    .cat-tabs::-webkit-scrollbar { display:none; }
    .cat-tab { padding:7px 16px; border:1.5px solid var(--line); border-radius:999px; font-size:13px; font-weight:700; color:var(--muted); white-space:nowrap; cursor:pointer; background:#fff; flex:none; }
    .cat-tab.active { background:var(--blue); border-color:var(--blue); color:#fff; }

    /* Menu items list */
    .sheet-body { flex:1; overflow-y:auto; padding:14px 18px; }
    .menu-item-row { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--line); }
    .menu-item-row:last-child { border-bottom:none; }
    .mi-icon { width:52px; height:52px; border-radius:14px; background:var(--bg); display:flex; align-items:center; justify-content:center; font-size:26px; flex:none; }
    .mi-info { flex:1; min-width:0; }
    .mi-info .nm { font-weight:700; font-size:15px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .mi-info .pr { font-size:13.5px; color:var(--muted); margin-top:2px; }
    .qty-ctrl { display:flex; align-items:center; gap:8px; flex:none; }
    .qty-ctrl button { width:32px; height:32px; border-radius:50%; border:2px solid var(--blue); background:#fff; color:var(--blue); font-size:20px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; line-height:1; }
    .qty-ctrl button:active { background:var(--blue); color:#fff; }
    .qty-ctrl .qty-num { font-size:16px; font-weight:800; min-width:22px; text-align:center; color:var(--text); }
    .no-menu { text-align:center; padding:40px 20px; color:var(--muted); }

    /* Sheet footer */
    .sheet-foot { padding:14px 18px; border-top:1px solid var(--line); flex:none; }
    #add-to-cart-btn { width:100%; padding:15px; border-radius:16px; background:var(--grad-blue); color:#fff; font-size:16px; font-weight:800; border:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; }
    #add-to-cart-btn:disabled { opacity:.45; cursor:default; }
    #add-to-cart-btn .btn-total { color:var(--gold-l); }

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
</style>

{{-- Header --}}
<div style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
    <a href="{{ route('kasir') }}" style="color:var(--muted); font-size:26px; text-decoration:none; line-height:1; padding:4px 6px 4px 0;">‹</a>
    <div>
        <div style="font-size:18px; font-weight:800; letter-spacing:-.4px;">🖥️ POS Digital</div>
        <div style="font-size:13px; color:var(--muted);">{{ $merchant->name }}</div>
    </div>
</div>

{{-- Add item trigger --}}
<button class="btn primary" style="width:100%; justify-content:center; margin-bottom:14px; padding:14px;" onclick="openMenu()">
    + Tambah Item
</button>

{{-- Cart --}}
<div class="card" style="margin-bottom:14px">
    <ul id="cart-list">
        <li class="empty-cart" id="empty-msg">Belum ada item</li>
    </ul>
</div>

{{-- Total bar --}}
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
        <input type="number" id="discount" placeholder="0" min="0" step="500"
               style="width:140px;padding:10px 12px;font-size:14px;border-radius:11px;border:1.5px solid var(--line);font-family:inherit;"
               oninput="recalc()">
    </div>

    <div class="cash-row" id="cash-section">
        <label for="cash-paid">Uang Diterima (Rp)</label>
        <input type="number" id="cash-paid" placeholder="0" min="0" step="500"
               style="width:100%;padding:12px;font-size:16px;border-radius:12px;border:1.5px solid var(--line);font-family:inherit;font-weight:600;"
               oninput="calcChange()">
        <div id="change-display"></div>
    </div>

    <label for="phone-input" style="margin-top:10px">No. HP Pelanggan <span style="font-size:12px;color:var(--muted)">(opsional · stempel)</span></label>
    <input type="tel" id="phone-input" placeholder="08xxx" style="margin-bottom:10px">

    <label for="note-input">Catatan</label>
    <input type="text" id="note-input" placeholder="Opsional…" maxlength="255" style="margin-bottom:14px">

    <button class="btn gold" id="pay-btn" onclick="processPayment()" disabled>
        Proses Pembayaran
    </button>
</div>

{{-- ── Menu Picker Modal ── --}}
<div id="menu-overlay" onclick="handleOverlayClick(event)">
    <div id="menu-sheet">
        <div class="sheet-head">
            <div class="drag"></div>
            <h3>Pilih Menu</h3>
            <div class="search-wrap">
                <span class="ico">🔍</span>
                <input type="text" id="menu-search" placeholder="Cari menu…" oninput="filterMenu()">
            </div>
            <div class="cat-tabs" id="cat-tabs"></div>
        </div>
        <div class="sheet-body" id="menu-body"></div>
        <div class="sheet-foot">
            <button id="add-to-cart-btn" onclick="confirmAdd()" disabled>
                <span id="btn-label">Pilih item terlebih dahulu</span>
                <span class="btn-total" id="btn-total"></span>
            </button>
        </div>
    </div>
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
        <button class="btn" style="margin-top:18px;width:100%;justify-content:center;" onclick="closeReceipt()">Transaksi Selesai ✓</button>
    </div>
</div>

<div style="height:20px"></div>

<script>
// ── Data dari server ──
var MENU_ITEMS = @json($menuItems);

// ── State ──
var cart     = [];
var method   = 'cash';
var cashPaid = 0;
var pending  = {}; // id → qty (di menu picker)
var activeCategory = 'Semua';
var searchQuery    = '';

// ── Helpers ──
function fmt(n) {
    return 'Rp ' + parseInt(n).toLocaleString('id-ID');
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Cart ──
function getSubtotal() { return cart.reduce(function(s,it){ return s + it.qty * it.price; }, 0); }
function getDiscount()  { return Math.max(0, parseInt(document.getElementById('discount').value) || 0); }
function getTotal()     { return Math.max(0, getSubtotal() - getDiscount()); }

function recalc() {
    document.getElementById('total-display').textContent = fmt(getTotal());
    calcChange();
}

function removeItem(i) { cart.splice(i, 1); renderCart(); }

function renderCart() {
    var ul = document.getElementById('cart-list');
    ul.innerHTML = '';
    if (cart.length === 0) {
        ul.innerHTML = '<li class="empty-cart" id="empty-msg">Belum ada item. Tekan "+ Tambah" untuk memilih menu.</li>';
        document.getElementById('pay-btn').disabled = true;
        recalc();
        return;
    }
    cart.forEach(function(item, i) {
        var li = document.createElement('li');
        li.innerHTML =
            '<button class="rm-btn" onclick="removeItem('+i+')">✕</button>' +
            '<div class="cart-name">' + escHtml(item.name) +
                '<div class="cart-info">' + item.qty + ' × ' + fmt(item.price) + '</div>' +
            '</div>' +
            '<div class="cart-sub">' + fmt(item.qty * item.price) + '</div>';
        ul.appendChild(li);
    });
    document.getElementById('pay-btn').disabled = false;
    recalc();
}

// ── Menu picker ──
function openMenu() {
    pending = {};
    // Rebuild category tabs
    var cats = ['Semua'];
    MENU_ITEMS.forEach(function(m){ if (m.category && cats.indexOf(m.category) === -1) cats.push(m.category); });
    var tabsEl = document.getElementById('cat-tabs');
    tabsEl.innerHTML = '';
    cats.forEach(function(c) {
        var btn = document.createElement('button');
        btn.className = 'cat-tab' + (c === 'Semua' ? ' active' : '');
        btn.textContent = c;
        btn.onclick = function(){ activeCategory = c; document.querySelectorAll('.cat-tab').forEach(function(t){ t.classList.remove('active'); }); btn.classList.add('active'); renderMenuItems(); };
        tabsEl.appendChild(btn);
    });
    activeCategory = 'Semua';
    document.getElementById('menu-search').value = '';
    searchQuery = '';
    renderMenuItems();
    updateAddBtn();
    document.getElementById('menu-overlay').classList.add('open');
}

function handleOverlayClick(e) {
    if (e.target === document.getElementById('menu-overlay')) closeMenu();
}
function closeMenu() {
    document.getElementById('menu-overlay').classList.remove('open');
}

function filterMenu() {
    searchQuery = document.getElementById('menu-search').value.toLowerCase();
    renderMenuItems();
}

function renderMenuItems() {
    var items = MENU_ITEMS.filter(function(m) {
        var matchCat  = activeCategory === 'Semua' || m.category === activeCategory;
        var matchText = !searchQuery || m.name.toLowerCase().indexOf(searchQuery) >= 0;
        return matchCat && matchText;
    });

    var body = document.getElementById('menu-body');
    if (items.length === 0) {
        body.innerHTML = '<div class="no-menu">😕<br>Tidak ada menu ditemukan</div>';
        return;
    }

    body.innerHTML = '';
    items.forEach(function(m) {
        var qty = pending[m.id] || 0;
        var row = document.createElement('div');
        row.className = 'menu-item-row';
        row.id = 'mi-' + m.id;
        row.innerHTML =
            '<div class="mi-icon">🍽️</div>' +
            '<div class="mi-info">' +
                '<div class="nm">' + escHtml(m.name) + '</div>' +
                '<div class="pr">' + fmt(m.price) + '</div>' +
            '</div>' +
            '<div class="qty-ctrl">' +
                '<button onclick="changeQty('+m.id+', -1)">−</button>' +
                '<span class="qty-num" id="qty-'+m.id+'">' + qty + '</span>' +
                '<button onclick="changeQty('+m.id+', 1)">+</button>' +
            '</div>';
        body.appendChild(row);
    });
}

function changeQty(id, delta) {
    pending[id] = Math.max(0, (pending[id] || 0) + delta);
    var el = document.getElementById('qty-' + id);
    if (el) el.textContent = pending[id];
    updateAddBtn();
}

function updateAddBtn() {
    var btn      = document.getElementById('add-to-cart-btn');
    var label    = document.getElementById('btn-label');
    var totalEl  = document.getElementById('btn-total');
    var count    = 0;
    var subtotal = 0;
    MENU_ITEMS.forEach(function(m) {
        var qty = pending[m.id] || 0;
        count    += qty;
        subtotal += qty * m.price;
    });
    if (count === 0) {
        btn.disabled = true;
        label.textContent = 'Pilih item terlebih dahulu';
        totalEl.textContent = '';
    } else {
        btn.disabled = false;
        label.textContent = count + ' item dipilih →';
        totalEl.textContent = fmt(subtotal);
    }
}

function confirmAdd() {
    MENU_ITEMS.forEach(function(m) {
        var qty = pending[m.id] || 0;
        if (qty === 0) return;
        // Cek apakah sudah ada di cart, jika iya tambah qty
        var found = false;
        cart.forEach(function(c) {
            if (c.name === m.name && c.price === m.price) {
                c.qty += qty;
                found = true;
            }
        });
        if (!found) cart.push({ name: m.name, price: m.price, qty: qty });
    });
    closeMenu();
    renderCart();
}

// ── Payment ──
function setMethod(btn) {
    document.querySelectorAll('.method-btn').forEach(function(b){ b.classList.remove('active'); });
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

    fetch('{{ route("kasir.pos.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({
            items:          cart,
            discount:       getDiscount(),
            payment_method: method,
            phone:          document.getElementById('phone-input').value,
            note:           document.getElementById('note-input').value,
        }),
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        if (data.error) { alert(data.error); btn.disabled=false; btn.textContent='Proses Pembayaran'; return; }
        showReceipt(data);
    })
    .catch(function(){ alert('Terjadi kesalahan. Coba lagi.'); btn.disabled=false; btn.textContent='Proses Pembayaran'; });
}

function showReceipt(data) {
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
    var ml = { cash:'💵 Cash', qris:'📱 QRIS', transfer:'🏦 Transfer' };
    document.getElementById('r-method').textContent = ml[data.payment_method] || data.payment_method;
    if (data.payment_method === 'cash' && cashPaid > data.total) {
        document.getElementById('r-change-row').style.display = 'flex';
        document.getElementById('r-change').textContent = fmt(cashPaid - data.total);
    }
    document.getElementById('receipt-overlay').classList.add('open');
}

function closeReceipt() {
    document.getElementById('receipt-overlay').classList.remove('open');
    cart = []; cashPaid = 0;
    document.getElementById('discount').value   = '';
    document.getElementById('cash-paid').value  = '';
    document.getElementById('phone-input').value = '';
    document.getElementById('note-input').value  = '';
    document.getElementById('r-disc-row').style.display   = 'none';
    document.getElementById('r-change-row').style.display = 'none';
    document.getElementById('change-display').style.display = 'none';
    renderCart();
    document.getElementById('pay-btn').textContent = 'Proses Pembayaran';
}

// Init
setMethod(document.querySelector('.method-btn.active'));
renderCart();
</script>
@endsection
