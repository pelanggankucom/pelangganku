<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0A2A5C">
    <title>@yield('title', 'Super Admin') — pelangganku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy:#0A2A5C; --blue:#0D47A1; --blue-l:#1E66D0; --blue-xl:#3B82E8;
            --gold:#F6B931; --gold-l:#FFCF5C; --gold-d:#D99812;
            --bg:#EEF2F9; --panel:#fff; --line:#E4EAF3; --text:#0F2444; --muted:#73839E;
            --ok:#1E9E5A; --ok-bg:#E4F6EC; --danger:#E0344B; --danger-bg:#FCE8EB;
            --grad-blue:linear-gradient(135deg,#0A2A5C 0%,#0D47A1 55%,#1E66D0 100%);
            --grad-gold:linear-gradient(135deg,#FFCF5C 0%,#F6B931 55%,#D99812 100%);
            --shadow:0 6px 22px rgba(15,36,68,.07);
        }
        * { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
        body { font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; -webkit-font-smoothing:antialiased; }

        /* Layout */
        .sa-wrap { display:flex; min-height:100vh; }

        /* Sidebar */
        .sidebar { width:220px; background:var(--grad-blue); color:#fff; display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh; z-index:10; }
        .sidebar .brand { display:flex; align-items:center; gap:10px; padding:20px 18px 14px; font-weight:800; font-size:16px; border-bottom:1px solid rgba(255,255,255,.12); }
        .sidebar .brand img { height:30px; width:30px; background:#fff; border-radius:8px; padding:3px; }
        .sidebar .brand .dot { color:var(--gold-l); }
        .sidebar .admin-badge { font-size:10px; font-weight:700; background:var(--gold); color:#3A2A00; padding:3px 9px; border-radius:999px; margin-left:4px; }
        .sidebar nav { flex:1; padding:14px 10px; display:flex; flex-direction:column; gap:4px; }
        .sidebar nav a { display:flex; align-items:center; gap:10px; padding:11px 12px; border-radius:12px; color:rgba(255,255,255,.75); font-size:14px; font-weight:600; text-decoration:none; transition:background .12s; }
        .sidebar nav a:hover { background:rgba(255,255,255,.1); color:#fff; }
        .sidebar nav a.active { background:rgba(255,255,255,.18); color:#fff; }
        .sidebar nav a .ico { font-size:18px; width:24px; text-align:center; }
        .sidebar .logout-area { padding:14px 10px; border-top:1px solid rgba(255,255,255,.12); }
        .sidebar .logout-area form button { display:flex; align-items:center; gap:10px; padding:11px 12px; border-radius:12px; color:rgba(255,255,255,.7); font-size:14px; font-weight:600; background:none; border:none; cursor:pointer; width:100%; }
        .sidebar .logout-area form button:hover { background:rgba(255,255,255,.1); color:#fff; }

        /* Mobile topbar (hamburger) */
        .topbar-mobile { display:none; align-items:center; justify-content:space-between; padding:14px 18px; background:var(--grad-blue); color:#fff; position:sticky; top:0; z-index:20; }
        .topbar-mobile .brand { display:flex; align-items:center; gap:9px; font-weight:800; font-size:16px; color:#fff; text-decoration:none; }
        .topbar-mobile .brand img { height:28px; width:28px; background:#fff; border-radius:8px; padding:3px; }
        .topbar-mobile .menu-btn { background:rgba(255,255,255,.14); border:1.5px solid rgba(255,255,255,.3); color:#fff; padding:8px 12px; border-radius:10px; font-size:20px; cursor:pointer; line-height:1; }

        /* Mobile drawer */
        .drawer-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:25; }
        .drawer { position:fixed; top:0; left:0; width:220px; height:100vh; background:var(--grad-blue); z-index:30; transform:translateX(-100%); transition:transform .22s; display:flex; flex-direction:column; }
        .drawer.open { transform:translateX(0); }
        .drawer-overlay.open { display:block; }

        /* Main content */
        .main { margin-left:220px; flex:1; display:flex; flex-direction:column; min-height:100vh; }
        .main-header { background:#fff; border-bottom:1px solid var(--line); padding:16px 28px; display:flex; align-items:center; justify-content:space-between; }
        .main-header h1 { font-size:20px; font-weight:800; letter-spacing:-.4px; }
        .main-header .user-chip { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:var(--muted); }
        .main-content { flex:1; padding:24px 28px; max-width:1100px; }

        /* Components */
        .flash { padding:13px 16px; border-radius:13px; margin-bottom:16px; font-size:14px; font-weight:600; }
        .flash.ok { background:var(--ok-bg); border:1px solid #B6E3C7; color:#157A43; }
        .flash.err { background:var(--danger-bg); border:1px solid #F4C2CB; color:var(--danger); }

        .stat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:14px; margin-bottom:24px; }
        .stat-card { background:#fff; border:1px solid var(--line); border-radius:18px; padding:18px; box-shadow:var(--shadow); }
        .stat-card .ico { font-size:26px; margin-bottom:8px; }
        .stat-card .n { font-size:32px; font-weight:800; letter-spacing:-1px; color:var(--text); line-height:1; }
        .stat-card .n.blue { color:var(--blue); }
        .stat-card .n.gold { color:var(--gold-d); }
        .stat-card .n.ok { color:var(--ok); }
        .stat-card .lbl { font-size:12.5px; color:var(--muted); font-weight:600; margin-top:6px; }
        .stat-card .sub-n { font-size:12px; color:var(--muted); font-weight:600; margin-top:2px; }

        .section-title { font-size:16px; font-weight:800; letter-spacing:-.3px; margin-bottom:12px; margin-top:6px; }

        /* User list */
        .user-list { display:flex; flex-direction:column; gap:10px; }
        .user-row { background:#fff; border:1px solid var(--line); border-radius:16px; padding:16px; box-shadow:var(--shadow); display:flex; align-items:center; gap:14px; }
        .user-row .avatar { width:44px; height:44px; border-radius:14px; background:var(--bg); display:flex; align-items:center; justify-content:center; font-size:20px; flex:none; font-weight:700; }
        .user-row .info { flex:1; min-width:0; }
        .user-row .info .name { font-weight:700; font-size:15px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .user-row .info .meta { font-size:12.5px; color:var(--muted); font-weight:500; margin-top:2px; }
        .user-row .info .merchant-tag { font-size:12px; color:var(--blue-l); font-weight:700; margin-top:3px; }
        .user-row .actions { display:flex; gap:6px; flex:none; }

        /* Badges */
        .badge { display:inline-flex; align-items:center; font-size:11px; padding:4px 10px; border-radius:999px; font-weight:700; white-space:nowrap; }
        .badge.ok { background:var(--ok-bg); color:var(--ok); }
        .badge.off { background:#F0F2F7; color:var(--muted); }
        .badge.blue { background:#E3EDFF; color:var(--blue); }
        .badge.gold { background:#FFF1C9; color:#8A6A00; }

        /* Buttons */
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 16px; font-size:13px; font-weight:700; border:none; border-radius:11px; cursor:pointer; text-decoration:none; font-family:inherit; transition:opacity .1s; }
        .btn:active { opacity:.75; }
        .btn.primary { background:var(--grad-blue); color:#fff; box-shadow:0 4px 12px rgba(13,71,161,.22); }
        .btn.success { background:var(--ok-bg); color:var(--ok); border:1.5px solid #B6E3C7; }
        .btn.muted { background:#F0F2F7; color:var(--muted); border:1.5px solid var(--line); }
        .btn.danger { background:var(--danger-bg); color:var(--danger); border:1.5px solid #F4C2CB; }
        .btn.sm { padding:7px 12px; font-size:12px; border-radius:9px; }

        /* Search form */
        .search-row { display:flex; gap:10px; margin-bottom:18px; }
        .search-row input { flex:1; padding:12px 16px; font-size:14px; border-radius:13px; border:1.5px solid var(--line); background:#fff; color:var(--text); font-family:inherit; font-weight:500; }
        .search-row input:focus { outline:none; border-color:var(--blue-l); box-shadow:0 0 0 3px rgba(30,102,208,.12); }

        /* Pagination */
        .pager { display:flex; gap:6px; justify-content:center; margin-top:20px; }
        .pager a, .pager span { padding:8px 14px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; }
        .pager a { background:#fff; border:1.5px solid var(--line); color:var(--text); }
        .pager a:hover { border-color:var(--blue-l); color:var(--blue-l); }
        .pager span.active { background:var(--grad-blue); color:#fff; border:none; }
        .pager span.dots { background:none; color:var(--muted); }

        /* Empty state */
        .empty { text-align:center; padding:48px 20px; color:var(--muted); }
        .empty .ico { font-size:48px; margin-bottom:12px; }
        .empty p { font-size:14px; font-weight:600; }

        /* Confirm modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:#fff; border-radius:20px; padding:24px; max-width:320px; width:90%; }
        .modal h3 { font-size:17px; font-weight:800; margin-bottom:8px; }
        .modal p { font-size:14px; color:var(--muted); font-weight:500; margin-bottom:20px; }
        .modal .row { display:flex; gap:10px; }
        .modal .row .btn { flex:1; justify-content:center; }

        @media (max-width: 700px) {
            .sidebar { display:none; }
            .topbar-mobile { display:flex; }
            .main { margin-left:0; }
            .main-header { display:none; }
            .main-content { padding:16px; }
            .stat-grid { grid-template-columns:repeat(2,1fr); gap:10px; }
            .user-row { flex-wrap:wrap; }
            .user-row .actions { width:100%; justify-content:flex-end; }
            .drawer { display:flex; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="sa-wrap">

    {{-- Sidebar (desktop) --}}
    <aside class="sidebar">
        <div class="brand">
            <img src="/logo.svg" alt="">
            pelangganku<span class="dot">.</span>
            <span class="admin-badge">ADMIN</span>
        </div>
        <nav>
            <a href="{{ route('superadmin.dashboard') }}" class="{{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                <span class="ico">🏠</span> Dashboard
            </a>
            <a href="{{ route('superadmin.owners') }}" class="{{ request()->routeIs('superadmin.owners') ? 'active' : '' }}">
                <span class="ico">👤</span> Kelola Owner
            </a>
            <a href="{{ route('superadmin.kasir') }}" class="{{ request()->routeIs('superadmin.kasir') ? 'active' : '' }}">
                <span class="ico">🧾</span> Kelola Kasir
            </a>
            <a href="{{ route('superadmin.merchants') }}" class="{{ request()->routeIs('superadmin.merchants') ? 'active' : '' }}">
                <span class="ico">🖥️</span> POS Digital
            </a>
        </nav>
        <div class="logout-area">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"><span class="ico">🚪</span> Keluar</button>
            </form>
        </div>
    </aside>

    {{-- Mobile topbar --}}
    <div class="topbar-mobile">
        <a href="{{ route('superadmin.dashboard') }}" class="brand">
            <img src="/logo.svg" alt=""> pelangganku<span style="color:var(--gold-l)">.</span>
        </a>
        <button class="menu-btn" onclick="openDrawer()">☰</button>
    </div>

    {{-- Mobile drawer --}}
    <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
    <div class="drawer" id="drawer">
        <div class="brand" style="padding:20px 18px 14px; border-bottom:1px solid rgba(255,255,255,.12);">
            <img src="/logo.svg" alt="" style="height:28px;width:28px;background:#fff;border-radius:8px;padding:3px;">
            <span style="font-weight:800;font-size:15px;color:#fff">Super Admin</span>
        </div>
        <nav style="flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:4px;">
            <a href="{{ route('superadmin.dashboard') }}" style="display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:12px;color:rgba(255,255,255,.75);font-size:14px;font-weight:600;text-decoration:none;" onclick="closeDrawer()">🏠 Dashboard</a>
            <a href="{{ route('superadmin.owners') }}" style="display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:12px;color:rgba(255,255,255,.75);font-size:14px;font-weight:600;text-decoration:none;" onclick="closeDrawer()">👤 Kelola Owner</a>
            <a href="{{ route('superadmin.kasir') }}" style="display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:12px;color:rgba(255,255,255,.75);font-size:14px;font-weight:600;text-decoration:none;" onclick="closeDrawer()">🧾 Kelola Kasir</a>
            <a href="{{ route('superadmin.merchants') }}" style="display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:12px;color:rgba(255,255,255,.75);font-size:14px;font-weight:600;text-decoration:none;" onclick="closeDrawer()">🖥️ POS Digital</a>
        </nav>
        <div style="padding:14px 10px;border-top:1px solid rgba(255,255,255,.12);">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:12px;color:rgba(255,255,255,.7);font-size:14px;font-weight:600;background:none;border:none;cursor:pointer;width:100%;">🚪 Keluar</button>
            </form>
        </div>
    </div>

    {{-- Main --}}
    <main class="main">
        <div class="main-header">
            <h1>@yield('page-title', 'Dashboard')</h1>
            <div class="user-chip">
                <span>👑</span>
                <span>{{ auth()->user()->name }}</span>
            </div>
        </div>
        <div class="main-content">
            @if(session('success'))<div class="flash ok">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif
            @yield('content')
        </div>
    </main>
</div>

<script>
function openDrawer() {
    document.getElementById('drawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('open');
}
function closeDrawer() {
    document.getElementById('drawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('open');
}
</script>
@stack('scripts')
</body>
</html>
