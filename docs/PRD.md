# PRD & Technical Spec — pelangganku.com (Merchant/Cashier PWA)

| | |
|---|---|
| **Produk** | pelangganku.com — Digital Loyalty Stamp (sisi Merchant/Kasir) |
| **Tipe** | Progressive Web App (PWA) — mobile & tablet first |
| **Versi Dokumen** | 1.0 |
| **Tanggal** | 16 Juni 2026 |
| **Status** | Draft for Development |
| **Backend** | Laravel 12 (REST API) — sudah live & auto-deploy |
| **Identifikasi Pelanggan** | **Nomor telepon** (TANPA QR Code) |

---

## 1. Ringkasan Proyek (Project Overview)

### 1.1 Deskripsi
**pelangganku.com** adalah aplikasi kasir berbasis web (PWA) yang memungkinkan merchant memberikan **stempel loyalitas digital** dan mengelola **penukaran reward** kepada pelanggan. Berbeda dari aplikasi sejenis (mis. Seven Stamp) yang mengandalkan QR Code, identifikasi pelanggan di sini **sepenuhnya berbasis nomor telepon** — sehingga tidak butuh kamera, tidak butuh aplikasi di sisi pelanggan, dan transaksi tetap cepat saat antrean ramai.

Konsep stempel: setiap transaksi yang memenuhi syarat (mis. pembelian 1 produk) memberi 1 atau lebih stempel. Setelah pelanggan mengumpulkan sejumlah stempel tertentu (mis. 10 stempel), ia berhak menukar **reward** (mis. 1 produk gratis).

### 1.2 Tujuan Produk
- Meningkatkan **retensi pelanggan** lokal tanpa friksi teknologi (tanpa scan, tanpa install di sisi pelanggan).
- Memberi kasir alur **secepat mungkin** agar tidak menghambat antrean.
- Memberi owner **visibilitas data** (jumlah pelanggan, stempel terbit, reward tertukar, performa cabang/kasir).

### 1.3 Target Pengguna
| Pengguna | Deskripsi | Perangkat |
|----------|-----------|-----------|
| **Pemilik Bisnis (Owner/Admin)** | Mengelola cabang, karyawan, program loyalitas, database pelanggan, analitik. | Tablet / Desktop |
| **Kasir (Staff/Cashier)** | Operasional harian: cek nomor, daftar pelanggan, beri stempel, tukar reward. | Tablet / HP di meja kasir |
| **Staf Toko** | Sama seperti kasir (peran teknis identik). | Tablet / HP |

### 1.4 Platform & Arsitektur Teknis (rekomendasi)
```
┌────────────────────────┐        HTTPS/JSON        ┌─────────────────────────┐
│   PWA Frontend (Kasir) │  ───────────────────────▶ │  Laravel 12 REST API     │
│  Vue 3 + Vite + Pinia  │ ◀───────────────────────  │  + Sanctum (token auth)  │
│  Service Worker/Workbox│        (Bearer token)     │  + SQLite/MySQL          │
│  IndexedDB (offline q) │                            │  Hosting: Rumahweb       │
└────────────────────────┘                            └─────────────────────────┘
```
- **Frontend:** Vue 3 + Vite (plugin `vite-plugin-pwa`), Pinia (state), TailwindCSS (UI). Installable (Add to Home Screen), responsive tablet-first.
- **Backend:** Laravel 12 sebagai **API-only** (`/api/v1/...`), autentikasi **Laravel Sanctum** (personal access token), validasi via Form Request.
- **DB:** mulai SQLite (sudah aktif), siap migrasi ke MySQL saat skala bertambah.
- **Offline-first (opsional fase 2):** antre transaksi stempel di IndexedDB saat koneksi putus, sinkron otomatis saat online.

---

## 2. Arsitektur Pengguna & Hak Akses (User Roles & Permissions)

Sistem **multi-tenant** sederhana: satu **Merchant** memiliki banyak **Branch (Cabang)**, dan banyak **User** (Owner/Kasir) yang terikat pada merchant & cabang.

### 2.1 Definisi Peran
| Peran | Lingkup | Tanggung Jawab |
|-------|---------|----------------|
| **Owner / Admin** | Seluruh merchant (semua cabang) | Kelola cabang, kelola karyawan, setup program loyalitas & reward, kelola database pelanggan, lihat analitik & laporan. |
| **Staff / Cashier** | 1 cabang yang ditugaskan | Cek nomor pelanggan, daftar pelanggan baru, beri stempel, tukar reward, lihat riwayat transaksi hariannya. |

### 2.2 Matriks Hak Akses (Permission Matrix)
| Fitur / Aksi | Owner/Admin | Staff/Cashier |
|---|:---:|:---:|
| Login (email+password) | ✅ | ✅ |
| Login (PIN kasir) | ✅ | ✅ |
| Kelola cabang (CRUD) | ✅ | ❌ |
| Kelola karyawan/kasir (CRUD) | ✅ | ❌ |
| Setup program loyalitas (jumlah stempel, reward) | ✅ | ❌ |
| Lihat seluruh database pelanggan | ✅ | 🔸 (hanya cari per-nomor) |
| Edit/hapus data pelanggan | ✅ | ❌ |
| Cek nomor & buka profil loyalitas pelanggan | ✅ | ✅ |
| Daftar pelanggan baru (quick registration) | ✅ | ✅ |
| Beri stempel (Give Stamp) | ✅ | ✅ |
| Tukar reward (Redeem) | ✅ | ✅ |
| Void/koreksi transaksi (mis. salah input) | ✅ | 🔸 (≤ N menit, lalu butuh approval) |
| Lihat riwayat transaksi harian (cabangnya) | ✅ | ✅ |
| Lihat analitik & laporan lintas cabang | ✅ | ❌ |

> 🔸 = akses terbatas/bersyarat.

---

## 3. Fitur Utama (Core Features & Functional Requirements)

### 3.1 Autentikasi (Authentication)
**Deskripsi:** Login/logout untuk Owner & Kasir, mendukung dua metode.

| ID | Requirement |
|----|-------------|
| AUTH-01 | Login via **Email + Password** (Owner & Staff). |
| AUTH-02 | Login cepat via **PIN Kasir** (4–6 digit) pada perangkat yang sudah "ter-pairing" dengan cabang. PIN unik per kasir di dalam cabang. |
| AUTH-03 | Token akses (Sanctum) disimpan aman di PWA; auto-refresh/expiry yang wajar (mis. 12 jam shift). |
| AUTH-04 | **Logout** menghapus token lokal. Tersedia "Ganti Kasir" (lock screen → minta PIN) tanpa logout penuh device. |
| AUTH-05 | Rate limiting & lockout setelah X percobaan PIN/password gagal. |
| AUTH-06 | Audit: setiap transaksi tercatat `performed_by` (user kasir) & `branch_id`. |

**Acceptance Criteria:**
- Kasir bisa berganti shift cukup dengan PIN (≤ 3 detik), tanpa mengetik email/password.
- Token kedaluwarsa → user diarahkan ke layar lock, bukan kehilangan data antrean.

---

### 3.2 Identifikasi Pelanggan (Pengganti Scan)
**Deskripsi:** Inti aplikasi. Kasir mengetik nomor telepon pelanggan via **numpad besar di layar**, sistem otomatis mengecek keberadaan pelanggan.

| ID | Requirement |
|----|-------------|
| IDN-01 | Layar utama kasir menampilkan **numpad angka besar** (0–9, hapus, bersihkan) yang dominan dan nyaman ditekan dengan jempol. |
| IDN-02 | Input menampilkan nomor saat diketik, dengan format/visual yang jelas (mis. `0812-3456-7890`). |
| IDN-03 | Validasi format **nomor Indonesia** secara real-time (lihat NFR-SEC-01). Tombol "Lanjut/Cek" nonaktif sampai format valid. |
| IDN-04 | Saat tombol **"Cek"** ditekan, sistem normalisasi nomor lalu query backend (target < 1 detik). |
| IDN-05 | **Jika nomor terdaftar** → tampilkan **Profil Loyalitas Pelanggan**: nama, total stempel berjalan, progres menuju reward (mis. 7/10), riwayat singkat, tombol **Beri Stempel** & **Tukar Reward**. |
| IDN-06 | **Jika nomor belum terdaftar** → tampilkan **pop-up/formulir Quick Registration** (lihat 3.3) dengan nomor sudah terisi otomatis. |
| IDN-07 | Auto-detect duplikasi: nomor dinormalisasi sebelum cek, sehingga `0812...`, `+62812...`, `62812...` dianggap pelanggan yang sama. |

**Tampilan Profil Loyalitas (komponen kunci):**
- Visual progres stempel (mis. grid 10 kotak; terisi = stempel didapat).
- Indikator **"Reward siap ditukar!"** bila stempel ≥ ambang batas.

---

### 3.3 Pendaftaran Pelanggan Baru (Quick Registration)
**Deskripsi:** Form ringkas agar kasir mendaftar pelanggan baru di tempat tanpa memperlambat antrean.

| ID | Requirement |
|----|-------------|
| REG-01 | Form hanya 2 field wajib: **Nama Pelanggan** & **Nomor Telepon** (nomor terisi otomatis dari layar cek). |
| REG-02 | Field opsional (dapat dikonfigurasi owner): tanggal lahir, gender, email. Default disembunyikan agar cepat. |
| REG-03 | Validasi anti-duplikasi: jika nomor sudah ada → batal daftar, langsung buka profil pelanggan tsb. |
| REG-04 | Setelah simpan → pelanggan otomatis aktif & langsung masuk ke layar **Beri Stempel** (alur menyatu, tanpa langkah ekstra). |
| REG-05 | Pelanggan baru otomatis terikat ke `merchant_id` & cabang tempat ia didaftarkan (sumber akuisisi tercatat). |

---

### 3.4 Pemberian Stempel (Give Stamp)
**Deskripsi:** Menambahkan stempel ke saldo pelanggan setelah nomor terverifikasi.

| ID | Requirement |
|----|-------------|
| STMP-01 | Default **+1 stempel** dengan satu ketukan tombol besar **"Beri Stempel"**. |
| STMP-02 | Mendukung input **jumlah custom** (mis. beli 3 item = 3 stempel) via stepper/numpad kecil. |
| STMP-03 | (Opsional, dikonfigurasi owner) input **nominal transaksi** untuk konversi otomatis (mis. Rp25.000 = 1 stempel). |
| STMP-04 | Setelah diberikan → tampilkan **konfirmasi sukses** + saldo stempel terbaru + animasi progres. |
| STMP-05 | Jika setelah penambahan stempel **mencapai ambang reward**, tampilkan badge **"Pelanggan berhak reward"** (tidak otomatis menukar). |
| STMP-06 | Setiap pemberian stempel tercatat sebagai transaksi (`type=earn`) dengan kasir, cabang, jumlah, waktu. |
| STMP-07 | Pencegahan double-tap/duplikasi: debounce + idempotency key per transaksi. |

---

### 3.5 Penukaran Hadiah (Redeem Reward)
**Deskripsi:** Memotong stempel saat pelanggan menukar reward.

| ID | Requirement |
|----|-------------|
| RDM-01 | Tombol **"Tukar Reward"** hanya aktif bila stempel ≥ ambang batas reward. |
| RDM-02 | Tampilkan daftar reward yang tersedia (jika ada >1 tier reward) beserta biaya stempelnya. |
| RDM-03 | Saat ditukar → **verifikasi kelayakan** ulang di backend (cegah race condition), lalu **potong stempel** sesuai biaya reward. |
| RDM-04 | Sisa stempel berjalan tetap tersimpan (mis. punya 12, tukar reward 10 → sisa 2). Perilaku reset/carry-over dapat dikonfigurasi owner. |
| RDM-05 | Catat transaksi (`type=redeem`) + reward yang ditukar + kasir + waktu. |
| RDM-06 | Konfirmasi penukaran (mencegah salah pencet) sebelum potong stempel. |

---

### 3.6 Riwayat Transaksi (History)
**Deskripsi:** Catatan aktivitas harian kasir.

| ID | Requirement |
|----|-------------|
| HIS-01 | Kasir melihat **riwayat hari ini** di cabangnya: pemberian stempel, pendaftaran baru, penukaran reward. |
| HIS-02 | Tiap entri menampilkan: waktu, nama/nomor pelanggan (disamarkan sebagian, mis. `0812****890`), jenis aksi, jumlah stempel. |
| HIS-03 | Ringkasan harian: total pelanggan baru, total stempel terbit, total reward tertukar. |
| HIS-04 | Owner dapat memfilter riwayat lintas cabang, rentang tanggal, & per kasir. |
| HIS-05 | Ekspor (CSV) untuk owner (fase 2). |

---

### 3.7 Modul Owner/Admin (ringkas)
| Modul | Fungsi |
|-------|--------|
| **Manajemen Cabang** | CRUD cabang (nama, alamat, jam operasional). |
| **Manajemen Karyawan** | CRUD kasir, set PIN, assign ke cabang, aktif/nonaktif. |
| **Setup Program Loyalitas** | Atur ambang stempel reward, definisi reward, aturan earn (per transaksi/per nominal), carry-over vs reset. |
| **Database Pelanggan** | Cari, lihat, edit, gabung duplikat, ekspor. |
| **Analitik** | Pelanggan aktif, stempel terbit, reward tertukar, tren harian/bulanan, performa cabang & kasir. |

---

## 4. Kebutuhan Non-Fungsional (Non-Functional Requirements)

### 4.1 Keamanan (Security)
| ID | Requirement |
|----|-------------|
| NFR-SEC-01 | **Validasi & normalisasi nomor Indonesia.** Terima `08xxxxxxxxxx`, `+628xxxxxxxx`, `628xxxxxxxx`. Normalisasi ke format kanonik **`628xxxxxxxxx`** (E.164 tanpa `+`) sebelum simpan/cari. Panjang valid 9–13 digit setelah prefix. |
| NFR-SEC-02 | **Anti-duplikasi pelanggan:** kolom nomor (kanonik) **UNIQUE per merchant**. Cek di level DB + aplikasi. |
| NFR-SEC-03 | Transport **HTTPS** wajib. Token Sanctum, tidak menyimpan password plaintext (bcrypt/argon2). |
| NFR-SEC-04 | **Otorisasi ketat:** kasir tidak bisa akses data merchant/cabang lain (scoping `merchant_id`/`branch_id` di setiap query — gunakan Laravel Policy + Global Scope). |
| NFR-SEC-05 | **Audit trail** untuk semua transaksi stempel/redeem (siapa, kapan, cabang). |
| NFR-SEC-06 | **Idempotency** pada endpoint mutasi stempel/redeem untuk cegah double submit. |
| NFR-SEC-07 | Privasi: nomor pelanggan ditampilkan tersamar di layar/riwayat publik kasir. |
| NFR-SEC-08 | Rate limiting endpoint cek-nomor & login. |

### 4.2 Performa (Performance)
| ID | Requirement |
|----|-------------|
| NFR-PERF-01 | **Pencarian pelanggan via nomor < 1 detik** (target p95). Wajib **index DB** pada kolom nomor kanonik. |
| NFR-PERF-02 | Aksi beri stempel → konfirmasi sukses < 1.5 detik (p95) pada koneksi seluler normal. |
| NFR-PERF-03 | PWA load awal < 3 detik; setelah ter-cache (service worker) < 1 detik. |
| NFR-PERF-04 | Payload API ramping (hanya field yang dibutuhkan layar kasir). |

### 4.3 UI/UX Guideline
| ID | Requirement |
|----|-------------|
| NFR-UX-01 | **Desain minimalis**, fokus pada satu tujuan per layar. |
| NFR-UX-02 | **Numpad dominan**: tombol angka besar (min. 64×64px touch target), kontras tinggi, feedback haptic/visual saat ditekan. |
| NFR-UX-03 | **Alur seminimal mungkin:** target pelanggan lama selesai dalam **≤ 2 ketukan** setelah nomor diinput (cek → beri stempel). |
| NFR-UX-04 | Status besar & jelas: sukses (hijau), perhatian (kuning "reward siap"), error (merah). |
| NFR-UX-05 | Tablet-first landscape & portrait; tetap nyaman di HP. |
| NFR-UX-06 | Bahasa Indonesia, istilah sehari-hari ("Beri Stempel", "Tukar Hadiah"). |
| NFR-UX-07 | Mode "antrean ramai": setelah sukses, auto-reset ke numpad dalam 2–3 detik untuk pelanggan berikutnya. |

### 4.4 Reliabilitas (tambahan)
| ID | Requirement |
|----|-------------|
| NFR-REL-01 | PWA tetap dapat dibuka offline (shell ter-cache). |
| NFR-REL-02 | (Fase 2) Antrean transaksi offline di IndexedDB, sinkron saat online, dengan deteksi konflik. |

---

## 5. Alur Pengguna (User Flow)

### Skenario A — Pelanggan Lama (nomor sudah terdaftar)
1. Kasir berada di **Layar Utama (Numpad)**.
2. Kasir mengetik nomor telepon pelanggan pada numpad besar.
3. Kasir menekan **"Cek"**.
4. Sistem menormalisasi nomor & mencari (< 1 detik) → **ditemukan**.
5. Tampil **Profil Loyalitas**: nama, progres stempel (mis. 7/10), tombol aksi.
6. Kasir menekan **"Beri Stempel"** (default +1, atau set jumlah).
7. Sistem menambah stempel → tampil **konfirmasi sukses** + saldo baru (mis. 8/10).
8. (Jika mencapai 10) muncul badge **"Reward siap ditukar"** — kasir bisa lanjut **Tukar Reward** atau biarkan.
9. Layar **auto-reset ke numpad** → siap pelanggan berikutnya. **Selesai.**

```
[Numpad] → ketik nomor → [Cek] → (TERDAFTAR) → [Profil] → [Beri Stempel] → [Sukses] → auto-reset
```

### Skenario B — Pelanggan Baru (nomor belum terdaftar)
1. Kasir di **Layar Utama (Numpad)**.
2. Kasir mengetik nomor telepon pelanggan → tekan **"Cek"**.
3. Sistem mencari → **tidak ditemukan**.
4. Muncul **pop-up Quick Registration** dengan **nomor sudah terisi otomatis**.
5. Kasir mengetik **Nama Pelanggan** → tekan **"Daftar & Lanjut"**.
6. Sistem membuat data pelanggan (terikat merchant & cabang) → **langsung** ke layar **Beri Stempel**.
7. Kasir menekan **"Beri Stempel"** → **konfirmasi sukses** (mis. 1/10).
8. Layar **auto-reset ke numpad**. **Selesai.**

```
[Numpad] → ketik nomor → [Cek] → (BARU) → [Form: Nama] → [Daftar & Lanjut] → [Beri Stempel] → [Sukses] → auto-reset
```

### Skenario C (edge) — Penukaran Reward
1. Dari **Profil Loyalitas** pelanggan yang stempelnya ≥ ambang.
2. Tekan **"Tukar Reward"** → pilih reward (jika >1).
3. Konfirmasi → backend verifikasi ulang & potong stempel.
4. Tampil **sukses** + sisa stempel. **Selesai.**

---

## Lampiran A — Model Data (Skema Awal)

| Tabel | Kolom utama |
|-------|-------------|
| `merchants` | id, name, owner_user_id, created_at |
| `branches` | id, merchant_id, name, address, is_active |
| `users` | id, merchant_id, branch_id (nullable utk owner), name, email, password, role (`owner`/`cashier`), pin_hash, is_active |
| `customers` | id, merchant_id, name, phone_canonical (**UNIQUE per merchant, indexed**), phone_raw, dob (null), created_branch_id, created_at |
| `loyalty_programs` | id, merchant_id, stamps_per_reward, earn_rule (`per_visit`/`per_amount`), amount_per_stamp (null), carry_over (bool), is_active |
| `rewards` | id, loyalty_program_id, name, cost_stamps, is_active |
| `customer_balances` | id, customer_id, loyalty_program_id, stamps_current, lifetime_stamps |
| `stamp_transactions` | id, customer_id, branch_id, user_id, type (`earn`/`redeem`/`void`), stamps_delta, reward_id (null), idempotency_key, created_at |

> Catatan relasi: `merchant 1—N branch`, `merchant 1—N user`, `merchant 1—N customer`, `customer 1—1 balance` (per program), `customer 1—N stamp_transactions`.

---

## Lampiran B — Endpoint API (Rancangan, prefix `/api/v1`)

| Method | Endpoint | Deskripsi | Akses |
|--------|----------|-----------|-------|
| POST | `/auth/login` | Login email+password → token | Public |
| POST | `/auth/pin-login` | Login PIN (device ter-pairing) | Public (device-scoped) |
| POST | `/auth/logout` | Hapus token | Auth |
| GET | `/customers/lookup?phone=` | Cek nomor (normalisasi) → profil atau 404 | Owner, Cashier |
| POST | `/customers` | Quick registration | Owner, Cashier |
| GET | `/customers/{id}` | Detail profil loyalitas | Owner, Cashier |
| POST | `/customers/{id}/stamps` | Beri stempel (`amount`, `idempotency_key`) | Owner, Cashier |
| POST | `/customers/{id}/redeem` | Tukar reward (`reward_id`, `idempotency_key`) | Owner, Cashier |
| GET | `/transactions/today` | Riwayat harian (cabang/kasir) | Owner, Cashier |
| GET | `/analytics/summary` | Ringkasan analitik | Owner |
| CRUD | `/branches`, `/staff`, `/loyalty-programs`, `/rewards` | Manajemen | Owner |

**Contoh respons `lookup` (ditemukan):**
```json
{
  "found": true,
  "customer": {
    "id": 123,
    "name": "Budi",
    "phone_masked": "0812****890",
    "stamps_current": 7,
    "stamps_per_reward": 10,
    "reward_ready": false
  }
}
```

---

## Lampiran C — Aturan Normalisasi Nomor (Indonesia)

| Input kasir | Hasil kanonik |
|-------------|---------------|
| `081234567890` | `6281234567890` |
| `+6281234567890` | `6281234567890` |
| `6281234567890` | `6281234567890` |
| `81234567890` | `6281234567890` |

Aturan:
1. Hapus spasi, `-`, `(`, `)`.
2. Jika diawali `+` → buang `+`.
3. Jika diawali `0` → ganti jadi `62`.
4. Jika diawali `8` (tanpa 0/62) → tambahkan `62`.
5. Validasi: hasil cocok regex `^628[1-9][0-9]{7,11}$`.
6. Simpan `phone_canonical` (untuk index & unique) + `phone_raw` (tampilan asli).

---

## Lampiran D — Metrik Keberhasilan (KPI)
- **Waktu transaksi kasir** (cek → selesai): target median < 8 detik.
- **Repeat rate** pelanggan (kembali ≥ 2×/bulan).
- **Stamp issuance** & **redemption rate** per cabang.
- **Adoption**: % transaksi yang ter-capture stempel vs total transaksi.

## Lampiran E — Di Luar Lingkup (Out of Scope) & Roadmap
**MVP tidak mencakup:** aplikasi sisi pelanggan, notifikasi WhatsApp/SMS, OTP verifikasi nomor, pembayaran/POS penuh, multi-bahasa.

**Roadmap fase berikut:**
1. Notifikasi WhatsApp (reminder reward & promo).
2. Mode offline-first penuh (sinkronisasi antrean).
3. Verifikasi OTP saat redeem (anti-fraud).
4. Dashboard analitik lanjutan + ekspor.
5. Tier membership & poin (selain stempel).
6. Integrasi POS/pembayaran.
