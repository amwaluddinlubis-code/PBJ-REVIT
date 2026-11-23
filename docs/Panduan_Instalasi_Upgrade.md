# Panduan Instalasi & Upgrade

- Terakhir diverifikasi: 24 November 2026 (migrasi 000028)
- Sumber: `README.md`, `app/Database/Migrations/`, `app/Commands/`

## 1. Prasyarat

- XAMPP (Apache + MariaDB/MySQL) dengan PHP 8.2+.
- Ekstensi PHP aktif: `intl`, `mbstring`, `mysqli`, `zip`.
- Folder proyek di `htdocs` (contoh `D:\xampp\htdocs\pbj-revit`). Nama folder
  menentukan `app.baseURL` — jangan diganti tanpa menyesuaikan `.env`.

## 2. Instalasi baru

1. Buat database `pbj_sekolah` lewat phpMyAdmin.
2. Sesuaikan `.env`:
   - `app.baseURL = 'http://localhost:81/pbj-revit/public/'`
     (port = port Apache, path = nama folder + `/public/`, bukan `index.php`)
   - database: hostname `localhost`, database `pbj_sekolah`, username `root`,
     password kosong (bawaan XAMPP), driver MySQLi, port `3306`.
3. Jalankan dari folder proyek (`D:\xampp\php\php.exe spark ...` bila `php`
   tidak ada di PATH):
   - `php spark migrate`
   - `php spark db:seed PenggunaAwalSeeder` → login awal **admin / admin123**
   - `php spark impor:json` → mengimpor `writable/impor.json` (ekstrak workbook v4)
4. Buka `http://localhost:81/pbj-revit/public/` di browser.
   Alternatif tanpa Apache: `php spark serve --port 8080` lalu buka
   http://localhost:8080 (sesuaikan `app.baseURL`).

Pastikan folder `writable/cache`, `writable/logs`, `writable/session`,
`writable/debugbar`, `writable/uploads`, dan `writable/impor` dapat ditulis.

## 3. Upgrade setelah `git pull`

1. Backup database dulu (Pemeliharaan Database → Backup SQL, atau ekspor
   phpMyAdmin).
2. Jalankan `php spark migrate` (migrasi terakhir saat ini **000028**; semua
   migrasi bersifat idempoten dan menambahkan kolom bila belum ada).
3. Bila tampilan masih versi lama: tekan Ctrl+F5 di peramban.
4. Untuk data lama pasca-upgrade, jalankan sekali tombol **Rapikan ke 2 Jenis**
   di halaman Master Bahan & Upah (bila masih ada varian upah).

## 4. Perintah impor CLI (`app/Commands/`)

| Perintah | Fungsi |
|---|---|
| `php spark impor:json [file] [sekolah_id]` | Mengimpor ekstrak workbook v4 (`writable/impor.json` secara bawaan). |
| `php spark impor:perencana <file> [sekolah_id]` | Mengimpor workbook RAB perencana langsung dari berkas. |
| `php spark impor:kurva-s <file.json> [sekolah_id]` | Mengimpor rencana Kurva S resmi dari berkas JSON (menggantikan rencana lama sekolah, tercatat di audit). |
| `php spark impor:acuan` | Memuat master Acuan AHSP Nasional dari `app/Data/ahsp_acuan.json` (idempoten, cocok lewat `kode_kita`). Jalankan sekali setelah instalasi. |

## 5. Folder writable penting

- `writable/impor/` — berkas .xlsx untuk impor Cara 1 (tanpa batas ukuran).
- `writable/template-laporan/` — berkas template .xlsx per jenis (spk/gedung/sekolah).
- `writable/uploads/` — foto progres dan unggahan.
- `writable/backup/` — cadangan SQL.
- `writable/cache`, `logs`, `session`, `debugbar` — wajib dapat ditulis.

## 6. Pengujian

- `composer test` — PHPUnit, 9 tes fungsi dasar `BaseController` (terbilang,
  rupiah, pajak, ketentuan PBJ, faktor satuan). Peringatan "No code coverage
  driver available" tidak menggagalkan tes.
- Cek sintaks PHP: `php -l <berkas>`.
- Cek JavaScript: `node --check <berkas>`.

## 7. Pemecahan masalah umum

- Redirect ke URL yang salah (mis. `/pbj_revit/` tanpa `/public/`) → `app.baseURL`
  di `.env` tidak cocok dengan lokasi/port Apache.
- Halaman error "Table ... doesn't exist" → `php spark migrate` belum dijalankan.
- `CacheException`/folder tidak writable → pastikan folder `writable/*` ada dan
  dapat ditulis.

Rujuk `README.md` untuk ringkasan proyek dan `AGENTS.md` untuk aturan pengembangan.