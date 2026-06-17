<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a2a5c">
    <link rel="icon" href="/icon.svg">
    <title>pelangganku.com — Stempel Loyalti Digital untuk Bisnismu</title>
    <meta name="description" content="Platform loyalti digital berbasis nomor HP. Ubah pembeli biasa menjadi pelanggan setia.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0a2a5c; --blue:#0D47A1; --blue-l:#1559b8; --gold:#FFC107; --gold-l:#FFD86B; --gold-d:#C99700; --ink:#11233f; --muted:#5f6b82; --line:#e9eef5; }
        * { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { font-family:'Plus Jakarta Sans',-apple-system,sans-serif; color:var(--ink); background:#fff; line-height:1.65; -webkit-font-smoothing:antialiased; }
        .wrap { max-width:1120px; margin:0 auto; padding:0 22px; }
        a { text-decoration:none; }
        .serif { font-family:'Playfair Display',serif; }
        .gold-text { background:linear-gradient(120deg,#FFE39A,var(--gold),var(--gold-d)); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .btn { display:inline-flex; align-items:center; gap:8px; padding:15px 30px; border-radius:13px; font-weight:700; font-size:15px; letter-spacing:.2px; transition:transform .15s, box-shadow .15s; }
        .btn:hover { transform:translateY(-2px); }
        .btn-gold { background:linear-gradient(135deg,var(--gold-l),var(--gold)); color:#3a2c00; box-shadow:0 10px 28px rgba(255,193,7,.32); }
        .btn-ghost { background:rgba(255,255,255,.06); color:#fff; border:1px solid rgba(255,214,107,.45); }
        .btn-outline { background:transparent; color:var(--blue); border:1.5px solid #cdd9ea; }
        .eyebrow { display:inline-flex; align-items:center; gap:10px; color:var(--gold-d); font-weight:700; font-size:12.5px; letter-spacing:2.5px; text-transform:uppercase; }
        .eyebrow::before { content:""; width:26px; height:2px; background:linear-gradient(90deg,var(--gold),var(--gold-d)); border-radius:2px; }
        .eyebrow.center::after { content:""; width:26px; height:2px; background:linear-gradient(90deg,var(--gold-d),var(--gold)); border-radius:2px; }
        svg.ic { width:24px; height:24px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }

        nav { position:sticky; top:0; z-index:20; background:rgba(255,255,255,.85); backdrop-filter:blur(12px); border-bottom:1px solid var(--line); }
        nav .wrap { display:flex; align-items:center; justify-content:space-between; height:70px; }
        nav .brand { display:flex; align-items:center; gap:10px; font-weight:800; font-size:19px; color:var(--blue); letter-spacing:-.3px; }
        nav .brand img { width:34px; height:34px; }

        .hero { position:relative; overflow:hidden; color:#fff; background:radial-gradient(1100px 520px at 80% -10%, rgba(255,193,7,.16), transparent 55%), linear-gradient(155deg,#081f44 0%,var(--navy) 45%,var(--blue) 100%); padding:70px 0 78px; }
        .hero::after { content:""; position:absolute; left:0; right:0; bottom:0; height:3px; background:linear-gradient(90deg,transparent,var(--gold),transparent); }
        .hero .wrap { display:grid; grid-template-columns:1.05fr .95fr; gap:34px; align-items:center; }
        .hero .pill { display:inline-flex; gap:8px; align-items:center; background:rgba(255,193,7,.12); color:var(--gold-l); border:1px solid rgba(255,193,7,.4); padding:7px 16px; border-radius:999px; font-size:12px; font-weight:600; letter-spacing:1.5px; margin-bottom:22px; }
        .hero h1 { font-size:50px; line-height:1.1; font-weight:800; letter-spacing:-1px; margin-bottom:20px; }
        .hero p.lead { font-size:18.5px; color:#c6d6ef; margin-bottom:30px; max-width:540px; line-height:1.7; }
        .hero .cta { display:flex; gap:14px; flex-wrap:wrap; }
        .hero .trust { margin-top:26px; display:flex; gap:20px; flex-wrap:wrap; font-size:13.5px; color:#9fb4d6; }
        .hero .trust span { display:inline-flex; align-items:center; gap:7px; }
        .hero .trust svg { width:17px; height:17px; stroke:var(--gold); }
        .hero .phone { text-align:center; }
        .hero .phone img { width:265px; max-width:82%; filter:drop-shadow(0 30px 60px rgba(0,0,0,.45)); }

        section { padding:80px 0; }
        .center { text-align:center; }
        h2.title { font-size:38px; font-weight:800; letter-spacing:-.6px; line-height:1.18; margin:14px 0 14px; }
        .sec-sub { color:var(--muted); font-size:17px; max-width:640px; margin:0 auto 46px; }
        .alt { background:linear-gradient(180deg,#f7f9fc,#eef3fb); }

        .grid { display:grid; gap:20px; }
        .g3 { grid-template-columns:repeat(3,1fr); }
        .card { background:#fff; border:1px solid var(--line); border-radius:20px; padding:30px 26px; transition:transform .18s, box-shadow .18s, border-color .18s; }
        .card:hover { transform:translateY(-4px); box-shadow:0 18px 40px rgba(13,71,161,.10); border-color:#dbe6f4; }
        .ico { width:56px; height:56px; border-radius:16px; display:flex; align-items:center; justify-content:center; margin-bottom:18px; background:linear-gradient(135deg,#0D47A1,#1559b8); color:#fff; box-shadow:0 8px 20px rgba(13,71,161,.2); }
        .ico.gold { background:linear-gradient(135deg,var(--gold-l),var(--gold)); color:#5a4300; box-shadow:0 8px 20px rgba(255,193,7,.28); }
        .ico svg { width:26px; height:26px; }
        .card h3 { font-size:18px; font-weight:700; margin-bottom:8px; letter-spacing:-.2px; }
        .card p { color:var(--muted); font-size:14.5px; }

        .compare { display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-top:30px; }
        .col { border-radius:22px; padding:32px; }
        .col.bad { background:#fff; border:1px solid #f0dede; }
        .col.good { position:relative; overflow:hidden; color:#fff; background:radial-gradient(600px 300px at 100% 0,rgba(255,193,7,.16),transparent 60%),linear-gradient(155deg,var(--navy),var(--blue)); border:1px solid rgba(255,193,7,.3); }
        .col h3 { font-size:19px; margin-bottom:18px; display:flex; align-items:center; gap:10px; }
        .col ul { list-style:none; }
        .col li { padding:12px 0 12px 34px; position:relative; font-size:15px; border-top:1px solid var(--line); }
        .col.good li { border-top:1px solid rgba(255,255,255,.12); color:#e7eefb; }
        .col li:first-child { border-top:none; }
        .col li svg { position:absolute; left:0; top:12px; width:20px; height:20px; }
        .col.bad li svg { stroke:#d98a8a; }
        .col.good li svg { stroke:var(--gold); }
        .tagpill { font-size:11px; font-weight:700; letter-spacing:1px; padding:4px 11px; border-radius:999px; }
        .tagpill.b { background:#fbeaea; color:#b04545; }
        .tagpill.g { background:rgba(255,193,7,.18); color:var(--gold-l); }

        .metrics { display:grid; grid-template-columns:repeat(3,1fr); gap:22px; }
        .metric { background:#fff; border:1px solid var(--line); border-radius:20px; padding:34px 26px; text-align:center; position:relative; }
        .metric::before { content:""; position:absolute; top:0; left:50%; transform:translateX(-50%); width:50px; height:3px; background:linear-gradient(90deg,var(--gold-l),var(--gold-d)); border-radius:0 0 4px 4px; }
        .metric .num { font-size:44px; font-weight:800; line-height:1; letter-spacing:-1px; }
        .metric .num.serif { font-family:'Playfair Display',serif; }
        .metric p { color:var(--muted); font-size:14.5px; margin-top:12px; }

        .steps { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; position:relative; }
        .step { text-align:center; padding:8px; }
        .step .n { width:54px; height:54px; border-radius:50%; background:linear-gradient(135deg,var(--gold-l),var(--gold)); color:#3a2c00; font-weight:800; font-size:20px; display:flex; align-items:center; justify-content:center; margin:0 auto 18px; box-shadow:0 10px 24px rgba(255,193,7,.3); }
        .step h3 { font-size:18px; margin-bottom:8px; }
        .step p { color:var(--muted); font-size:14.5px; }

        .final { position:relative; overflow:hidden; color:#fff; border-radius:28px; padding:62px 30px; text-align:center; background:radial-gradient(700px 360px at 50% -20%,rgba(255,193,7,.18),transparent 60%),linear-gradient(155deg,var(--navy),var(--blue)); border:1px solid rgba(255,193,7,.25); }
        .final h2 { font-size:34px; font-weight:800; letter-spacing:-.5px; margin-bottom:14px; }
        .final p { color:#c6d6ef; margin-bottom:28px; font-size:17px; }

        footer { background:var(--navy); color:#9fb4d6; padding:44px 0; text-align:center; font-size:13.5px; }
        footer .brand { display:inline-flex; align-items:center; gap:9px; font-weight:800; color:#fff; font-size:18px; margin-bottom:10px; }
        footer .brand img { width:30px; height:30px; background:#fff; border-radius:8px; padding:3px; }
        footer .tag { color:var(--gold); letter-spacing:2px; font-size:12px; font-weight:600; }

        @media(max-width:780px){
            .hero .wrap,.g3,.compare,.metrics,.steps { grid-template-columns:1fr; }
            .hero h1 { font-size:36px; } h2.title { font-size:28px; }
            .hero .phone { order:-1; margin-bottom:8px; } section { padding:56px 0; }
        }
    </style>
</head>
<body>

    <nav>
        <div class="wrap">
            <div class="brand"><img src="/logo.svg" alt=""> pelangganku</div>
            <div style="display:flex; gap:10px; align-items:center">
                <a href="/member/masuk" class="btn btn-gold" style="padding:11px 20px">Cek Poin Saya</a>
                <a href="/login" class="btn btn-outline" style="padding:11px 20px">Masuk Owner</a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="wrap">
            <div>
                <span class="pill">LOYALTY THAT MATTERS</span>
                <h1 class="serif">Ubah pembeli biasa menjadi <span class="gold-text">pelanggan setia</span></h1>
                <p class="lead">Platform stempel loyalti digital berbasis nomor HP. Tanpa scan, tanpa kartu hilang — pelanggan punya alasan untuk kembali, lagi dan lagi.</p>
                <div class="cta">
                    <a href="/login" class="btn btn-gold">Coba Gratis Sekarang
                        <svg class="ic" style="width:18px;height:18px"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                    <a href="#cara" class="btn btn-ghost">Lihat Cara Kerja</a>
                </div>
                <div class="trust">
                    <span><svg class="ic"><path d="M5 12l4 4 10-10"/></svg> Tanpa aplikasi untuk pelanggan</span>
                    <span><svg class="ic"><path d="M5 12l4 4 10-10"/></svg> Cukup nomor HP</span>
                </div>
            </div>
            <div class="phone"><img src="/illustration-phone.svg" alt="Aplikasi pelangganku"></div>
        </div>
    </header>

    <section>
        <div class="wrap center">
            <div class="eyebrow center">Masih pakai kartu kertas?</div>
            <h2 class="title serif">Kartu stempel manual diam-diam<br>merugikan bisnis Anda</h2>
            <p class="sec-sub">Detail kecil ini membuat pelanggan tidak kembali — sering kali tanpa Anda sadari.</p>
            <div class="grid g3">
                <div class="card">
                    <div class="ico"><svg class="ic"><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 6l3-3h12l3 3M9 11h6"/></svg></div>
                    <h3>Kartu hilang &amp; terlupa</h3>
                    <p>Progres hangus saat kartu hilang. Pelanggan kecewa lalu berhenti datang.</p>
                </div>
                <div class="card">
                    <div class="ico"><svg class="ic"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></div>
                    <h3>Mudah dipalsukan</h3>
                    <p>Stempel kertas gampang ditiru. Hadiah bocor, margin keuntungan tergerus.</p>
                </div>
                <div class="card">
                    <div class="ico"><svg class="ic"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a6 6 0 0 1 11 0M18 7v4M18 14v.5"/></svg></div>
                    <h3>Anda tak mengenal pelanggan</h3>
                    <p>Tanpa data: siapa yang sering datang, siapa yang loyal, siapa yang menghilang.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="alt" id="fitur">
        <div class="wrap center">
            <div class="eyebrow center">Kenapa pelangganku</div>
            <h2 class="title serif">Dirancang untuk membangun loyalitas</h2>
            <p class="sec-sub">Cepat dipakai kasir, menyenangkan bagi pelanggan, dan berdampak bagi bisnis.</p>
            <div class="grid g3">
                <div class="card"><div class="ico gold"><svg class="ic"><rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M11 18h2"/></svg></div><h3>Cukup Nomor HP</h3><p>Tanpa scan QR, tanpa aplikasi untuk pelanggan. Sebut nomor, selesai.</p></div>
                <div class="card"><div class="ico gold"><svg class="ic"><path d="M13 3 4 14h6l-1 7 9-11h-6l1-7z"/></svg></div><h3>Super Cepat di Kasir</h3><p>Numpad besar, dua ketukan beres. Antrean tetap lancar di jam sibuk.</p></div>
                <div class="card"><div class="ico gold"><svg class="ic"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M3 12h18M12 8v13M12 8S9 3 7 4.5 8.5 8 12 8zm0 0s3-5 5-3.5S15.5 8 12 8z"/></svg></div><h3>Hadiah Fleksibel</h3><p>Atur hadiah di stempel ke-5, ke-10, dan seterusnya — lengkap nama, gambar &amp; ketentuan.</p></div>
                <div class="card"><div class="ico gold"><svg class="ic"><path d="M3 9l2-5h14l2 5M5 9v11h14V9M9 20v-6h6v6"/></svg></div><h3>Kelola Banyak Outlet</h3><p>Semua cabang dalam satu sistem. Pelanggan bisa mengumpulkan stempel di mana saja.</p></div>
                <div class="card"><div class="ico gold"><svg class="ic"><path d="M4 20V11M10 20V5M16 20v-7M3 20h18"/></svg></div><h3>Data Jadi Milik Anda</h3><p>Database pelanggan tumbuh otomatis. Kenali siapa yang loyal dan siapa yang menjauh.</p></div>
                <div class="card"><div class="ico gold"><svg class="ic"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg></div><h3>Aman &amp; Anti-Curang</h3><p>Setiap stempel tercatat digital. Tidak bisa dipalsukan seperti kartu kertas.</p></div>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="center"><div class="eyebrow center">Manual vs Digital</div><h2 class="title serif">Perbedaannya terasa sejak hari pertama</h2></div>
            <div class="compare">
                <div class="col bad">
                    <h3><span class="tagpill b">CARA LAMA</span> Kartu Stempel Kertas</h3>
                    <ul>
                        <li><svg class="ic"><path d="M6 6l12 12M18 6 6 18"/></svg> Sering hilang &amp; terlupa</li>
                        <li><svg class="ic"><path d="M6 6l12 12M18 6 6 18"/></svg> Mudah dipalsukan</li>
                        <li><svg class="ic"><path d="M6 6l12 12M18 6 6 18"/></svg> Tidak ada data pelanggan</li>
                        <li><svg class="ic"><path d="M6 6l12 12M18 6 6 18"/></svg> Tak tahu siapa yang loyal</li>
                        <li><svg class="ic"><path d="M6 6l12 12M18 6 6 18"/></svg> Repot &amp; boros cetak kartu</li>
                    </ul>
                </div>
                <div class="col good">
                    <h3><span class="tagpill g">PELANGGANKU</span> Stempel Digital</h3>
                    <ul>
                        <li><svg class="ic"><path d="M5 12l4 4 10-10"/></svg> Tersimpan aman di nomor HP</li>
                        <li><svg class="ic"><path d="M5 12l4 4 10-10"/></svg> Digital &amp; anti-palsu</li>
                        <li><svg class="ic"><path d="M5 12l4 4 10-10"/></svg> Database tumbuh otomatis</li>
                        <li><svg class="ic"><path d="M5 12l4 4 10-10"/></svg> Lihat pelanggan paling setia</li>
                        <li><svg class="ic"><path d="M5 12l4 4 10-10"/></svg> Atur hadiah kapan saja</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="alt">
        <div class="wrap center">
            <div class="eyebrow center">Dampak untuk bisnis</div>
            <h2 class="title serif">Pelanggan kembali lebih sering,<br>omzet bertumbuh</h2>
            <p class="sec-sub">Program loyalti memberi pelanggan alasan untuk selalu memilih Anda.</p>
            <div class="metrics">
                <div class="metric"><div class="num gold-text serif">Repeat ↑</div><p>Pelanggan terdorong kembali demi menyelesaikan kartu dan meraih hadiah.</p></div>
                <div class="metric"><div class="num gold-text serif">0</div><p>Tidak ada lagi kartu kertas yang hilang atau lupa dibawa pelanggan.</p></div>
                <div class="metric"><div class="num gold-text serif">100%</div><p>Setiap kunjungan tercatat — kenali dan hargai pelanggan terbaik Anda.</p></div>
            </div>
        </div>
    </section>

    <section id="cara">
        <div class="wrap center">
            <div class="eyebrow center">Cara kerja</div>
            <h2 class="title serif">Semudah satu, dua, tiga</h2>
            <p class="sec-sub">Tanpa pelatihan rumit — kasir bisa langsung menggunakannya.</p>
            <div class="steps">
                <div class="step"><div class="n">1</div><h3>Pelanggan sebut No. HP</h3><p>Kasir mengetik nomor di numpad. Pelanggan baru terdaftar otomatis.</p></div>
                <div class="step"><div class="n">2</div><h3>Beri stempel</h3><p>Satu ketukan menambah stempel. Progres pelanggan langsung terlihat.</p></div>
                <div class="step"><div class="n">3</div><h3>Tukar hadiah</h3><p>Kartu penuh, pelanggan menukar hadiah — senang, dan kembali lagi.</p></div>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="final">
                <h2 class="serif">Siap membangun pelanggan yang lebih setia?</h2>
                <p>Mulai gunakan stempel digital hari ini. Gratis dicoba, tanpa ribet.</p>
                <a href="/login" class="btn btn-gold">Coba Gratis Sekarang
                    <svg class="ic" style="width:18px;height:18px"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                <div style="margin-top:18px; font-size:13.5px; color:#9fb4d6;">Ingin demo untuk toko Anda? Hubungi tim kami via WhatsApp.</div>
            </div>
        </div>
    </section>

    <footer>
        <div class="wrap">
            <div class="brand"><img src="/logo.svg" alt=""> pelangganku.com</div>
            <div class="tag">LOYALTY THAT MATTERS</div>
            <div style="margin-top:10px">© {{ date('Y') }} pelangganku.com — Semua hak dilindungi.</div>
        </div>
    </footer>

</body>
</html>
