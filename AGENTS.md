# Aturan Pengembangan Wajib

Aturan ini berlaku untuk semua perubahan dan kode baru di aplikasi Analisa Perencana. Stack utama adalah CodeIgniter 4.7, PHP 8.2+, MariaDB/MySQL, Tailwind CSS, IDDS, dan JavaScript browser. Ikuti pola repo yang sudah ada; aturan ini melengkapi, bukan mengganti, kontrak aplikasi.

## Cara Bekerja

- Sebelum mengubah kode, baca implementasi, pemanggil, helper, test, dan dokumentasi yang langsung terkait. Bentuk hipotesis lokal dan tentukan satu pemeriksaan yang dapat membuktikannya salah.
- Buat perubahan terkecil yang menyelesaikan kebutuhan. Jangan merapikan atau memformat ulang area yang tidak terkait.
- Pertahankan API, route, nama field, bentuk data, dan perilaku lama kecuali perubahan itu memang diminta.
- Gunakan utilitas dan komponen bersama yang sudah tersedia sebelum menambah helper, dependency, atau pola baru. Abstraksi harus mengurangi kompleksitas nyata, bukan hanya memindahkan kode.
- Jangan menganggap perubahan lokal sebagai sampah. Jangan membuang, menimpa, me-reset, atau meng-commit perubahan pengguna tanpa instruksi eksplisit.

## PHP dan CodeIgniter

- Ikuti autoload PSR-4 `App\\` di `app/`, gaya PHP file sekitar, dan API CodeIgniter 4. Jangan menambah dependency tanpa kebutuhan dan alasan yang jelas.
- Semua controller aplikasi memakai `BaseController` bila sesuai. Gunakan `tampil()` untuk halaman layout utama dan manfaatkan helper yang sudah ada seperti `sid()`, `ambilGedung()`, `hapusLunak()`, `idsTerpilih()`, serta fungsi hitung domain yang relevan.
- Controller menangani request, otorisasi, validasi, dan penyiapan data; view merender data. Jangan menaruh query database atau aturan bisnis utama di view.
- Daftarkan route privat dengan filter `auth`. Jangan memindahkan endpoint privat ke luar grup/filter untuk mempermudah pengujian atau pemanggilan.
- Ambil input dari Request CodeIgniter, validasi di server, normalisasi angka/tanggal sesuai format yang diterima aplikasi, dan gunakan `withInput()` bila alur form membutuhkannya.
- Query memakai Query Builder atau parameter binding. Jangan menyisipkan input pengguna langsung ke SQL.
- Escape output HTML dengan `esc()` sesuai konteks. Untuk data yang dimasukkan ke JavaScript, gunakan `json_encode()` dengan opsi encoding yang aman; jangan membangun kode JavaScript dari string pengguna.

## Data dan Database

- **Isolasi sekolah wajib dijaga.** Untuk data milik sekolah, gunakan sekolah aktif dari `sid()` dan sertakan `sekolah_id` pada baca, ubah, dan hapus. Jangan mempercayai `sekolah_id` dari form biasa; hanya alur tujuan-impor yang memang mendukung pilihan sekolah boleh menerimanya dan harus memvalidasinya.
- Untuk data turunan, pastikan kepemilikan melalui relasi induknya (misalnya pekerjaan melalui gedung, invoice melalui SPK). Validasi ID dan kepemilikan sebelum mengubah data.
- Gunakan soft-delete `is_deleted` / `deleted_at` dan `hapusLunak()` sesuai pola aplikasi. Jangan melakukan hard-delete pada alur bisnis biasa; pembaruan daftar aktif harus mengecualikan baris terhapus.
- Semua perubahan skema dibuat sebagai migration baru di `app/Database/Migrations/`. Jangan mengubah migration yang sudah mungkin dijalankan di instalasi pengguna. Pertahankan data, urutan migration, dan perilaku upgrade; uji pada database pengembangan yang benar.
- Operasi yang mengubah beberapa tabel atau menjaga relasi tetap konsisten harus memakai transaksi dan rollback saat gagal.
- Jangan mengubah data atau menjalankan migration terhadap database pengguna/produksi tanpa instruksi eksplisit.

## Keamanan dan Alur Data

- Setiap form POST harus menyertakan `csrf_field()` dan endpoint mutasi harus tetap terlindungi filter yang tepat.
- Validasi otorisasi dan aturan bisnis di server; atribut HTML atau JavaScript hanya membantu UX, bukan pengganti validasi.
- Untuk upload/impor, validasi ekstensi, MIME/isi, ukuran, nama/path, dan status hasil parsing. Jangan mempercayai nama file atau path dari request. Pertahankan sumber impor sesuai alur dan kebijakan aplikasi.
- Operasi massal harus membatasi ID ke sekolah dan jenis data yang benar, melewati data yang tidak berhak/terkait sesuai aturan domain, serta melaporkan hasil dengan jelas.
- Jangan mencatat atau menampilkan password, token, kredensial, isi `.env`, atau data sensitif dalam log, pesan error, maupun respons.
- Gunakan `PhpSpreadsheet` dan `Dompdf` yang sudah tersedia untuk spreadsheet dan PDF; jangan membuat parser/renderer ad hoc tanpa alasan kuat.

## Aturan UI Wajib

Sebelum mengubah tampilan, baca view, CSS, JavaScript, helper, dan dokumentasi desain yang terkait. Ikuti pola TailAdmin + token IDDS yang sudah digunakan; jangan menambah sistem visual kedua.

### Form Compact

- Form adalah alat kerja, bukan halaman penjelasan. Tampilkan field, nilai, dan aksi yang dibutuhkan tanpa paragraf pengantar panjang.
- Kelompokkan field yang saling terkait dalam grid dua kolom pada desktop dan satu kolom pada layar sempit. Pakai pola grid serta kelas form yang sudah ada.
- Jaga jarak vertikal rapat dan konsisten. Hindari panel kosong, kartu di dalam kartu, dan pembungkus dekoratif yang tidak menambah informasi.
- Gunakan label singkat di atas kontrol. Pindahkan contoh dan syarat ke hint satu baris; jangan ulangi isi label dalam placeholder.
- Tampilkan field opsional hanya bila diperlukan alur. Untuk opsi lanjutan atau jarang dipakai, gunakan `<details>` dengan judul dan ringkasan yang jelas.
- Letakkan aksi utama dekat akhir form dan bedakan secara visual dari aksi sekunder/destruktif. Hindari lebih dari satu tombol utama dalam satu konteks.
- Pertahankan `name`, `id`, `required`, nilai lama, CSRF, validasi, dan kontrak request yang sudah dipakai controller.

### Gunakan Komponen Bersama

- Pakai `.panel`, `.aksi`, token warna `--ta-*` / `--ina-*`, dark theme `html.gelap`, dan pola spacing yang sudah tersedia. Hindari warna literal jika token semantik tersedia.
- Semua select tunggal otomatis menjadi searchable. Untuk placeholder khusus, pakai `data-placeholder` atau helper `select_cari()`; jangan membuat dropdown pencarian kedua. Panel dropdown select-cari dirender sebagai lapisan `fixed` di `<body>` (portal, z-index 500) agar selalu melayang di atas panel lain dan tidak pernah menambah scroll atau terpotong wadah — jangan mengembalikannya ke posisi absolute di dalam wadah. **Pengecualian:** select "Per halaman" pada paginasi tabel adalah select biasa (bukan searchable) — nilainya hanya 10/25/50/100 dan harus bisa diganti secepat mungkin tanpa membuka panel pencarian; jangan mengubahnya menjadi searchable.
- Untuk tabel, gunakan `data-cari` dan/atau `data-paginasi` agar mendapat pencarian, pengurutan, paginasi, hitungan hasil, dan empty state bersama. Jangan membuat implementasi per halaman tanpa kebutuhan yang terbukti.
- **Tabel Standar v2** (mesin bersama `paginasi.js`): setiap tabel daftar mendapat pencarian langsung, paginasi, **pengurutan klik-header** (sadar angka format Indonesia, tanggal, teks; kolom berisi kontrol formulir tidak bisa diurutkan, atau tandai `data-tanpa-urut`), **empty state** seragam saat saringan kosong, baris judul grup (satu sel `colspan` penuh atau class `baris-grup`) mengikuti baris anaknya, baris total (class `total`/`baris-total`) selalu tampil dan tidak dihitung, baris rincian (class `baris-detail`) tersinkron dengan pemiliknya, "pilih semua" hanya mencentang baris yang terlihat, dan semua baris tercetak saat print. Struktur dinormalisasi menjadi `thead`/`tbody` dengan header sticky; status tabel (kata cari, urutan, ukuran halaman, halaman) tersimpan per sesi; tombol Ekspor CSV mengunduh baris yang sedang tersaring; header urut membawa `aria-sort` dan kotak cari berlabel aksesibel. Jangan menulis ulang perilaku ini per halaman.
- **Semua tabel daftar wajib berperilaku sama**: tabel yang menampilkan daftar data (master, register, riwayat, rekap, kebutuhan) memakai perilaku bersama di atas — daftar yang bisa panjang memakai `data-paginasi`, dan setiap tabel daftar memakai `data-cari` agar selalu bisa disaring. Pengecualian hanya untuk: tabel pada halaman cetak, tabel ringkasan/pernyataan singkat (sedikit baris hitungan, mis. verifikasi anggaran dan sisa dana), sub-tabel rincian di dalam baris tabel lain, tabel editor dokumen (isi dokumen yang sedang disusun), tabel formulir per kelompok (mis. penggabungan duplikat), dan tabel yang pencariannya sudah disediakan di tingkat halaman. Di luar pengecualian itu, tabel tanpa perilaku bersama adalah ketidakselarasan yang harus diperbaiki.
- Untuk hapus massal, gunakan `komponenBulk()` dan atribut konfirmasi bersama. Jangan membuat modal konfirmasi baru untuk pola yang sudah ditangani layout.
- Pakai `<details>/<summary>` native untuk panel padat atau bagian jarang dipakai. Ringkasan harus tetap informatif saat tertutup; kelompok besar boleh tertutup awal, tetapi bagian kecil dan alur utama tetap terlihat.
- Jika nilai select diubah lewat JavaScript, dispatch event `change` atau panggil `window.perbaruiSelectCari(select)` agar label dan validasi tetap sinkron.
- Konversi satuan bahan yang berbeda antara komponen AHSP dan master memakai aturan bawaan `faktorSatuan()`; faktor manual per pasangan (bahan, satuan komponen) disimpan di tabel `bahan_konversi` lewat panel Satuan Bahan Berbeda di modul Analisa dan selalu mengalahkan aturan bawaan. Jangan menambah aturan tebak-tebakan baru di `faktorSatuan()` untuk kasus yang bisa diatur pengguna lewat faktor manual.
- Menghapus bahan dari Rencana Belanja berarti menghapus lunak komponennya di Analisa (sumber kebutuhan) — jangan menghapus master bahan atau baris SPK dari aksi itu.
- Field berisi harga/rupiah wajib memakai atribut `data-uang` (mesin bersama `uang.js`): nilai tampil dengan pemisah ribuan Indonesia saat diketik dan saat halaman dimuat, dan otomatis dikembalikan ke angka polos sebelum formulir terkirim agar parsing server tidak berubah. Gunakan `type="text"` + `inputmode="numeric"` untuk field ini — jangan `type="number"` karena tidak bisa menampilkan pemisah ribuan. Field volume/kuantitas/desimal bukan field uang dan tidak memakai pola ini.

### Kepadatan Tabel dan Panel

- Prioritaskan informasi untuk pemindaian: header jelas, angka rata kanan, kolom aksi stabil, dan teks panjang dibatasi dengan wrapping atau ellipsis yang aman.
- Jangan menyembunyikan aksi penting di dalam panel lipat. Tampilkan jumlah baris/status pada summary bila tabel dikelompokkan.
- Tabel lebar boleh memiliki scroll horizontal pada pembungkusnya; jangan membuat seluruh halaman ikut melebar.
- Hindari panel bertumpuk. Gunakan panel hanya untuk kelompok konten yang memang perlu dibatasi, bukan setiap field atau baris.

### Aksesibilitas dan Responsif

- Setiap kontrol punya label yang terlihat atau nama aksesibel; ikon aksi punya `aria-label`/tooltip bila tidak ada teks.
- Pastikan alur keyboard: fokus terlihat, Tab berurutan, Escape menutup overlay yang sesuai, dan fokus kembali ke pemicu setelah dialog ditutup.
- Modal harus memakai semantik dialog, fokus terkelola, dan tidak mengirim form sebelum konfirmasi.
- Pertahankan target sentuh sekitar 44 px pada layar sempit untuk tombol, summary, dan kontrol utama.
- Uji lebar desktop dan ponsel. Pastikan teks tidak bertumpuk, tidak terpotong tanpa cara melihatnya, dan hanya komponen yang semestinya yang menggulir.
- Periksa tema terang dan gelap; state hover, fokus, disabled, error, kosong, dan loading harus tetap terbaca.

### CSS dan Perilaku

- Scope aturan CSS dengan kelas komponen/halaman. Jangan mengubah selector global jika perubahan hanya dibutuhkan oleh satu halaman.
- Reuse fungsi/helper yang sudah ada sebelum menambahkan abstraksi baru. Hindari menggeneralisasi aturan bisnis yang berbeda hanya karena markup-nya mirip.
- Jangan mengubah struktur data, nama field, route, atau perilaku submit sebagai bagian dari perapian visual kecuali memang diminta.
- Untuk aset CSS/JavaScript yang di-cache, ikuti pola cache-busting `filemtime` pada layout.

## Pengujian dan Penyelesaian

- Jalankan lint PHP untuk view/controller yang disentuh dan `node --check` untuk JavaScript yang disentuh.
- Uji perilaku utama di browser, bukan hanya keberhasilan render: buka/tutup, submit/validasi, pencarian/paginasi, dan state dinamis sesuai komponen.
- Periksa screenshot desktop dan ponsel untuk halaman yang layout-nya berubah. Uji perubahan CSS pada tema terang dan gelap bila selector terkait.
- Jalankan `composer test` untuk perubahan logika yang punya cakupan test, serta `git diff --check` sebelum selesai.
- Untuk UI, uji state normal, kosong, error, disabled, dan loading yang relevan; pastikan form tetap mengirim nilai yang sama dan validasi tetap berfungsi.
- Untuk migration atau query lintas sekolah, uji upgrade dan isolasi data pada database pengembangan yang sesuai.
- Sebutkan pemeriksaan yang benar-benar dijalankan dan hasilnya. Nyatakan jelas bila browser, test, atau database tidak tersedia.
- Jangan membuat commit, branch, push, atau stash kecuali diminta. Jangan menjalankan operasi Git destruktif; pertahankan staging dan perubahan lokal pengguna.
