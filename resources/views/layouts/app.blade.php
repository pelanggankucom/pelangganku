<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0A2A5C">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icon.svg">
    <title>@yield('title', 'pelangganku.com')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy:#0A2A5C; --blue:#0D47A1; --blue-l:#1E66D0; --blue-xl:#3B82E8;
            --gold:#F6B931; --gold-l:#FFCF5C; --gold-d:#D99812;
            --bg:#EEF2F9; --panel:#fff; --line:#E4EAF3; --text:#0F2444; --muted:#73839E;
            --ok:#1E9E5A; --danger:#E0344B;
            --grad-blue:linear-gradient(135deg,#0A2A5C 0%,#0D47A1 55%,#1E66D0 100%);
            --grad-gold:linear-gradient(135deg,#FFCF5C 0%,#F6B931 55%,#D99812 100%);
            --shadow:0 6px 22px rgba(15,36,68,.07);
        }
        * { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
        body { font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; -webkit-font-smoothing:antialiased; }
        .wrap { max-width:480px; margin:0 auto; min-height:100vh; display:flex; flex-direction:column; background:var(--bg); }
        /* Topbar */
        .topbar { display:flex; align-items:center; justify-content:space-between; padding:15px 18px; background:var(--grad-blue); color:#fff; position:sticky; top:0; z-index:5; }
        .topbar .brand { display:flex; align-items:center; gap:9px; font-weight:800; font-size:17px; color:#fff; text-decoration:none; letter-spacing:-.3px; }
        .topbar .brand img { height:30px; width:30px; background:#fff; border-radius:9px; padding:3px; }
        .topbar .brand .dot { color:var(--gold-l); }
        .content { flex:1; padding:18px; }
        h1 { font-size:23px; font-weight:800; margin-bottom:4px; letter-spacing:-.5px; }
        h2 { font-size:17px; font-weight:700; margin:0 0 12px; letter-spacing:-.3px; }
        .sub { color:var(--muted); font-size:14px; margin-bottom:18px; }
        .muted { color:var(--muted); font-size:13px; }
        /* Flash */
        .flash { padding:13px 15px; border-radius:13px; margin-bottom:14px; font-size:14px; font-weight:600; }
        .flash.ok { background:#E4F6EC; border:1px solid #B6E3C7; color:#157A43; }
        .flash.err { background:#FCE8EB; border:1px solid #F4C2CB; color:var(--danger); }
        /* Form */
        label { display:block; font-size:13px; color:var(--muted); font-weight:600; margin:14px 0 6px; }
        input[type=text], input[type=email], input[type=password], input[type=number], input[type=tel], input[type=date], input[type=file], textarea, select {
            width:100%; padding:14px; font-size:16px; border-radius:13px; border:1.5px solid var(--line); background:#fff; color:var(--text); font-family:inherit; font-weight:500; }
        input:focus, textarea:focus, select:focus { outline:none; border-color:var(--blue-l); box-shadow:0 0 0 3px rgba(30,102,208,.12); }
        textarea { min-height:74px; resize:vertical; }
        /* Buttons */
        .btn { display:block; width:100%; padding:16px; font-size:16px; font-weight:700; border:none; border-radius:15px; background:var(--grad-blue); color:#fff; cursor:pointer; text-align:center; text-decoration:none; box-shadow:0 6px 16px rgba(13,71,161,.28); transition:transform .08s; }
        .btn:active { transform:scale(.98); }
        .btn.gold { background:var(--grad-gold); color:#3A2A00; box-shadow:0 6px 16px rgba(246,185,49,.34); }
        .btn.secondary { background:#fff; border:1.5px solid var(--line); color:var(--text); box-shadow:none; }
        .btn.danger { background:#fff; border:1.5px solid var(--danger); color:var(--danger); box-shadow:none; }
        .btn.sm { padding:10px 16px; font-size:14px; width:auto; display:inline-block; border-radius:11px; box-shadow:none; }
        .btn:disabled { opacity:.45; box-shadow:none; }
        .mt { margin-top:14px; }
        .row { display:flex; gap:10px; }
        /* Cards */
        .card { background:var(--panel); border:1px solid var(--line); border-radius:20px; padding:18px; margin-bottom:14px; box-shadow:var(--shadow); }
        /* Hero */
        .hero { background:var(--grad-blue); color:#fff; border-radius:24px; padding:22px; margin-bottom:18px; position:relative; overflow:hidden; box-shadow:0 12px 30px rgba(10,42,92,.30); }
        .hero::after { content:""; position:absolute; top:-60px; right:-40px; width:180px; height:180px; border-radius:50%; background:radial-gradient(circle,rgba(246,185,49,.35),transparent 70%); }
        .hero .label { font-size:13px; opacity:.88; font-weight:500; position:relative; z-index:1; }
        .hero .big { font-size:30px; font-weight:800; margin:3px 0; letter-spacing:-.6px; position:relative; z-index:1; }
        .hero .coin { color:var(--gold-l); }
        .hero img.deco { position:absolute; right:-6px; bottom:-10px; width:118px; opacity:.95; z-index:0; }
        /* Numpad */
        .display { font-size:32px; letter-spacing:2px; text-align:center; padding:20px; background:#fff; border:1.5px solid var(--line); border-radius:16px; min-height:64px; color:var(--text); font-weight:700; }
        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:14px; }
        .key { padding:20px 0; font-size:26px; font-weight:700; background:#fff; border:1.5px solid var(--line); border-radius:16px; color:var(--text); cursor:pointer; user-select:none; box-shadow:0 2px 6px rgba(15,36,68,.04); }
        .key:active { background:var(--grad-blue); color:#fff; border-color:transparent; }
        /* Stamp card */
        .stamps { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin:16px 0; }
        .stamp { aspect-ratio:1; border-radius:50%; border:2px dashed var(--line); display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; color:var(--muted); position:relative; background:#FAFCFF; }
        .stamp.filled { background:var(--grad-gold); border:2px solid var(--gold-d); color:#fff; }
        .stamp.milestone { border-color:var(--blue-l); border-style:solid; font-size:30px; line-height:1; }
        .stamp .gift { position:absolute; top:-9px; right:-5px; font-size:13px; }
        .reward-ready { display:flex; align-items:center; gap:10px; background:#FFF6DF; border:1px solid var(--gold); color:#8A6A00; padding:13px; border-radius:13px; font-weight:700; margin-bottom:12px; }
        .rwd { display:flex; gap:12px; align-items:center; padding:12px 0; border-top:1px solid var(--line); }
        .rwd:first-of-type { border-top:none; }
        .rwd img, .rwd .ph { width:52px; height:52px; border-radius:13px; object-fit:cover; background:#EEF3FB; flex:none; display:flex; align-items:center; justify-content:center; font-size:24px; }
        .rwd .info { flex:1; } .rwd .info b { display:block; font-weight:700; }
        .badge { font-size:11px; padding:4px 10px; border-radius:999px; white-space:nowrap; font-weight:700; }
        .badge.gold { background:#FFF1C9; color:#8A6A00; }
        .badge.grey { background:#EEF1F6; color:var(--muted); }
        /* Bottom nav — 3 tombol besar */
        .bottomnav { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:#fff; border-top:1px solid var(--line); display:flex; justify-content:space-around; align-items:center; padding:10px 6px calc(11px + env(safe-area-inset-bottom)); z-index:30; box-shadow:0 -4px 20px rgba(15,36,68,.08); }
        .bottomnav a { flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; color:var(--muted); font-size:11px; font-weight:700; text-decoration:none; }
        .bottomnav a .bi { font-size:22px; line-height:1; padding:5px 16px; border-radius:14px; transition:all .15s; }
        .bottomnav a.active { color:var(--blue); }
        .bottomnav a.active .bi { background:#E3EDFF; transform:translateY(-1px); box-shadow:0 3px 10px rgba(13,71,161,.16); }
    </style>
</head>
<body>
    <div class="wrap">
        @auth
        @php($isOwner = auth()->user()->isOwner())
        <div class="topbar">
            <a href="{{ route($isOwner ? 'owner.dashboard' : 'kasir') }}" class="brand"><img src="/logo.svg" alt=""> pelangganku<span class="dot">.</span></a>
        </div>
        @endauth

        <div class="content"@auth @if($isOwner) style="padding-bottom:96px"@endif @endauth>
            @if(session('success'))<div class="flash ok">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif
            @yield('content')
        </div>

        @auth
        @if($isOwner)
        <nav class="bottomnav">
            <a href="{{ route('owner.dashboard') }}" class="{{ request()->routeIs('owner.dashboard') ? 'active' : '' }}"><span class="bi">🏠</span>Beranda</a>
            <a href="{{ route('kasir') }}" class="{{ request()->routeIs('kasir*') ? 'active' : '' }}"><span class="bi">🧾</span>Kasir</a>
            <a href="{{ route('owner.customers') }}" class="{{ request()->routeIs('owner.customers') ? 'active' : '' }}"><span class="bi">👥</span>Pelanggan</a>
            <a href="{{ route('owner.settings') }}" class="{{ request()->routeIs('owner.settings') || request()->routeIs('owner.profile') || request()->routeIs('owner.store') || request()->routeIs('owner.branches') || request()->routeIs('owner.program') ? 'active' : '' }}"><span class="bi">⚙️</span>Atur</a>
        </nav>
        @endif
        @endauth
    </div>
</body>
</html>
