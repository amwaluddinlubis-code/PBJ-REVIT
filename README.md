# Aplikasi PBJ Sekolah (CodeIgniter 4)

Konversi workbook `Aplikasi_PBJ_Sekolah` (Excel) menjadi aplikasi web CI4 + MariaDB.
Data awal diimpor dari workbook v4 (master 95 bahan, 5 SPK, 169 baris detail, 1 invoice).

README ini adalah **indeks singkat**. Dokumentasi lengkap ada di folder `docs/`:
lihat [`docs/Panduan_Penggunaan.md`](docs/Panduan_Penggunaan.md) (panduan pengguna),
[`docs/Panduan_Instalasi_Upgrade.md`](docs/Panduan_Instalasi_Upgrade.md) (instalasi/upgrade),
[`docs/Arsitektur_Data.md`](docs/Arsitektur_Data.md) (skema data),
[`docs/Matriks_Hak_Akses.md`](docs/Matriks_Hak_Akses.md) (peran & akses),
[`docs/Kontribusi_dan_Pengujian.md`](docs/Kontribusi_dan_Pengujian.md) (kontribusi/uji),
[`docs/CHANGELOG.md`](docs/CHANGELOG.md) (riwayat migrasi), dan
[`AGENTS.md`](AGENTS.md) (aturan pengembangan wajib).

## Pengujian Otomatis & CI

Jalankan seluruh pengujian dengan `composer test` (PHPUnit). Ada dua lapis:

- `tests/Unit` — fungsi murni (terbilang, format rupiah, ketentuan PBJ, pajak,
  faktor satuan) dan konsistensi dokumentasi; tidak butuh database.
- `tests/Database` — mesin uang yang butuh database: harga berjalan/AHSP dan
  total BOQ, penomoran SPK (termasuk aturan nomor SPK terhapus), kunci invoice
  berjurnal pembayaran, dan hitungan kebutuhan bersih Rencana Belanja
  (`hitungPilihan`). Pengujian memakai data sintetis di database khusus tes.

Database tes memakai MySQL/MariaDB (grup koneksi `tests` di
`app/Config/Database.php`): buat database `pbj_test` dan pengguna
`pbj_test`/`pbj_test` dengan hak penuh atas database itu, misalnya:

```sql
CREATE DATABASE pbj_test;
CREATE USER 'pbj_test'@'localhost' IDENTIFIED BY 'pbj_test';
GRANT ALL PRIVILEGES ON pbj_test.* TO 'pbj_test'@'localhost';
```

JANGAN pernah menunjuk grup `tests` ke database produksi — rangkaian
pengujian mengosongkan dan memigrasi ulang database tes dari nol.

GitHub Actions (`.github/workflows/ci.yml`) menjalankan validasi composer,
lint PHP, dan `composer test` dengan layanan MariaDB pada setiap push dan
pull request ke `main`.

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
- Struktur view mengikuti pola komponen ala TALL di CI4 (Tailwind + Alpine.js + partial server): view panjang dipecah menjadi partial `_<nama>.php` di folder yang sama dan dirangkai dengan `$this->include()`; layout utama dirangkai dari `app/Views/layout/_*.php`. Formatter bersama (`rp()`, `tgl()`, `ikonSvg()`) ada di `app/Common.php`. Aturan lengkapnya di AGENTS.md bagian "Komponen View".

## Dokumentasi

- Panduan pengguna lengkap: [`docs/Panduan_Penggunaan.md`](docs/Panduan_Penggunaan.md)
  (alur kerja per modul, konvensi antarmuka, FAQ, dan lampiran QA).
- Rencana pengembangan dokumentasi: [`docs/Rencana_Pengembangan_Dokumentasi.md`](docs/Rencana_Pengembangan_Dokumentasi.md).
- Aturan pengembangan wajib: [`AGENTS.md`](AGENTS.md).
- Uji otomatis: `composer test` (PHPUnit, 9 tes fungsi dasar `BaseController`).
  Cek sintaks PHP: `php -l <berkas>`; cek JavaScript: `node --check <berkas>`.

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

Skema: 41 tabel aplikasi (42 termasuk tabel `migrations` yang dicatat CodeIgniter),
dimulai dari 14 tabel inti di atas dan bertambah melalui migrasi 000002–000028:
gedung, pekerjaan, analisa, analisa_komponen, upah_master, realisasi, upah_bayar,
impor_log, sekolah, biaya_lain, kas_transaksi, bukti_setor, foto_progres,
dokumen_spk, revisi_pekerjaan, bahan_konversi, audit_events, rencana_mingguan,
rencana_mingguan_detail, aplikasi_pengaturan, kurva_minggu, kurva_rencana,
akses_menu, ahsp_acuan, ahsp_acuan_komponen, spk_tenaga, simulasi_realisasi,
laporan_template, dan lain-lain. Lihat `app/Database/Migrations/` untuk urutan
dan detail kolom.

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

Langkah sesudah `git pull`: jalankan `php spark migrate` (migrasi terakhir saat ini 000028),
lalu buka menu **Impor Dokumen Perencana** dan unggah workbook RAB perencana sekolah.

## Kesesuaian dengan Juknis Revitalisasi SMP 2026

Aturan Panduan Pelaksanaan Bantuan Pemerintah Program Revitalisasi Satuan
Pendidikan 2026 (Direktorat SMP) yang kini diterapkan aplikasi:

- **PPh 22 = 1,5% dari DPP** (nilai tagihan di luar PPN), dan **tidak dipungut**
  untuk pembelian sampai dengan Rp2.000.000. Berlaku di Jurnal Pembayaran,
  cetak Invoice, Dashboard, dan Laporan (helper `pajakPembelian`).
- **PPN 11% dipungut bendahara** dari bagian harga yang kena PPN dan tampil
  sebagai kolom tersendiri di Jurnal Pembayaran; nilai dibayar ke penyedia =
  nilai tagihan − PPN dipungut − PPh 22.
- **Ambang dokumen & penyedia** (Permendikbudristek 18/2022): nota sampai
  Rp10 juta, kuitansi Rp10–50 juta, SPK di atas Rp50 juta; calon penyedia
  minimal 1 (≤ Rp50 juta), 2 (Rp50–200 juta), 3 (> Rp200 juta). Panel
  ketentuan tampil di detail SPK (dengan jumlah toko pembanding dari Survei
  Harga) dan penjelasan di halaman Survei Harga.
- **Biaya Non-Gedung**: tiap baris dapat diberi jenis pajak yang dipotong saat
  pembayaran — PPh 23 jasa/sewa 2%, PPh 4 ayat 2 perencana/pengawas 3,5%
  (ber-SKA) / 6% (tanpa SKA), PPh 21 honor (Gol. IV 15%, Gol. III 5%,
  Gol. II 0%, non-PNS 5% dari 50%) — kolom Potongan Pajak dan Bersih Diterima
  tampil di tabel (migrasi 000007 menambah kolom `pajak`).
- **PPh 21 upah harian**: upah sampai Rp450.000/hari tidak dipotong; di
  atasnya dipotong 5% dari kelebihan per hari. Tampil di Riwayat Pembayaran
  Upah (batas kumulatif Rp4,5 juta dalam 22 hari kerja per bulan untuk
  penerima yang sama dicek manual).
- **Bea materai**: pengingat materai Rp10.000 untuk kuitansi pembayaran
  Rp5 juta ke atas (di Jurnal Pembayaran, detail SPK, dan cetak Invoice).

Pengembangan lanjutan berdasarkan Juknis (migrasi 000008–000011):

- **Pembukuan (menu Buku Kas)**: Buku Kas Umum, Buku Bank, dan Buku
  Pembantu Kas Tunai yang ditutup setiap akhir bulan, tergabung otomatis
  dari pembayaran invoice (bank), pembayaran upah (tunai), bukti setor,
  dan transaksi kas manual; cetak mengikuti format dokumen Juknis.
- **Penerimaan Dana**: pencairan bertahap Tahap 1 (70%) dan Tahap 2 (30%,
  syarat kemajuan fisik ≥ 50%) dengan target, status, peringatan jatuh
  tempo di Dashboard, dan cetak Kuitansi Penerimaan Uang format Juknis.
- **Laporan Kemajuan & Laporan Akhir**: pernyataan kemajuan penyelesaian
  pekerjaan untuk pengajuan tahap 2 (dilampiri rincian per gedung dan
  dokumentasi foto) dan laporan akhir 100% berisi dana awal, dana yang
  digunakan, dan sisa dana.
- **Foto Progres**: dokumentasi foto kondisi sebelum, selama (proses),
  dan akhir pekerjaan per gedung sebagai lampiran laporan.
- **Bukti Setor & Pajak**: kewajiban pajak per jenis dan masa (PPh 22,
  PPN, PPh 21, PPh 23, PPh 4 ayat 2) dibandingkan bukti setor e-billing/
  NTPN; sisa dana bantuan dan pengembaliannya ke kas negara via SIMPONI
  terpantau di halaman yang sama.
- **Ceklis Kelengkapan Dokumen per SPK**: status otomatis dari modul
  survei, negosiasi, BAP, BAST, invoice, dan pembayaran, ditambah penanda
  manual dokumen perencanaan dan kuitansi fisik.
- **Kurva S**: jadwal pelaksanaan per gedung — garis rencana berbentuk S
  dibandingkan realisasi kumulatif mingguan beserta deviasinya.
- **Berita Acara Revisi Pekerjaan**: pencatatan dan cetak BA setiap
  perubahan volume/biaya pekerjaan.
- **BAST Aset** per gedung (di halaman LPJ Gedung), **Papan Nama
  Kegiatan** (di halaman BAP & BAST), **cetak Daftar Pembayaran Upah**
  bulanan dengan kolom tanda tangan penerima, dan **deteksi pola
  pemecahan pembelian** di Dashboard.
- **Master Bahan & Upah** (satu menu): master bahan dan master upah
  menjadi referensi bersama semua modul. **ALERT** muncul di Dashboard
  dan di halaman master bila ada bahan/jenis tenaga yang sama dengan
  satuan atau harga berbeda; alurnya **perbaiki dulu** (tentukan satuan
  & harga/tarif final) **barulah gabungkan** ke master — penggabungan
  mengalihkan semua referensi (SPK detail, komponen analisa, survei
  harga, negosiasi, buku material) dan menjumlahkan kuantitas RAB.
  Menambah bahan/jenis dengan nama yang sama kini ditolak.
- **RAB Gabungan**: selain RAB per gedung di LPJ Gedung, tersedia RAB
  gabungan beberapa gedung (pekerjaan yang sama dijumlahkan volumenya
  lintas gedung) lengkap dengan cetaknya.
- **Pembersihan Hasil Impor** (menu tersendiri, wizard 3 langkah):
  pekerjaan bervolume < 1 adalah sisa baris template perencana yang
  analisanya tidak akan terpakai. Langkah 1 menandai pekerjaan tak
  terpakai (volume 0 tercentang bawaan; volume pecahan menunggu
  keputusan; tersedia mode massal "Pilih Semua Volume 0 / < 1" karena
  formulir ribuan centang melampaui batas POST PHP; pekerjaan yang
  sudah punya realisasi selalu dikecualikan pada mode massal).
  Langkah 2 menawarkan analisa yang hanya dirujuk pekerjaan terpilih
  untuk ikut dibuang. Langkah 3 menawarkan bahan "yatim" — Kelompok A
  hanya dipakai analisa yang dibuang (tercentang bawaan), Kelompok B
  tidak dirujuk apa pun (tidak tercentang bawaan); bahan yang masih
  dirujuk analisa aktif, SPK, survei, negosiasi, atau buku material
  terlindungi dan tidak ditawarkan. Eksekusi bersifat hapus lunak dan
  nilai BOQ gedung dihitung ulang dari pekerjaan yang tersisa.

Langkah sesudah `git pull` versi ini: jalankan `php spark migrate`
(migrasi 000001–000028; semua migrasi bersifat idempoten dan menambahkan kolom
bila belum ada).
