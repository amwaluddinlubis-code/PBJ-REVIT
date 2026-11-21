# Aplikasi PBJ Sekolah (CodeIgniter 4)

Konversi workbook `Aplikasi_PBJ_Sekolah` (Excel) menjadi aplikasi web CI4 + MariaDB.
Data awal diimpor dari workbook v4 (master 95 bahan, 5 SPK, 169 baris detail, 1 invoice).

## Menjalankan di PC (XAMPP)

1. Salin folder ini ke `C:\xampp\htdocs\pbj-sekolah`.
2. Jalankan Apache & MySQL dari XAMPP Control Panel.
3. Buat database `db_pbj` lewat phpMyAdmin (http://localhost/phpmyadmin).
4. Sesuaikan `.env`:
   - `app.baseURL = 'http://localhost/pbj-sekolah/public/'`
   - database: hostname `127.0.0.1`, database `db_pbj`, username `root`, password kosong
     (bawaan XAMPP), driver MySQLi.
   - Pastikan ekstensi PHP aktif di `php.ini` XAMPP: `intl`, `mbstring`, `mysqli`, `zip`.
5. Dari Command Prompt di folder proyek (`C:\xampp\php\php.exe spark ...` bila php tidak di PATH):
   - `php spark migrate`
   - `php spark db:seed PenggunaAwalSeeder` → login awal **admin / admin123**
   - `php spark impor:json` → mengimpor `writable/impor.json` (ekstrak workbook v4)
6. Buka http://localhost/pbj-sekolah/public/ di browser.
   Alternatif tanpa memindah folder: `php spark serve --port 8080` lalu buka http://localhost:8080.

## Status

- Fase 1 (selesai): login, Dashboard (KPI + peringatan otomatis), Master Bahan (CRUD),
  Register SPK + Detail (dropdown bahan, harga acuan berjenjang negosiasi→penawaran→RAB),
  halaman "Belum Bernomor SPK" untuk memasangkan 159 baris warisan workbook ke SPK.
- Fase 2 (selesai): Survei Harga (termurah/terpilih otomatis) & Negosiasi (total + hemat),
  BAP/BAST per SPK + cetak dokumen SPK/BAP/BAST (kop dari Identitas, nomor 422/... otomatis,
  terbilang otomatis), Invoice (peringatan nilai != total SPK) & Jurnal Pembayaran
  (PPh 22 1,5% otomatis), Buku Material (saldo berjalan) & Sisa Bahan,
  Identitas & Pengaturan + master Penyedia.

Skema: 14 tabel (users, identitas, bahan, penyedia, spk, spk_detail, survei_harga,
negosiasi, bap, bap_detail, bast, invoice, pembayaran, buku_material).
