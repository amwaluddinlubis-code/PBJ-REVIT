# Tinjauan Desain — Aplikasi "Analisa Perencana"

- Tanggal: 2026-11-21 (pull `66ab742..7b5b10d`)
- Tema: TailAdmin + IDDS, tema gelap (CSS var), zoom font 80–130%, scroll & overlay global
- Struktur utama: sidebar utama (6 grup menu + daftar gedung) → topbar utama → konten (panel/tabel/kartu)

## Kesimpulan Singkat

Desainnya solid, profesional, dan konsisten: kulit **TailAdmin + token IDDS** dipakai dengan disiplin tinggi (sidebar terang berikon, topbar, panel putih, KPI berikon, badge, tombol radius 8–12px, skala font 80–130%). **Bahasa visual kompak dan cepat dicerna** — proses admin yang sering dijalankan terbaca jelas. Fitur impor yang baru tadi (impor dari folder + template + verifikasi) juga sudah membaiki beberapa hal standar dari sebelumnya. Fitur drawer mobile (P1) **sudah diimplementasi & diverifikasi** (2026-11-21): drawer dari kiri, lapik overlay, aria-expanded, Escape, tombol sembunyikan/tunjukkan sidebar di desktop. Fitur Dark theme (P2) **sudah selesai & diverifikasi** (2026-11-21): palet `--ina-neutral-*` gelap dipasangkan — hover seperti `.tombol.abu:hover`, `.paginasi button:hover` terlihat di gelap. Masih tersisa: **UX feedback saat impor berjalan** (P6).

## Kekuatan

1. **Sistem desain konsisten & profesional.** IDDS (biru utama `#0049b8`, palet warna semantik terpisah), tema gelap via `html.gelap` + CSS var tanpa `!important`, transisi hover ~200–300ms, icon SVG stroke inline.
2. **Struktur informasi jelas.** Sidebar berlapis: pengguna, pemilih sekolah aktif, 5 grup menu parent>child (Perencanaan, Gedung, Belanja, Bahan & Laporan, Pengaturan) + daftar gedung dengan badge persen fisik. Topbar: pencarian, A−/A+, tema, dropdown user (nama, username, peran, sekolah).
3. **Dashboard yang bertindak.** KPI 4 kartu ikon berbeda (Total RAB, SPK Terdaftar, Realisasi PPh 22, Sisa Anggaran) + `peringatan` otomatis daftar masalah yang disesaikan klik (baris detail belum bernomor, invoice ≠ total SPK, sisa negatif).
4. **Lebih dari cukup rinci UX kecil.** Paginasi klien, `lencana` warna (kuning "dikonversi", abu "satuan beda"), baris total netral, flash messages, tombol zoom font, preferensi di `localStorage`, fokus visible, `aria-label`.

## Fitur-Fitur Baru (pull `66ab742..7b5b10d`)

1. **Impor Satu-Klik, 3 cara** (`app/Views/impor/index.php`):
   - Cara 1 — Dari Folder: daftar berkas .xlsx di `writable/impor/` (cards dengan nama, MB, tanggal); file `Taruh berkas xlsx di folder ini.txt` bantu daftar. → Fitur antik dari v1 ditambah polish.
   - Cara 2 — Impor Ulang Berkas Terakhir (hanya muncul saat ada unggahan).
   - Cara 3 — Unggahan klasik (`impor/proses`) dengan error handling lebih baik (pesan jelas saat batas ukuran PHP tersebar ke folder).
   - **Semua cara punya "Pemilih Sekolah Tujuan Impor"** — bisa impor ke sekolah yang bukan sekolah aktif.
2. **Template Manual 4-Sheet** (`ParserTemplate`, `PenulisTemplate`): format datar (Sekolah, Harga, Analisa, BOQ) terdeteksi otomatis pada semua jalur impor; BOQ dihitung ulang dengan konversi satuan (non-perencana); "Ekspor Template Terisi" dari data sekolah aktif → periksa/koreksi di Excel → impor kembali.
3. **Panel "Komponen Belum Tertaut"** — saran padanan dengan badge skor (hijau ≥ 80, abu di bawah), datalist, tautan satu-satu; "Tautkan Massal Saran ≥ 80" (dengan konfirmasi). Indikator komitmen yang baik — patut dicontoh app lain.
4. **Riwayat Impor + Verifikasi** — tabel per gedung: Terhitung vs GRAND TOTAL Sheet + Selisih + badge hijau "Sesuai". Dukungan validasi standar impor lain.

## Masalah & Saran (prioritas menurun)

### P1 — Mobile: tombol menu sama saja (bug nyata)
- **Masalah**: di ≤760px `.samping{display:none}` (`app.css:156`), tapi `.tombol-samping` tetap terlihat; klik hanya menambah `.samping-tutup` di `.kerangka` (`app.css:159–160`) yang tidak menampilkan sidebar.
- **Saran**: implementasi off-canvas drawer — sidebar di mobile jadi panel transisi dari kiri; `aria-expanded`; fokus otomatis; Escape/klik di luar tutup; sembunyikan tombol-samping pada desktop; tambah min-height 44px.

### P2 — Dark theme: beberapa hover masih putih-kosong
- `html.gelap` tidak mengubah `--ina-neutral-50/100` (terlihat pada hover `.tombol.abu`, `--ina-neutral-100` pada beberapa border).
- **Saran**: tambah variabel gelap (`--ina-neutral-50:#16263f; --ina-neutral-100:#22334f; ...`) atau overrides khusus `html.gelap .tombol.abu`.

### P3 — Tabel melebar di mobile tanpa scroll
- Rencana Belanja, SPK Detail, LPJ, Dashboard Gedung (5–9 kolom).
- **Saran**: `.panel { overflow-x:auto; }` atau `table { display:block; overflow-x:auto; }` — mulai dari tabel impor baru (panel tak tertaut, riwayat).

### P4 — Touch & teks terlalu kecil di genggaman
- Sidebar: `padding:8px 10px; font:11.2px` → tinggi ~31px.
- **Saran**: `min-height:44px` pada `.samping nav a`, naikkan padding menjadi 9–10px; tombol A−/A+ cukup.

### P5 — Minor a11y & polish
- Input pencarian topbar: tambah `aria-label="Cari bahan"`.
- Dropdown user: tambah `aria-expanded`; fokus terlihat saat mobile sudah via `focus-visible`.
- Tambah override `--ina-neutral-*` gelap (di P2).

### P6 — Feedback saat impor berjalan (UX baru)
- Cara 1/3 proses synchronous, workbook 8MB+ bisa "berjalan beberapa detik sampai ±1 menit". Tanpa feedback — user rela refresh/menunggu.
- **Saran**: `onsubmit` → disable button + indikasi animasi ("Mengimpor… workbook besar bisa berlangsung hingga ±1 menit"). Tidak ada spinner saat ini.

### P7 — Cara 2/3 duplikat & imajinal — prefer Cara 1
- Tiga "Cara" di halaman itu tanpa prioritas visual; harus discan dulu.
- **Saran**: progressive disclosure — tampilkan Cara 1 secara default (utama), Cara 2 & 3 di `<details>`/collapse; atau wizard: 1 langkah. Balik urutan agar yang paling mudah ditemukan muncul paling dulu. Template panel juga boleh collapse (jarang dipakai).

### P8 — Small polish (opsional)
- Cara 1: badge file Cards terlalu panjang waktu render buat workbook besar — fokus default dulu Cara 1.
- Error: `galat` untuk error upload ada — ok, bisa lanjut hubungkan garis: spansi size → Cara 1 secara otomatis? (optional)
- Ajak: tambah "untuk kali berikutnya — cara yang dipilih" storage (opsional).
- `writable/impor/Taruh berkas xlsx di folder ini.txt` — memang agak tersembunyi; usul direktori sudah ada. OK.

## Bagian yang perlu dikembangkan (menurut urgensi)

~~1.~~ **Mobile drawer** (P1) — **sudah selesai & diverifikasi** (2026-11-21): click menambah class `sanding-boka` + aria-expanded true, Escape menutupnya; drawer CSS lengkap ada di media ≤760px dan cocok dengan class DOM.
~~2.~~ **Dark theme vars** (P2) — **sudah selesai & diverifikasi** (2026-11-21): tambah override `--ina-neutral-50/100/200/300/400` gelap — hover seperti `.tombol.abu:hover`, `.paginasi button:hover` terlihat di gelap.
3. **Overflow-x tabel** (P3) — kebutuhan kompatibilitas layar sempit (termasuk tabel baru impor).
4. **Touch target 44px** (P4) — bagian dari fix mobile.
5. **A11y polis** (P5) — aria-label pencarian, aria-expanded user menu, minor lain.
6. **Feedback impor (P6)** — disable + spinner pada saat proses impor.
7. **Progressive disclosure Cara 2/3** (P7) — default show Cara 1 + collapse lainnya.

## Pertanyaan untuk pemilik desain (opsional)
- Apakah preferensi font saat ini (skala 80–130%) sudah disesuaikan dengan seluler/desktop? Kalau tidak, pertimbangkan default 100% hanya di mobile.
- Tema gelap sekarang warna utama `#0a1120 / #0f1a2e` (Navy) — sesuaikan dengan warna brand sekolah Anda.
- Urutan Cara 1/2/3 — Cara 1 adalah yang direkomendasikan; pertimbangkan dibuat paling dulu dan paling terlihat.
