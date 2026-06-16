<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0D47A1">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icon.svg">
    <title>@yield('title', 'pelangganku.com')</title>
    <style>
        :root { --blue:#0D47A1; --blue-d:#0a3a85; --blue-l:#1559b8; --gold:#FFC107; --gold-d:#e6a900; --bg:#F5F7FA; --panel:#fff; --line:#e7ecf3; --text:#13294b; --muted:#7587a3; --accent:#0D47A1; --accent-d:#0a3a85; --ok:#2e9e5b; --danger:#dc2626; }
        * { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
        body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .wrap { max-width:480px; margin:0 auto; min-height:100vh; display:flex; flex-direction:column; background:var(--bg); }
        .topbar { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:linear-gradient(135deg,var(--blue) 0%,var(--blue-l) 100%); color:#fff; position:sticky; top:0; z-index:5; }
        .topbar .brand { display:flex; align-items:center; gap:8px; font-weight:700; color:#fff; text-decoration:none; }
        .topbar .brand img { height:26px; width:26px; background:#fff; border-radius:7px; padding:2px; }
        .topbar a, .topbar button { color:#fff; background:none; border:none; font-size:14px; cursor:pointer; text-decoration:none; opacity:.92; }
        .topbar .right { display:flex; align-items:center; gap:14px; }
        .content { flex:1; padding:18px; }
        h1 { font-size:21px; margin-bottom:4px; }
        h2 { font-size:16px; margin:0 0 10px; }
        .sub { color:var(--muted); font-size:14px; margin-bottom:18px; }
        .flash { padding:12px 14px; border-radius:12px; margin-bottom:14px; font-size:14px; }
        .flash.ok { background:#e8f5ee; border:1px solid var(--ok); color:#1d7a45; }
        .flash.err { background:#fdeaea; border:1px solid var(--danger); color:var(--danger); }
        label { display:block; font-size:13px; color:var(--muted); margin:12px 0 6px; }
        input[type=text], input[type=email], input[type=password], input[type=number], input[type=tel], textarea, select {
            width:100%; padding:13px; font-size:16px; border-radius:11px; border:1px solid var(--line); background:#fff; color:var(--text); font-family:inherit; }
        input:focus, textarea:focus { outline:none; border-color:var(--blue); }
        textarea { min-height:70px; resize:vertical; }
        .btn { display:block; width:100%; padding:15px; font-size:16px; font-weight:700; border:none; border-radius:14px; background:var(--blue); color:#fff; cursor:pointer; text-align:center; text-decoration:none; box-shadow:0 4px 12px rgba(13,71,161,.25); }
        .btn:active { background:var(--blue-d); }
        .btn.gold { background:var(--gold); color:#3a2c00; box-shadow:0 4px 12px rgba(255,193,7,.3); }
        .btn.gold:active { background:var(--gold-d); }
        .btn.secondary { background:#fff; border:1px solid var(--line); color:var(--text); box-shadow:none; }
        .btn.danger { background:#fff; border:1px solid var(--danger); color:var(--danger); box-shadow:none; }
        .btn.sm { padding:9px 14px; font-size:13px; width:auto; display:inline-block; border-radius:10px; box-shadow:none; }
        .btn:disabled { opacity:.45; box-shadow:none; }
        .mt { margin-top:14px; }
        .row { display:flex; gap:10px; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:18px; padding:18px; margin-bottom:14px; box-shadow:0 2px 10px rgba(19,41,75,.04); }
        /* Hero biru */
        .hero { background:linear-gradient(135deg,var(--blue) 0%,var(--blue-l) 100%); color:#fff; border-radius:20px; padding:20px; margin-bottom:16px; position:relative; overflow:hidden; box-shadow:0 8px 22px rgba(13,71,161,.25); }
        .hero .label { font-size:13px; opacity:.85; }
        .hero .big { font-size:30px; font-weight:800; margin:2px 0; }
        .hero .coin { color:var(--gold); }
        .hero img.deco { position:absolute; right:-6px; bottom:-10px; width:120px; opacity:.95; }
        /* Tiles menu */
        .tiles { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .tiles a { background:var(--panel); border:1px solid var(--line); border-radius:18px; padding:16px; text-decoration:none; color:var(--text); display:flex; flex-direction:column; gap:8px; box-shadow:0 2px 10px rgba(19,41,75,.04); }
        .tiles .ico { width:46px; height:46px; border-radius:13px; background:linear-gradient(135deg,var(--blue),var(--blue-l)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; }
        .tiles a.gold .ico { background:linear-gradient(135deg,var(--gold),var(--gold-d)); color:#3a2c00; }
        .tiles b { font-size:14px; } .tiles small { color:var(--muted); }
        .muted { color:var(--muted); font-size:13px; }
        /* Numpad */
        .display { font-size:32px; letter-spacing:2px; text-align:center; padding:18px; background:#fff; border:1px solid var(--line); border-radius:14px; min-height:62px; color:var(--text); font-weight:600; }
        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:14px; }
        .key { padding:20px 0; font-size:25px; font-weight:700; background:#fff; border:1px solid var(--line); border-radius:16px; color:var(--text); cursor:pointer; user-select:none; box-shadow:0 2px 6px rgba(19,41,75,.04); }
        .key:active { background:var(--blue); color:#fff; border-color:var(--blue); }
        /* Stamp card */
        .stamps { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin:16px 0; }
        .stamp { aspect-ratio:1; border-radius:50%; border:2px dashed var(--line); display:flex; align-items:center; justify-content:center; font-size:16px; color:var(--muted); position:relative; background:#fbfdff; }
        .stamp.filled { background:linear-gradient(135deg,var(--gold),var(--gold-d)); border:2px solid var(--gold-d); color:#fff; }
        .stamp.milestone { border-color:var(--blue); border-style:solid; }
        .stamp .gift { position:absolute; top:-9px; right:-5px; font-size:13px; }
        .reward-ready { display:flex; align-items:center; gap:10px; background:#fff7e0; border:1px solid var(--gold); color:#8a6d00; padding:12px; border-radius:12px; font-weight:600; margin-bottom:10px; }
        .rwd { display:flex; gap:12px; align-items:center; padding:12px 0; border-top:1px solid var(--line); }
        .rwd:first-of-type { border-top:none; }
        .rwd img, .rwd .ph { width:50px; height:50px; border-radius:12px; object-fit:cover; background:#eef3fb; flex:none; display:flex; align-items:center; justify-content:center; font-size:22px; }
        .rwd .info { flex:1; } .rwd .info b { display:block; }
        .badge { font-size:11px; padding:3px 9px; border-radius:999px; white-space:nowrap; }
        .badge.gold { background:#fff3cd; color:#8a6d00; }
        .badge.grey { background:#eef1f6; color:var(--muted); }
        /* Bottom nav */
        .bottomnav { position:fixed; bottom:0; left:50%; transform:translateX(-50%); width:100%; max-width:480px; background:#fff; border-top:1px solid var(--line); display:flex; justify-content:space-around; align-items:center; padding:9px 6px calc(10px + env(safe-area-inset-bottom)); z-index:30; box-shadow:0 -3px 18px rgba(19,41,75,.07); }
        .bottomnav a { flex:1; display:flex; flex-direction:column; align-items:center; gap:3px; color:var(--muted); font-size:10.5px; font-weight:600; text-decoration:none; }
        .bottomnav a .bi { font-size:21px; line-height:1; }
        .bottomnav a.active { color:var(--blue); }
    </style>
</head>
<body>
    <div class="wrap">
        @auth
        @php($isOwner = auth()->user()->isOwner())
        <div class="topbar">
            <a href="{{ route($isOwner ? 'owner.dashboard' : 'kasir') }}" class="brand"><img src="/logo.svg" alt=""> pelangganku</a>
            <form action="{{ route('logout') }}" method="POST" style="display:inline">
                @csrf
                <button type="submit">Keluar</button>
            </form>
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
            <a href="{{ route('owner.program-outlet') }}" class="{{ request()->routeIs('owner.program-outlet*') ? 'active' : '' }}"><span class="bi">🎯</span>Program</a>
            @if(auth()->user()->merchants()->count() > 1)
            <a href="{{ route('merchant.select') }}" class="{{ request()->routeIs('merchant.select') ? 'active' : '' }}"><span class="bi">🏪</span>Ganti Toko</a>
            @endif
        </nav>
        @endif
        @endauth
    </div>
</body>
</html>
