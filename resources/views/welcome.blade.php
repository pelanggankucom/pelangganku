<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0D47A1">
    <link rel="icon" href="/icon.svg">
    <title>pelangganku.com — Stempel Loyalti Digital untuk Bisnismu</title>
    <meta name="description" content="Ganti kartu stempel kertas dengan stempel digital berbasis nomor HP. Bikin pelanggan kembali lagi dan lagi.">
    <style>
        :root { --blue:#0D47A1; --blue-l:#1559b8; --blue-d:#0a3a85; --gold:#FFC107; --gold-d:#e6a900; --bg:#F5F7FA; --text:#13294b; --muted:#5f6b82; }
        * { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; color:#13294b; background:#fff; line-height:1.6; }
        .wrap { max-width:1080px; margin:0 auto; padding:0 20px; }
        a { text-decoration:none; }
        .btn { display:inline-block; padding:14px 26px; border-radius:14px; font-weight:700; font-size:15px; cursor:pointer; }
        .btn-gold { background:var(--gold); color:#3a2c00; box-shadow:0 8px 22px rgba(255,193,7,.3); }
        .btn-blue { background:var(--blue); color:#fff; }
        .btn-ghost { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.4); }
        .btn-outline { background:#fff; color:var(--blue); border:1.5px solid #d7e2f1; }

        /* NAV */
        nav { position:sticky; top:0; z-index:10; background:rgba(255,255,255,.92); backdrop-filter:blur(8px); border-bottom:1px solid #eef2f7; }
        nav .wrap { display:flex; align-items:center; justify-content:space-between; height:64px; }
        nav .brand { display:flex; align-items:center; gap:9px; font-weight:800; font-size:18px; color:var(--blue); }
        nav .brand img { width:32px; height:32px; }

        /* HERO */
        .hero { background:linear-gradient(160deg,#0D47A1 0%,#1559b8 60%,#0a3a85 100%); color:#fff; padding:54px 0 60px; }
        .hero .wrap { display:grid; grid-template-columns:1.1fr .9fr; gap:30px; align-items:center; }
        .hero .pill { display:inline-block; background:rgba(255,193,7,.18); color:var(--gold); border:1px solid rgba(255,193,7,.5); padding:6px 14px; border-radius:999px; font-size:12px; font-weight:600; letter-spacing:.5px; margin-bottom:18px; }
        .hero h1 { font-size:40px; line-height:1.15; font-weight:800; margin-bottom:16px; }
        .hero h1 span { color:var(--gold); }
        .hero p.lead { font-size:18px; color:#dbe7f7; margin-bottom:26px; max-width:520px; }
        .hero .cta { display:flex; gap:12px; flex-wrap:wrap; }
        .hero .phone { text-align:center; }
        .hero .phone img { width:240px; max-width:80%; filter:drop-shadow(0 24px 50px rgba(0,0,0,.35)); }
        .trust { margin-top:24px; font-size:13px; color:#aebfd9; }

        section { padding:56px 0; }
        .center { text-align:center; }
        .eyebrow { color:var(--gold-d); font-weight:700; font-size:13px; letter-spacing:1px; text-transform:uppercase; }
        h2.title { font-size:30px; font-weight:800; margin:8px 0 12px; }
        .sec-sub { color:#5f6b82; font-size:16px; max-width:620px; margin:0 auto 36px; }

        .grid { display:grid; gap:18px; }
        .g3 { grid-template-columns:repeat(3,1fr); }
        .g2 { grid-template-columns:repeat(2,1fr); }
        .card { background:#fff; border:1px solid #eaeff5; border-radius:18px; padding:24px; box-shadow:0 4px 18px rgba(19,41,75,.05); }
        .ico { width:52px; height:52px; border-radius:15px; display:flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:14px; background:#e9f1fc; }
        .ico.gold { background:#fff3cd; }
        .card h3 { font-size:17px; margin-bottom:6px; }
        .card p { color:#5f6b82; font-size:14.5px; }

        .alt { background:var(--bg); }
        /* compare */
        .compare { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .compare .col { border-radius:18px; padding:24px; }
        .compare .bad { background:#fff; border:1px solid #f0d6d6; }
        .compare .good { background:linear-gradient(160deg,#0D47A1,#1559b8); color:#fff; }
        .compare h3 { font-size:18px; margin-bottom:14px; }
        .compare ul { list-style:none; }
        .compare li { padding:9px 0 9px 30px; position:relative; font-size:15px; border-top:1px solid rgba(0,0,0,.06); }
        .compare .good li { border-top:1px solid rgba(255,255,255,.14); color:#eaf1fb; }
        .compare li:first-child { border-top:none; }
        .compare li::before { position:absolute; left:0; top:9px; font-weight:700; }
        .compare .bad li::before { content:"✕"; color:#dc2626; }
        .compare .good li::before { content:"✓"; color:var(--gold); }

        /* impact metrics */
        .metrics { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
        .metric { background:#fff; border:1px solid #eaeff5; border-radius:18px; padding:26px; text-align:center; }
        .metric .num { font-size:34px; font-weight:800; color:var(--blue); }
        .metric .num span { color:var(--gold-d); }
        .metric p { color:#5f6b82; font-size:14px; margin-top:4px; }

        /* steps */
        .steps { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
        .step { text-align:center; padding:10px; }
        .step .n { width:46px; height:46px; border-radius:50%; background:var(--gold); color:#3a2c00; font-weight:800; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; font-size:18px; }
        .step h3 { font-size:16px; margin-bottom:6px; }
        .step p { color:#5f6b82; font-size:14px; }

        /* final cta */
        .final { background:linear-gradient(160deg,#0D47A1,#0a3a85); color:#fff; border-radius:24px; padding:48px 28px; text-align:center; }
        .final h2 { font-size:28px; font-weight:800; margin-bottom:10px; }
        .final p { color:#dbe7f7; margin-bottom:24px; }

        footer { padding:30px 0 40px; text-align:center; color:#8a98ad; font-size:13px; }
        footer .brand { display:flex; align-items:center; justify-content:center; gap:8px; font-weight:800; color:var(--blue); margin-bottom:8px; }
        footer .brand img { width:26px; height:26px; }

        @media(max-width:760px){
            .hero .wrap, .g3, .g2, .compare, .metrics, .steps { grid-template-columns:1fr; }
            .hero h1 { font-size:32px; } h2.title { font-size:25px; }
            .hero .phone { order:-1; }
        }
    </style>
</head>
<body>

    <nav>
        <div class="wrap">
            <div class="brand"><img src="/logo.svg" alt=""> pelangganku</div>
            <a href="/login" class="btn btn-outline" style="padding:10px 20px">Masuk</a>
        </div>
    </nav>

    <header class="hero">
        <div class="wrap">
            <div>
                <span class="pill">LOYALTY THAT MATTERS</span>
                <h1>Ubah pembeli biasa jadi <span>pelanggan setia</span></h1>
                <p class="lead">Ganti kartu stempel kertas dengan <b>stempel digital berbasis nomor HP</b>. Tanpa scan, tanpa kartu hilang — pelanggan kembali lagi dan lagi.</p>
                <div class="cta">
                    <a href="/login" class="btn btn-gold">Coba Gratis Sekarang →</a>
                    <a href="#cara" class="btn btn-ghost">Lihat Cara Kerja</a>
                </div>
                <div class="trust">✓ Tanpa aplikasi untuk pelanggan &nbsp; ✓ Cukup nomor HP &nbsp; ✓ Siap dipakai hari ini</div>
            </div>
            <div class="phone"><img src="/illustration-phone.svg" alt="Aplikasi pelangganku"></div>
        </div>
    </header>

    {{-- MASALAH --}}
    <section>
        <div class="wrap center">
            <div class="eyebrow">Masih pakai kartu kertas?</div>
            <h2 class="title">Kartu stempel manual diam-diam merugikan Anda</h2>
            <p class="sec-sub">Hal-hal kecil ini membuat pelanggan tidak kembali — dan Anda bahkan tidak menyadarinya.</p>
            <div class="grid g3">
                <div class="card"><div class="ico">🗑️</div><h3>Kartu hilang &amp; lupa dibawa</h3><p>Pelanggan kehilangan kartu → progres hangus → mereka berhenti datang.</p></div>
                <div class="card"><div class="ico">✏️</div><h3>Gampang dipalsukan</h3><p>Stempel kertas mudah ditiru. Hadiah bocor, untung Anda berkurang.</p></div>
                <div class="card"><div class="ico">❓</div><h3>Anda tak kenal pelanggan</h3><p>Tak ada data: siapa yang sering datang, siapa yang sudah lama hilang.</p></div>
            </div>
        </div>
    </section>

    {{-- KEUNGGULAN --}}
    <section class="alt" id="fitur">
        <div class="wrap center">
            <div class="eyebrow">Kenapa pelangganku</div>
            <h2 class="title">Semua yang dibutuhkan untuk bikin pelanggan loyal</h2>
            <p class="sec-sub">Sistem stempel digital yang cepat di kasir dan disukai pelanggan.</p>
            <div class="grid g3">
                <div class="card"><div class="ico gold">📱</div><h3>Cukup Nomor HP</h3><p>Tanpa scan QR, tanpa aplikasi untuk pelanggan. Sebut nomor, selesai.</p></div>
                <div class="card"><div class="ico gold">⚡</div><h3>Super Cepat di Kasir</h3><p>Numpad besar, 2 ketukan beres. Antrean tetap lancar di jam sibuk.</p></div>
                <div class="card"><div class="ico gold">🎯</div><h3>Hadiah Fleksibel</h3><p>Atur hadiah di stempel ke-5, ke-10, dst. Lengkap nama, gambar, &amp; ketentuan.</p></div>
                <div class="card"><div class="ico gold">🏪</div><h3>Banyak Outlet</h3><p>Kelola semua cabang dalam satu sistem. Pelanggan bisa stempel di mana saja.</p></div>
                <div class="card"><div class="ico gold">📊</div><h3>Data Jadi Milik Anda</h3><p>Database pelanggan tumbuh otomatis. Tahu siapa yang loyal &amp; siapa yang hilang.</p></div>
                <div class="card"><div class="ico gold">🔒</div><h3>Anti Curang</h3><p>Stempel tercatat digital &amp; aman. Tidak bisa dipalsukan seperti kartu kertas.</p></div>
            </div>
        </div>
    </section>

    {{-- COMPARE --}}
    <section>
        <div class="wrap">
            <div class="center"><div class="eyebrow">Manual vs Digital</div><h2 class="title">Bedanya terasa sejak hari pertama</h2></div>
            <div class="compare" style="margin-top:24px">
                <div class="col bad">
                    <h3>😕 Kartu Stempel Kertas</h3>
                    <ul>
                        <li>Sering hilang &amp; terlupa</li>
                        <li>Mudah dipalsukan</li>
                        <li>Tidak ada data pelanggan</li>
                        <li>Tak tahu siapa yang loyal</li>
                        <li>Repot cetak ulang kartu</li>
                    </ul>
                </div>
                <div class="col good">
                    <h3>🚀 pelangganku.com</h3>
                    <ul>
                        <li>Tersimpan aman di nomor HP</li>
                        <li>Digital &amp; anti-palsu</li>
                        <li>Database otomatis bertumbuh</li>
                        <li>Lihat pelanggan paling setia</li>
                        <li>Atur hadiah kapan saja, gratis</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- IMPACT --}}
    <section class="alt">
        <div class="wrap center">
            <div class="eyebrow">Dampak untuk bisnis Anda</div>
            <h2 class="title">Pelanggan kembali lebih sering = omzet naik</h2>
            <p class="sec-sub">Program loyalti membuat pelanggan punya alasan untuk memilih Anda, lagi dan lagi.</p>
            <div class="metrics">
                <div class="metric"><div class="num">↑ <span>Repeat</span></div><p>Pelanggan terdorong kembali demi menyelesaikan kartu &amp; meraih hadiah.</p></div>
                <div class="metric"><div class="num"><span>0</span> Hilang</div><p>Tidak ada lagi kartu kertas yang hilang atau lupa dibawa.</p></div>
                <div class="metric"><div class="num"><span>100%</span> Tercatat</div><p>Setiap kunjungan jadi data — kenali &amp; hargai pelanggan terbaik Anda.</p></div>
            </div>
        </div>
    </section>

    {{-- CARA KERJA --}}
    <section id="cara">
        <div class="wrap center">
            <div class="eyebrow">Cara kerja</div>
            <h2 class="title">Semudah 1 - 2 - 3</h2>
            <p class="sec-sub">Tidak perlu pelatihan rumit. Kasir bisa langsung pakai.</p>
            <div class="steps">
                <div class="step"><div class="n">1</div><h3>Pelanggan sebut No. HP</h3><p>Kasir ketik nomor di numpad. Otomatis terdaftar bila pelanggan baru.</p></div>
                <div class="step"><div class="n">2</div><h3>Beri stempel</h3><p>Satu ketukan menambah stempel. Progres pelanggan langsung terlihat.</p></div>
                <div class="step"><div class="n">3</div><h3>Tukar hadiah</h3><p>Kartu penuh → pelanggan tukar hadiah → senang &amp; balik lagi.</p></div>
            </div>
        </div>
    </section>

    {{-- FINAL CTA --}}
    <section>
        <div class="wrap">
            <div class="final">
                <h2>Siap bikin pelanggan Anda lebih setia?</h2>
                <p>Mulai pakai stempel digital hari ini. Gratis dicoba, tanpa ribet.</p>
                <a href="/login" class="btn btn-gold">Coba Gratis Sekarang →</a>
                <div style="margin-top:16px; font-size:13px; color:#aebfd9;">Ingin demo untuk toko Anda? Hubungi tim kami via WhatsApp.</div>
            </div>
        </div>
    </section>

    <footer>
        <div class="wrap">
            <div class="brand"><img src="/logo.svg" alt=""> pelangganku.com</div>
            <div>Loyalty That Matters · © {{ date('Y') }} pelangganku.com</div>
        </div>
    </footer>

</body>
</html>
