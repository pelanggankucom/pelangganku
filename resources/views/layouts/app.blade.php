<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#16a34a">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>@yield('title', 'pelangganku.com')</title>
    <style>
        :root { --bg:#f3f7f4; --panel:#fff; --line:#e2e8e3; --accent:#16a34a; --accent-d:#15803d; --text:#15281d; --muted:#6b7c70; --ok:#16a34a; --danger:#dc2626; }
        * { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
        body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .wrap { max-width:480px; margin:0 auto; min-height:100vh; display:flex; flex-direction:column; background:var(--bg); }
        .topbar { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:var(--panel); border-bottom:1px solid var(--line); position:sticky; top:0; z-index:5; }
        .topbar .brand { font-weight:700; color:var(--text); text-decoration:none; }
        .topbar .brand span { color:var(--accent); }
        .topbar a, .topbar button { color:var(--muted); background:none; border:none; font-size:14px; cursor:pointer; text-decoration:none; }
        .topbar .right { display:flex; align-items:center; gap:14px; }
        .content { flex:1; padding:18px; }
        h1 { font-size:21px; margin-bottom:4px; }
        h2 { font-size:16px; margin:0 0 10px; }
        .sub { color:var(--muted); font-size:14px; margin-bottom:18px; }
        .flash { padding:12px 14px; border-radius:10px; margin-bottom:14px; font-size:14px; }
        .flash.ok { background:#e7f6ec; border:1px solid var(--accent); color:var(--accent-d); }
        .flash.err { background:#fdeaea; border:1px solid var(--danger); color:var(--danger); }
        label { display:block; font-size:13px; color:var(--muted); margin:12px 0 6px; }
        input[type=text], input[type=email], input[type=password], input[type=number], input[type=tel], textarea, select {
            width:100%; padding:13px; font-size:16px; border-radius:10px; border:1px solid var(--line); background:#fff; color:var(--text); font-family:inherit; }
        textarea { min-height:70px; resize:vertical; }
        .btn { display:block; width:100%; padding:15px; font-size:16px; font-weight:600; border:none; border-radius:12px; background:var(--accent); color:#fff; cursor:pointer; text-align:center; text-decoration:none; }
        .btn:active { background:var(--accent-d); }
        .btn.secondary { background:#fff; border:1px solid var(--line); color:var(--text); }
        .btn.danger { background:#fff; border:1px solid var(--danger); color:var(--danger); }
        .btn.sm { padding:9px 12px; font-size:13px; width:auto; display:inline-block; }
        .btn:disabled { opacity:.45; }
        .mt { margin-top:14px; }
        .row { display:flex; gap:10px; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:18px; margin-bottom:14px; box-shadow:0 1px 2px rgba(0,0,0,.03); }
        .menu a { display:flex; align-items:center; justify-content:space-between; padding:16px 18px; background:var(--panel); border:1px solid var(--line); border-radius:14px; margin-bottom:10px; text-decoration:none; color:var(--text); font-weight:600; }
        .menu a span.ico { color:var(--accent); margin-right:10px; }
        .muted { color:var(--muted); font-size:13px; }
        /* Numpad */
        .display { font-size:32px; letter-spacing:2px; text-align:center; padding:18px; background:#fff; border:1px solid var(--line); border-radius:12px; min-height:62px; color:var(--text); }
        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:14px; }
        .key { padding:20px 0; font-size:25px; font-weight:600; background:#fff; border:1px solid var(--line); border-radius:14px; color:var(--text); cursor:pointer; user-select:none; }
        .key:active { background:var(--accent); color:#fff; border-color:var(--accent); }
        /* Stamp card */
        .stamps { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin:16px 0; }
        .stamp { aspect-ratio:1; border-radius:50%; border:2px dashed var(--line); display:flex; align-items:center; justify-content:center; font-size:16px; color:var(--muted); position:relative; }
        .stamp.filled { background:var(--accent); border:2px solid var(--accent); color:#fff; }
        .stamp.milestone { border-color:var(--accent); border-style:solid; }
        .stamp .gift { position:absolute; top:-8px; right:-4px; font-size:13px; }
        .reward-ready { background:#e7f6ec; border:1px solid var(--accent); color:var(--accent-d); padding:10px; border-radius:10px; text-align:center; font-weight:600; margin-bottom:8px; }
        .rwd { display:flex; gap:12px; align-items:center; padding:10px 0; border-top:1px solid var(--line); }
        .rwd:first-child { border-top:none; }
        .rwd img, .rwd .ph { width:48px; height:48px; border-radius:10px; object-fit:cover; background:#eef2ee; flex:none; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .rwd .info { flex:1; }
        .rwd .info b { display:block; }
        .badge { font-size:11px; padding:2px 8px; border-radius:999px; }
        .badge.green { background:#e7f6ec; color:var(--accent-d); }
        .badge.grey { background:#eef0ee; color:var(--muted); }
    </style>
</head>
<body>
    <div class="wrap">
        @auth
        <div class="topbar">
            <a href="{{ route('kasir') }}" class="brand"><span>pelangganku</span>.com</a>
            <div class="right">
                @if(auth()->user()->isOwner())
                    <a href="{{ route('owner.dashboard') }}">⚙ Owner</a>
                @endif
                <form action="{{ route('logout') }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit">Keluar</button>
                </form>
            </div>
        </div>
        @endauth

        <div class="content">
            @if(session('success'))<div class="flash ok">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="flash err">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif
            @yield('content')
        </div>
    </div>
</body>
</html>
