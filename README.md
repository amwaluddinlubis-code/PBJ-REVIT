# Aplikasi PBJ Sekolah (CodeIgniter 4)

Konversi workbook `Aplikasi_PBJ_Sekolah` (Excel) menjadi aplikasi web CI4 + MariaDB.
Data awal diimpor dari workbook v4 (master 95 bahan, 5 SPK, 169 baris detail, 1 invoice).

## Menjalankan di PC (XAMPP)

1. Letakkan folder proyek di dalam `htdocs` XAMPP (contoh: `D:\xampp\htdocs\pbj-revit`).
   Nama folder menentukan `app.baseURL` — jangan diganti tanpa menyesuaikan `.env`.
2. Jalankan Apache & MySQL dari XAMPP Control Panel. Periksa port Apache di
   `xampp\apache\conf\httpd.conf` (baris `Listen`, contoh `Listen 81`).
3. Buat database `pbj_sekolah` lewat phpMyAdmin.
4. Sesuaikan `.env`:
   - `app.baseURL = 'http://localhost:81/pbj-revit/public/'`
     (port = port Apache, path = nama folder + `/public/`, **bukan** `index.php`)
   - database: hostname `localhost`, database `pbj_sekolah`, username `root`,
     password kosong (bawaan XAMPP), driver MySQLi, port `3306`.
   - Pastikan ekstensi PHP aktif di `php.ini` XAMPP: `intl`, `mbstring`, `mysqli`, `zip`.
5. Jalankan migrasi + data awal dari folder proyek (`D:\xampp\php\php.exe spark ...`
   bila `php` tidak ada di PATH):
   - `php spark migrate`
   - `php spark db:seed PenggunaAwalSeeder` → login awal **admin / admin123**
   - `php spark impor:json` → mengimpor `writable/impor.json` (ekstrak workbook v4)
6. Buka `http://localhost:81/pbj-revit/public/` di browser.
   Alternatif tanpa Apache: `php spark serve --port 8080` lalu buka http://localhost:8080
   (sesuaikan `app.baseURL` menjadi `http://localhost:8080/`).

### Jika aplikasi tidak bisa dibuka

- Redirect ke URL yang salah (mis. `/pbj_revit/` tanpa `/public/`) → `app.baseURL`
  di `.env` tidak cocok dengan lokasi/port Apache.
- Halaman error "Table ... doesn't exist" → `php spark migrate` belum dijalankan.
- `CacheException`/folder tidak writable → pastikan `writable/cache`, `writable/logs`,
  `writable/session`, `writable/debugbar`, `writable/uploads` ada dan dapat ditulis.

## Tampilan: IDDS + Tailwind

Antarmuka memakai **INA Digital Design System (IDDS)** dikombinasikan dengan **Tailwind CSS**:

- `public/assets/idds.css` — token resmi IDDS (`@idds/styles` v1.6.41): variabel warna `--ina-*`, font Inter.
- `public/assets/tailwind.css` — Tailwind v4 hasil build (preflight + utilitas yang dipakai view + palet IDDS sebagai utilitas, mis. `bg-blue-600` = biru IDDS `#0049b8`). Membangun ulang: `npx @tailwindcss/cli -i input.css -o public/assets/tailwind.css` dengan `@source` menunjuk `app/Views` dan tema dari `@idds/styles/tailwind/css/idds.css`.
- `public/assets/app.css` — komponen aplikasi (sidebar, tombol, panel, tabel) ditulis di atas token `--ina-*`.
- Form memakai grid Tailwind 2 kolom (`grid md:grid-cols-2`); tabel data memakai paginasi klien (`public/assets/paginasi.js`, atribut `data-paginasi` pada tabel; baris total beri kelas `baris-total`).

## Status

- Fase 1 (selesai): login, Dashboard (KPI + peringatan otomatis), Master Bahan (CRUD),
  Register SPK + Detail (dropdown bahan, harga acuan berjenjang negosiasi→penawaran→RAB),
  halaman "Belum Bernomor SPK" untuk memasangkan 159 baris warisan workbook ke SPK.
- Fase 3 (selesai): Cetak Invoice (nomor 422/INV/..., terbilang, catatan selisih nilai),
  halaman Laporan (ringkasan RAB vs realisasi, anggaran per gedung RKB/ADM/Toilet,
  rekap per SPK) + Cetak Laporan ber-kop sekolah aktif, dan ekspor Excel (.xlsx)
  untuk Rekap SPK, Buku Material, dan Sisa Bahan — semua mengikuti sekolah aktif.
- Multi-sekolah: satu instalasi melayani banyak sekolah. Data bahan/penyedia/SPK
  (dan seluruh turunannya) terpisah penuh per sekolah; sekolah aktif dipilih lewat
  pengalih di menu samping atau halaman Kelola Sekolah. Sekolah baru mulai dari data
  kosong; nomor SPK dan kode bahan berlaku per sekolah. Sesudah `git pull` versi ini,
  jalankan `php spark migrate` (migration 000002 MultiSekolah).
- Fase 2 (selesai): Survei Harga (termurah/terpilih otomatis) & Negosiasi (total + hemat),
  BAP/BAST per SPK + cetak dokumen SPK/BAP/BAST (kop dari Identitas, nomor 422/... otomatis,
  terbilang otomatis), Invoice (peringatan nilai != total SPK) & Jurnal Pembayaran
  (PPh 22 1,5% otomatis), Buku Material (saldo berjalan) & Sisa Bahan,
  Identitas & Pengaturan + master Penyedia.

Skema: 14 tabel (users, identitas, bahan, penyedia, spk, spk_detail, survei_harga,
negosiasi, bap, bap_detail, bast, invoice, pembayaran, buku_material).

---

## Modul Analisa Perencana (Dokumen Perencana → Perencanaan → Belanja → LPJ)

Aplikasi ini kini juga membaca **workbook RAB perencana** (BOQ per gedung + analisa AHSP +
daftar harga bahan & upah) dan menjadikannya dasar seluruh alur:

1. **Impor Dokumen Perencana** (menu tersendiri): unggah workbook .xlsx — aplikasi membaca
   nilai tersimpan berkas (tanpa mengandalkan hitung ulang rumus) lalu mengimpor daftar harga
   (bahan tertaut ke master / dibuat baru; upah menjadi Master Tarif), analisa AHSP per kode,
   BOQ per gedung (setiap sheet gedung menjadi satu menu gedung), dan total anggaran dari
   lembar rekap. Halaman impor menampilkan verifikasi per gedung: total terhitung aplikasi vs
   GRAND TOTAL pada sheet.
2. **Menu Gedung** (sidebar, satu per gedung): tab BOQ (volume dapat diubah — nilai BOQ,
   kebutuhan, dan rencana belanja ikut berubah), Kebutuhan Bahan (volume BOQ × koefisien AHSP;
   komponen yang belum tertaut ke master bahan dapat ditautkan dari tab ini), Kebutuhan Upah
   (orang-hari × tarif), dan Realisasi (catat volume terpasang per pekerjaan → persen fisik).
3. **Rencana Belanja**: turunan hitungan kebutuhan (bukan daftar bebas) — centang bahan dan
   atur qty, lalu **Buat SPK dari Pilihan**: SPK terbentuk tertaut ke gedung dengan harga acuan
   (negosiasi → penawaran → RAB). Upah direncanakan dari kebutuhan OH yang sama.
4. **Pembayaran Upah**: catat bayar tenaga per gedung/pekerjaan; tarif master dapat diubah.
5. **Kontrol pagu**: total belanja terbayar (invoice lunas + upah) tidak boleh melewati
   **Total Anggaran** sekolah — pembayaran yang menembus pagu ditahan aplikasi. Harga satuan
   belanja boleh melebihi harga dokumen (deviasi tampil sebagai selisih); yang dikunci hanya
   total keseluruhan. Total anggaran diatur di halaman Sekolah / terisi otomatis saat impor.
6. **LPJ & Realisasi**: rekap per gedung dan gabungan sekolah (BOQ vs fisik, kebutuhan vs
   belanja, upah) + cetak LPJ sekolah dan per gedung ber-kop resmi.

Catatan impor: konversi satuan mengikuti praktik AHSP — semen kg→zak (÷50), pasir kg→m³
(÷1.400), kerikil/batu kg→m³ (÷1.600), keramik bh→ktk (menurut ukuran). Baris yang satuannya
berbeda tanpa aturan konversi ditandai "satuan beda" agar diperiksa pengguna.

Langkah sesudah `git pull` di PC: jalankan `php spark migrate` (migrasi 000003), lalu buka
menu **Impor Dokumen Perencana** dan unggah workbook RAB perencana sekolah.
