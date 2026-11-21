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

## Status

- Fase 1 (selesai): login, Dashboard (KPI + peringatan otomatis), Master Bahan (CRUD),
  Register SPK + Detail (dropdown bahan, harga acuan berjenjang negosiasi→penawaran→RAB),
  halaman "Belum Bernomor SPK" untuk memasangkan 159 baris warisan workbook ke SPK.
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
