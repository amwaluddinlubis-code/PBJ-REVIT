# Rencana Pengembangan Dokumentasi

- Tanggal tinjauan: 24 November 2026
- Kode acuan: `main` @ `5d8956f` (working tree bersih)
- Cakupan: `README.md`, `docs/Panduan_Penggunaan.md`, `docs/ui-design-review.md`, `AGENTS.md`, rute, menu, migrasi, dan view terkait
- Sifat dokumen: rencana kerja prioritas, bukan panduan pengguna

## 1. Ringkasan

Dokumentasi belum mengikuti kecepatan pengembangan. `AGENTS.md` adalah satu-satunya
dokumen yang benar-benar mutakhir; `README.md` berhenti di migrasi 000011, dan
`Panduan_Penggunaan.md` sudah memuat fitur 24 November tetapi masih menyimpan
petunjuk lama yang **salah** (susunan menu, jenis upah, tab Master Bahan) serta
32 gambar yang tidak ada di repo. `docs/ui-design-review.md` adalah catatan
tinjauan 21 November yang sudah tidak cocok dengan CSS sekarang.

Urutan prioritas di bawah ini disusun dari risiko terbesar: **informasi salah yang
bisa membuat pengguna mengambil tindakan keliru**, lalu **kelengkapan modul baru**,
lalu **struktur dan otomatisasi**.

## 2. Metode dan bukti

Yang dibaca/diperiksa: seluruh berkas `.md` di repo, `app/Config/Routes.php`,
`app/Libraries/AksesMenu.php`, `app/Views/layout/main.php`, `app/Views/dashboard/index.php`,
`app/Views/bahan/index.php`, `app/Views/analisa/detail.php`, `app/Views/upah/index.php`,
`app/Filters/AuthFilter.php`, `app/Controllers/Pengguna.php`, `app/Controllers/Invoice.php`,
`app/Commands/*.php`, `public/assets/app.css`, `public/assets/paginasi.js`,
folder `app/Database/Migrations/`, dan `tests/`.

Angka kunci:

| Hal | Dokumen | Kenyataan di kode |
|---|---|---|
| Jumlah tabel | "14 tabel" (`README.md:65`) | 33 nama tabel dari 28 migrasi (000001–000028) |
| Langkah upgrade | "migrasi 000007–000011" (`README.md:187`) | migrasi terakhir 000028 |
| Gambar panduan | 32 rujukan `panduan_pbj/*.png` | 0 berkas gambar di repo |
| Grup menu | 5 grup (`Panduan:70`) | 7 grup: Perencanaan, Pembangunan Gedung, Belanja, Material, Keuangan, Laporan, Pengaturan |
| Jenis upah | Pekerja/Tukang + Kepala Tukang/Mandor (`Panduan:204,306,507`) | hanya Pekerja & Tukang; Mandor/Kepala Tukang dihapus |
| Menu Simulasi & Template Laporan | "menu Perencanaan" / "menu Laporan" (`Panduan:284,443`) | tidak ada tautan di sidebar; hanya bisa lewat URL/tautan tidak langsung |
| Uji otomatis | tidak disebut | `tests/` ada (9 tes) dan `composer test` berjalan |

## 3. Temuan utama

### A. Informasi yang sudah salah (risiko tinggi)

1. **Jenis upah.** `Panduan` masih menyuruh pengguna mengisi tarif Kepala Tukang/Mandor
   dan menyebut "Tukang Kayu, Tukang Batu, dst.". Aturan resmi (`AGENTS.md`) dan
   `BaseController::upahKanon()` hanya mengenal **Pekerja** dan **Tukang**; Mandor/Kepala
   Tukang dihapus dari analisa. Teks bantuan di `app/Views/upah/index.php:99` juga masih
   menyebut Mandor/Kepala Tukang, dan formulir masih menerima "Jenis tenaga baru"
   bebas (`upah/index.php:94`) — jadi UI dan dokumentasi sama-sama tertinggal dari aturan.
2. **Susunan menu.** Panduan memakai grup "Bahan & Laporan" dan "Pengaturan → Pengguna /
   Hak Akses Menu"; menu aktual adalah **Material**, **Laporan**, **Keuangan**, serta label
   **Manajemen User** dan **Akses Menu** (`layout/main.php:133–166`, `AksesMenu.php:51–71`).
   Pembaca akan mencari menu yang tidak ada.
3. **Dua menu tidak dapat ditemukan.** **Simulasi Mingguan** (`Routes.php:182`,
   `AksesMenu.php:34`) dan **Template Laporan** (`Routes.php:117`, `AksesMenu.php:66`)
   tidak dirender di sidebar (`layout/main.php:98–108,150–156`); Simulasi hanya tertaut dari
   halaman Kurva S, Template Laporan hanya dari tombol render di Detail SPK/Gedung.
   Panduan menuliskan keduanya sebagai menu — dokumentasi tidak boleh "menutupi" celah
   navigasi ini; perbaiki sidebar lebih dulu atau tulis jalur masuk yang sebenarnya.
4. **Tab Master Bahan.** Panduan menyebut tab "Terpakai / Belum Terpakai"; label aktual
   "Dipakai Gedung / Tidak Dipakai Gedung" (`bahan/index.php:166–167`).
5. **Panduan mengklaim perilaku yang tidak lengkap.** Invoice dinyatakan "dapat diubah,
   dihapus" tanpa syarat; kenyataannya invoice yang punya jurnal pembayaran aktif
   **terkunci** (`Invoice.php:74,94`). Panduan juga tidak menyebut **gerbang peran**
   (`penjagaPeran`), status akun `harus_ganti_sandi` yang memaksa ganti sandi
   (`AuthFilter.php:16–20`), dan hak akses bawaan per peran.
6. **Versi dan QA.** Header panduan "22 November 2026" padahal sudah memuat fitur
   24 November; lampiran QA "34/34 + 11/11" hanya mencakup halaman 22 November, bukan
   Acuan, Validasi Harga, Simulasi, SPK Gabungan, Template Laporan, atau Tuntaskan SPK.

### B. Modul/fitur baru yang belum terdokumentasi

- Dashboard: kolom **Nilai BOQ Asli / Nilai Analisa / Perubahan** (`dashboard/index.php:60`)
  dan 8 jenis peringatan (tahap 2 jatuh tempo, pemecahan pembelian, ALERT master,
  bahan tanpa harga, upah tanpa tarif) — panduan baru menyebut 3.
- Master Bahan: panel **Kelengkapan Harga & Tarif**, **Gabungkan Bahan Manual (Nama Berbeda)**,
  kolom **Tarif Analisa per Gedung**, tombol **Rapikan ke 2 Jenis**, dan aturan sinkron
  harga sadar satuan (komponen beda satuan dilewati, bukan disalin mentah).
- Analisa: editor komponen **modal live** (bukan form halaman), panel **Dipakai di Pekerjaan**,
  kolom **Koef. Acuan & Nilai Acuan**, pencarian yang menemukan analisa dari nama bahan
  (`data-cari-teks`), dan aturan "perbaikan koefisien tetap di Analisa, acuan hanya pembanding".
- BOQ Gedung: **subtotal per bab** pada baris judul bab, kode analisa sebagai tautan,
  dan dasar penilaian AHSP berjalan (bukan `harga_satuan` mentah).
- Rencana Mingguan: tombol **Ambil Target dari Kurva S**, aturan daftar belanja bersih,
  serta SPK gabungan + Buku Material per gedung (SPK gabungan sudah ditulis, detail
  "stok dikurangi sekali lalu dialokasikan" perlu ditegaskan sebagai aturan tetap).
- Template Laporan: katalog penanda, aturan baris berulang, dan penjaga "render batal bila
  ada penanda kosong" (sudah ditulis, perlu dipindahkan ke daftar menu yang benar).
- Sisa Bahan: basis Qty RAB dari kebutuhan analisa gedung didanai + status "Tidak ada kebutuhan".
- Konvensi antarmuka yang belum pernah didokumentasikan sebagai aturan pengguna:
  tanggal **dd/mm/yyyy**, isian uang berformat ribuan (`data-uang`), select pencarian,
  **Tabel Standar v2** (urut klik header, ekspor CSV baris tersaring, empty state,
  status tersimpan per sesi, semua baris tercetak), modal konfirmasi, tema gelap,
  zoom A−/A+ yang tidak mengubah ukuran logo/profil, serta tautan lintas modul.

### C. Dokumen yang belum ada

- Referensi skema/relasi (33 tabel, soft delete, isolasi `sekolah_id`, relasi id vs kode).
- Matriks hak akses per peran dan aksi (bukan hanya menu).
- Runbook pasang/upgrade: `php spark migrate`, seeder, `impor:json`, `impor:perencana`,
  `impor:kurva-s`, `impor:acuan`, folder `writable` (termasuk `template-laporan`),
  backup/pulihkan, dan catatan driver coverage PHPUnit.
- Panduan kontribusi/uji: `composer test`, `php -l`, `node --check`, cakupan QA browser.
- Changelog/rilis per migrasi agar pengguna tahu apa yang baru setelah `git pull`.

## 4. Prioritas pengembangan dokumentasi

### P1 — Kebenaran dan keamanan informasi (kerjakan lebih dulu)

| # | Pekerjaan | Target berkas | Ukuran |
|---|---|---|---|
| 1.1 | Perbaiki semua petunjuk upah: hanya Pekerja & Tukang, jelaskan tombol **Rapikan ke 2 Jenis**, hapus panduan mengisi Mandor/Kepala Tukang. Selaraskan teks bantuan UI `upah/index.php` dan pertimbangkan menghapus input "jenis bebas". | `docs/Panduan_Penggunaan.md` (Bab 6, 9, FAQ), `app/Views/upah/index.php` | S |
| 1.2 | Samakan nama grup/menu dengan sidebar aktual (Material, Keuangan, Laporan, Manajemen User, Akses Menu) dan tambahkan grup **Keuangan** pada daftar tampilan. | `docs/Panduan_Penggunaan.md` Bab 2, 10, 16, 17, 18 | S |
| 1.3 | Selesaikan navigasi **Simulasi Mingguan** dan **Template Laporan** di sidebar (kode), lalu tulis menu yang benar beserta jalur alternatifnya. Bila belum diperbaiki, dokumentasikan jalur sebenarnya dan tandai sebagai keterbatasan. | `app/Views/layout/main.php`, `docs/Panduan_Penggunaan.md` Bab 8, 16 | S–M |
| 1.4 | Dokumentasikan pembatas penting: invoice terkunci setelah ada jurnal, gerbang peran (jurnal/penerimaan/bukti setor/upah/kas = bendahara; pengesahan revisi = ketua/kepsek; impor & samakan harga = operator; kosongkan = admin), serta status akun & paksaan ganti sandi. | `docs/Panduan_Penggunaan.md` Bab 2, 13, 14, 18 | M |
| 1.5 | Perbaiki `README`: jumlah tabel (33), rentang migrasi (000028), langkah upgrade terbaru, dan tautan ke `docs/`. | `README.md` | S |
| 1.6 | Tangani 32 gambar yang hilang: tambahkan aset screenshot (dan alt text) atau ganti dengan blok teks/tabel. Jangan biarkan tautan rusak. | `docs/Panduan_Penggunaan.md` + folder aset | M |

**Kriteria selesai P1:** tidak ada lagi petunjuk yang bertentangan dengan aturan
`AGENTS.md`; semua nama menu yang ditulis ada di sidebar; langkah upgrade menyebut
migrasi terakhir yang benar; tidak ada tautan gambar rusak.

### P2 — Kelengkapan modul dan konvensi

| # | Pekerjaan | Target berkas | Ukuran |
|---|---|---|---|
| 2.1 | Tambahkan panel/kolom baru Dashboard (BOQ Asli/Analisa/Perubahan) dan daftar lengkap "Perlu perhatian" beserta tautan tindak lanjutnya. | Panduan Bab 5 | S |
| 2.2 | Lengkapi Master Bahan: tab baru, Kelengkapan Harga, Gabung Manual, Tarif Analisa per Gedung, sinkron sadar satuan (komponen dilewati), lingkup gedung didanai, serta tombol Rapikan. | Panduan Bab 10 | M |
| 2.3 | Lengkapi Analisa: modal live (tambah/ubah komponen), konversi satuan, panel Dipakai di Pekerjaan, kolom acuan, pencarian dari nama bahan. | Panduan Bab 4 "Analisa Pekerjaan" | M |
| 2.4 | Lengkapi Gedung: subtotal per bab, tautan kode analisa, dasar nilai AHSP berjalan, dan perbandingan nilai BOQ asli vs analisa. | Panduan Bab 6 | S–M |
| 2.5 | Lengkapi Rencana Mingguan (target dari Kurva S) dan tegaskan aturan SPK gabungan + Buku Material per gedung. | Panduan Bab 8 | S |
| 2.6 | Tambahkan bab/section **Konvensi Antarmuka**: tanggal dd/mm/yyyy, isian uang, select pencarian, Tabel Standar v2 (urut/CSV/kosong/cetak/sesi), modal, zoom & tema, tautan lintas modul. | Panduan bab baru (setelah Bab 2) | M |
| 2.7 | Perbarui QA: jalankan ulang uji browser untuk modul 23–24 November, catat tanggal, lingkup, hasil, dan yang belum diuji; tambahkan status uji otomatis `composer test` (9 tes) dan catatan peringatan coverage. | Panduan Bab 21, README | M |
| 2.8 | Tulis ulang `ui-design-review.md` sebagai tinjauan v2 (atau beri header "arsip historis 21 Nov") karena rujukan baris, status sidebar terang, dan saran `.panel { overflow-x:auto }` sudah tidak berlaku (`app.css:2543–2545` memakai `overflow: clip`). | `docs/ui-design-review.md` | M |

**Kriteria selesai P2:** setiap menu di sidebar punya penjelasan langkah kerja yang
sesuai tampilan sekarang; tidak ada fitur 000012–000028 yang hanya tercatat di commit.

### P3 — Struktur, referensi, dan otomatisasi

| # | Pekerjaan | Target berkas | Ukuran |
|---|---|---|---|
| 3.1 | Ubah `README.md` menjadi halaman indeks singkat (ringkasan, prasyarat, tautan ke dokumen lain) dan pindahkan detail ke `docs/`. | `README.md` | S |
| 3.2 | Buat `docs/Arsitektur_Data.md`: 33 tabel, relasi id (`analisa_id`, `upah_id`, `gedung_id`), soft delete, isolasi sekolah, aturan unik. | baru | L |
| 3.3 | Buat `docs/Matriks_Hak_Akses.md`: peran × menu (Akses Menu) × aksi sensitif (penjagaPeran) + alur akun baru/wajib ganti sandi. | baru | M |
| 3.4 | Buat `docs/Panduan_Instalasi_Upgrade.md`: instalasi XAMPP, `.env`, migrasi 000001–000028, seeder, 4 perintah impor, folder writable, backup/pulihkan, PHPUnit. | baru | M |
| 3.5 | Buat `docs/Kontribusi_dan_Pengujian.md`: standar `php -l`, `node --check`, `composer test`, QA browser, daftar periksa UI (tema, zoom, cetak, mobile). | baru | S |
| 3.6 | Tambahkan `docs/CHANGELOG.md` (atau catatan rilis per migrasi) agar pengguna tahu perubahan setelah `git pull`. | baru | S |
| 3.7 | Otomatisasi penjagaan dokumentasi: uji yang membandingkan katalog `AksesMenu` dengan tautan sidebar, pemeriksa tautan/gambar markdown, dan daftar perintah `spark` yang dihasilkan dari `app/Commands`. | `tests/`, skrip kecil | M |
| 3.8 | Beri setiap dokumen header "terakhir diverifikasi" + glosarium istilah (BOQ, AHSP, SPK, BAP/BAST, LPJ) dan hindari menduplikasi aturan `AGENTS.md` — cukup tautkan. | semua `.md` | S |

**Kriteria selesai P3:** README pendek dan akurat, ada referensi data/hak akses/instalasi,
dan perubahan menu yang lupa didokumentasikan tertangkap oleh pemeriksaan otomatis.

## 5. Urutan eksekusi yang disarankan

1. P1.1–P1.5 (satu putaran kecil: upah, menu, README, pembatas peran).
2. P1.3 (perbaikan sidebar) + P1.6 (aset gambar) — dua pekerjaan yang menyentuh kode/aset.
3. P2.1–P2.6 (kelengkapan panduan per modul), lalu P2.7 (QA ulang) dan P2.8 (tinjauan desain).
4. P3.1–P3.6 (dokumen referensi baru), lalu P3.7 (otomatisasi) dan P3.8 (header/glosarium).

## 6. Definition of done

- [ ] Setiap nama menu, label tombol, dan nama tab di panduan ada di kode (bisa dicek otomatis).
- [ ] Tidak ada instruksi yang bertentangan dengan `AGENTS.md` (terutama upah, peran, invoice, acuan).
- [ ] Rentang migrasi & jumlah tabel di README benar; langkah upgrade diuji pada database dev.
- [ ] Tidak ada tautan/gambar rusak; semua gambar punya alt text.
- [ ] Modul 000012–000028 tercakup minimal satu paragraf + satu alur kerja.
- [ ] QA menyebut tanggal, lingkup, hasil, dan yang belum diuji (jujur, tanpa klaim berlebih).
- [ ] `composer test` dan pemeriksaan dokumentasi otomatis lulus.

## 7. Catatan pekerjaan kode yang mendahului dokumentasi

Dua hal ini bukan pekerjaan dokumentasi, tetapi harus diputuskan sebelum panduan
ditulis ulang agar dokumen tidak mengabadikan keadaan sementara:

1. Tautan sidebar untuk **Simulasi Mingguan** dan **Template Laporan** (kini tidak ada).
2. Teks bantuan & input bebas jenis upah di `app/Views/upah/index.php` yang masih
   menyebut Mandor/Kepala Tukang.
