<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1b1b18">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>@yield('title', 'pelangganku.com')</title>
    <style>
        :root { --bg:#1b1b18; --panel:#26261f; --line:#3a3a32; --accent:#f53003; --text:#fff; --muted:#a1a09a; --ok:#1e9e54; }
        * { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
        body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .wrap { max-width:480px; margin:0 auto; min-height:100vh; display:flex; flex-direction:column; }
        .topbar { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--line); }
        .topbar .brand { font-weight:700; }
        .topbar .brand span { color:var(--accent); }
        .topbar a, .topbar button { color:var(--muted); background:none; border:none; font-size:14px; cursor:pointer; text-decoration:none; }
        .content { flex:1; padding:18px; }
        h1 { font-size:22px; margin-bottom:4px; }
        .sub { color:var(--muted); font-size:14px; margin-bottom:18px; }
        .flash { padding:12px 14px; border-radius:10px; margin-bottom:14px; font-size:14px; }
        .flash.ok { background:rgba(30,158,84,.15); border:1px solid var(--ok); color:#5fe39a; }
        .flash.err { background:rgba(245,48,3,.12); border:1px solid var(--accent); color:#ff7a5c; }
        label { display:block; font-size:13px; color:var(--muted); margin:12px 0 6px; }
        input[type=text], input[type=email], input[type=password], input[type=number], input[type=tel] {
            width:100%; padding:14px; font-size:18px; border-radius:10px; border:1px solid var(--line); background:var(--panel); color:var(--text); }
        .btn { display:block; width:100%; padding:16px; font-size:17px; font-weight:600; border:none; border-radius:12px; background:var(--accent); color:#fff; cursor:pointer; text-align:center; text-decoration:none; }
        .btn.secondary { background:var(--panel); border:1px solid var(--line); }
        .btn:disabled { opacity:.5; }
        .mt { margin-top:14px; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:18px; }
        /* Numpad */
        .display { font-size:34px; letter-spacing:2px; text-align:center; padding:18px; background:var(--panel); border:1px solid var(--line); border-radius:12px; min-height:64px; }
        .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:14px; }
        .key { padding:22px 0; font-size:26px; font-weight:600; background:var(--panel); border:1px solid var(--line); border-radius:14px; color:var(--text); cursor:pointer; user-select:none; }
        .key:active { background:var(--accent); }
        /* Stamp grid */
        .stamps { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin:16px 0; }
        .stamp { aspect-ratio:1; border-radius:50%; border:2px solid var(--line); display:flex; align-items:center; justify-content:center; font-size:18px; color:var(--muted); }
        .stamp.filled { background:var(--accent); border-color:var(--accent); color:#fff; }
        .reward-ready { background:rgba(245,48,3,.15); border:1px solid var(--accent); color:#ff7a5c; padding:10px; border-radius:10px; text-align:center; font-weight:600; margin-bottom:8px; }
    </style>
</head>
<body>
    <div class="wrap">
        @auth
        <div class="topbar">
            <a href="{{ route('kasir') }}" class="brand"><span>pelangganku</span>.com</a>
            <div>
                @if(auth()->user()->isOwner())
                    <a href="{{ route('owner.settings') }}">⚙ Pengaturan</a>
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
