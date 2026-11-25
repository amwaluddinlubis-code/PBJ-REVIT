# Panduan Penggunaan Aplikasi Analisa Perencana (PBJ-REVIT)

**Aplikasi pengelolaan pembangunan/revitalisasi sekolah berbasis dokumen perencana** — dari impor RAB perencana, perhitungan kebutuhan bahan & upah, perencanaan belanja, sampai LPJ.

Versi panduan: 24 November 2026 · Terakhir diverifikasi terhadap kode: 24 November 2026 (migrasi 000028) · Aplikasi: PBJ-REVIT (CodeIgniter 4) · Contoh data: SMP Negeri 3 Ranto Baek SATAP

> Catatan: tangkapan layar belum disertakan pada versi ini. Setiap bab menyebut menu, tombol, dan hasil yang harus terlihat agar tetap dapat diikuti.

---

## Daftar Isi

1. Pendahuluan — alur besar aplikasi
2. Memulai — masuk, tampilan, profil & sandi
2a. Konvensi Antarmuka (berlaku di semua modul)
3. Mengelola Sekolah & Pengaturan
4. Impor Dokumen Perencana
5. Dashboard & Panel Perlu Perhatian
6. Gedung & BOQ — kebutuhan bahan, kebutuhan upah, realisasi
7. Biaya Non-Gedung
8. Rencana Belanja & Membuat SPK
9. Pembayaran Upah
10. Master Bahan
11. SPK & Rincian — termasuk Belum Bernomor
12. Survei Harga & Negosiasi
13. BAP & BAST — dan mencetak dokumen
14. Invoice & Jurnal Pembayaran
15. Buku Material & Sisa Bahan
16. Laporan & Ekspor Excel
17. LPJ — sekolah dan per gedung
18. Manajemen Pengguna
19. Menghapus Data dengan Aman
20. Pemecahan Masalah (FAQ)
21. Lampiran — Ringkasan Hasil QA

---

## 1. Pendahuluan — alur besar aplikasi

Aplikasi ini membaca **dokumen RAB perencana** (workbook Excel dari konsultan perencana) dan menjadikannya dasar seluruh pengelolaan pembangunan:

```
Dokumen RAB perencana ──impor──► Analisa AHSP + Harsat (referensi bahan, upah, harga)
                                        │
                                        ▼
                              BOQ per gedung ──► kebutuhan bahan & upah terhitung
                                        │
                                        ▼
                  Rencana Belanja ──► SPK ──► BAP ──► BAST ──► Invoice ──► Jurnal (bayar)
                                        │
                                        ▼
                     Buku Material · Sisa Bahan · Laporan · LPJ per gedung & sekolah
```

Prinsip-prinsip penting:

- **Satu aplikasi, banyak sekolah.** Setiap sekolah datanya terpisah; pindah sekolah lewat pengalih di menu samping.
- **Gedung adalah modul mandiri.** Setiap gedung punya BOQ, kebutuhan bahan, kebutuhan upah, realisasi, dan LPJ sendiri; rekap gabungan ada di tingkat sekolah.
- **Referensi resmi berasal dari dokumen perencana.** Satuan dan harga bahan/upah diambil dari **sheet Analisa** (setiap komponen analisa mencantumkan satuan dan harga satuannya), dan harga satuan pekerjaan resmi diambil dari **sheet Harsat Pekerjaan**.
- **Harga satuan belanja boleh melebihi harga dokumen** (fluktuasi lapangan wajar; selisihnya ditampilkan), tetapi **total belanja keseluruhan tidak boleh melebihi pagu anggaran sekolah** — aplikasi menahan transaksi yang menembus pagu.
- **Rencana belanja adalah turunan kebutuhan**, bukan daftar bebas: paket belanja dibuat dari hitungan kebutuhan bahan/upah per pekerjaan, dan terhitung ulang bila volume pekerjaan berubah.
- **Alur SPK → SPJ bisa diselesaikan dari satu panel.** Sesudah barang diterima, panel **Tuntaskan SPK** di halaman rincian SPK membuat BAP, BAST, dan Invoice sekaligus dari satu tanggal terima, mencatat pembayarannya, dan mencetak keempat dokumen dalam satu berkas — lihat Bab 13.
- **Setiap peringatan membawa jalan keluarnya.** Bila suatu halaman menyatakan ada yang kurang — SPK belum punya rincian, bahan belum punya harga, komponen belum tertaut, invoice belum cocok — kalimatnya selalu disertai tautan langsung ke halaman tempat kekurangan itu dilengkapi, jadi tidak perlu bolak-balik mencari menu. Ceklis dokumen di Detail SPK juga menaut ke modul pemilik tiap dokumen (Survei, Negosiasi, Dokumen, Invoice, Jurnal).

## 2. Memulai — masuk, tampilan, profil & sandi

Buka alamat aplikasi (di komputer server, mis. `http://localhost:81/pbj-revit/`), lalu masuk dengan nama pengguna dan kata sandi yang diberikan admin. Akun bawaan awal adalah **admin / admin123**.

- **Wajib ganti sandi.** Akun bawaan dan akun yang baru dibuat admin memiliki status *harus ganti sandi*: sesudah masuk, aplikasi langsung mengarahkan ke halaman **Profil** dan menolak membuka menu lain sampai sandi diganti. Ini pengaman, bukan gangguan.
- **Akun nonaktif** tidak dapat masuk. Admin dapat menonaktifkan akun lewat **Pengaturan → Manajemen User**.
- Halaman masuk memakai **tampilan v2** (dua panel). Admin dapat memilih tampilan masuk lain lewat **Pengaturan → Identitas & Pengaturan → Tampilan halaman masuk**.

Sesudah masuk, tampilan utama terdiri dari:

- **Menu samping (kiri)** — berlatar gradasi hijau, berkelompok: **Perencanaan**, **Pembangunan Gedung**, **Belanja**, **Material**, **Keuangan**, **Laporan**, dan **Pengaturan**. Klik nama kelompok untuk membuka/menutup isinya; tombol di topbar menyembunyikan/menampilkan menu samping. Di HP, menu menjadi laci geser.
- **Blok logo statis** di puncak menu samping — memuat logo aplikasi, tulisan "Analisa Perencana", label **Sekolah aktif**, dan pemilih sekolah. Blok ini tidak ikut bergulir saat daftar menu panjang; yang bergulir hanya daftar menu di bawahnya. Saat ukuran huruf diubah (A−/A+), ukuran logo dan blok profil tetap.
- **Topbar** — pencarian bahan, cip nama sekolah aktif, tombol ukuran huruf (A− dan A+), tombol tema gelap/terang, dan menu profil.
- **Notifikasi toast** — setiap operasi simpan/ubah/hapus memunculkan pemberitahuan "Berhasil/Gagal" di kanan atas yang hilang sendiri dalam ±5 detik.

Menu yang terlihat mengikuti **peran** Anda dan pengaturan **Akses Menu** (Bab 18). Bila sebuah menu yang disebut panduan ini tidak tampak, kemungkinan peran Anda tidak diberi akses ke menu tersebut.

### Profil & ganti sandi

Klik avatar/nama Anda di topbar → **Profil**: ubah nama tampilan, dan ganti kata sandi (isi sandi lama + sandi baru). Tombol **Keluar** juga ada di menu yang sama. Setelah sandi berhasil diganti, status *harus ganti sandi* hilang dan seluruh menu terbuka kembali.

## 2a. Konvensi Antarmuka (berlaku di semua modul)

Agar panduan ini tidak mengulang hal yang sama di setiap bab, berikut konvensi yang berlaku di seluruh aplikasi:

- **Tanggal tampil dd/mm/yyyy.** Semua tanggal yang tampil (tabel, laporan, cetakan) memakai format hari/bulan/tahun. Penyimpanan tetap yyyy-mm-dd. Input tanggal adalah kotak teks bertanggal (kelas `isian-tanggal`) yang otomatis dikonversi saat formulir dikirim — jangan mengetik format ISO manual.
- **Isian uang berformat ribuan.** Field harga/rupiah memakai atribut `data-uang`: nilai tampil dengan pemisah ribuan Indonesia saat diketik dan saat halaman dimuat, dan otomatis dikembalikan ke angka polos sebelum formulir terkirim. Gunakan `type="text"` + `inputmode="numeric"` (bukan `type="number"`).
- **Select pencarian.** Semua select tunggal otomatis menjadi searchable. Untuk placeholder khusus, pakai `data-placeholder` atau helper `select_cari()`. Panel dropdown dirender sebagai lapisan tetap di `<body>` (portal) agar tidak terpotong. **Pengecualian:** select "Per halaman" pada paginasi tabel adalah select biasa (nilai 10/25/50/100) agar cepat diganti tanpa membuka panel pencarian.
- **Tabel Standar v2.** Setiap tabel daftar mendapat: pencarian langsung, paginasi, pengurutan klik-header (sadar angka format Indonesia, tanggal, teks; kolom berisi kontrol formulir tidak bisa diurutkan atau ditandai `data-tanpa-urut`), empty state seragam saat saringan kosong, baris judul grup mengikuti baris anaknya, baris total selalu tampil dan tidak dihitung, baris rincian tersinkron dengan pemiliknya, "pilih semua" hanya mencentang baris yang terlihat, dan semua baris tercetak saat print. Status tabel (kata cari, urutan, ukuran halaman, halaman) tersimpan per sesi; tombol Ekspor CSV mengunduh baris yang sedang tersaring; header urut membawa `aria-sort`; kotak cari berlabel aksesibel.
- **Tabel lebar** boleh memiliki scroll horizontal pada pembungkusnya; jangan membuat seluruh halaman ikut melebar. Header tabel menempel (sticky) bila tabel cukup tinggi.
- **Modal konfirmasi.** Setiap tombol Hapus membuka modal konfirmasi gaya TailAdmin; data tidak terhapus sebelum menekan **Ya, Hapus**. Esc atau klik di luar menutup.
- **Tema terang/gelap** (tombol bulan di topbar) dan **ukuran huruf** (A−/A+) diingat oleh peramban. Saat zoom in/out, ukuran logo dan blok profil di sidebar tetap (zoom balik).
- **Tautan lintas modul.** Setiap peringatan/keadaan kosong menyertakan tautan langsung ke halaman tempat kekurangan itu dilengkapi (mis. bahan tanpa harga → Master Bahan, SPK tanpa rincian → Detail SPK, invoice tak cocok → Register Invoice).

## 3. Mengelola Sekolah & Pengaturan

### Menambah & memilih sekolah

Menu **Pengaturan → Kelola Sekolah**: daftar semua sekolah, tombol tambah sekolah baru (form singkat: nama sekolah), pindah sekolah aktif, dan hapus sekolah yang masih kosong. Sekolah yang baru dibuat selanjutnya dilengkapi identitasnya di halaman Pengaturan.

### Pengaturan sekolah aktif

Menu **Pengaturan → Identitas & Pengaturan** berisi lima kelompok untuk sekolah yang sedang aktif:

1. **Identitas Sekolah** — nama, NPSN, alamat, desa/kecamatan/kabupaten/provinsi, tahun anggaran, paket pekerjaan.
2. **Pagu Anggaran** — total anggaran (pagu) sekolah; ditampilkan juga belanja terbayar dan sisa pagu. Pagu ini yang menjadi kunci kontrol total belanja. Angka boleh diketik berformat ribuan (mis. `2.540.292.000`).
3. **Kop Surat** — baris kop pemerintah dan dinas untuk dokumen cetak.
4. **Panitia & Penandatangan** — kepala sekolah, ketua, sekretaris, pelaksana, dan nama-nama yang menandatangani dokumen.
5. **Pengaturan Lanjutan** (terlipat) — pemeriksa, toko/penyedia bawaan, tanggal survei.

Di halaman yang sama ada **Master Penyedia**: tambah, **Ubah**, dan hapus toko/rekanan yang dipakai pada SPK dan invoice.

## 4. Impor Dokumen Perencana

Menu **Perencanaan → Impor Dokumen Perencana**. Ada dua format yang dikenali otomatis: **workbook RAB perencana** (format konsultan) dan **template manual** (format aplikasi sendiri). Impor hanya dapat dijalankan oleh peran **operator** dan **admin**.

### Tahap peninjauan & pemetaan sheet

Sesudah berkas dipilih (cara apa pun), impor **tidak langsung menulis ke tabel**. Anda dibawa ke halaman **Tinjau & Petakan Impor** yang memuat seluruh sheet berkas beserta jenis terdeteksinya, jumlah baris, dan total nilainya. Pada tahap ini Anda dapat:

1. **Melewati sheet** yang tidak akan diimpor (pilihan *Lewati*) — misalnya sheet BOQ gedung yang tidak termasuk paket yang didanai, atau sheet duplikat.
2. **Memetakan tujuan tiap sheet**: *Gedung* (menjadi BOQ sebuah gedung), *Biaya Non-Gedung* (menjadi baris biaya non-gedung), atau *Data Referensi* (daftar harga, analisa, harsat, rekap pagu, data sekolah — khusus sheet referensi).
3. **Mengubah nama gedung** tujuan sebelum impor.

Sheet yang tidak ingin diimpor juga bisa dibuang dari daftar dengan tombol **Hapus** pada barisnya — baris terhapus dianggap **Dilewati** (data lama yang sudah ada tidak berubah), berpindah ke bagian bawah daftar supaya daftar aktif tetap ringkas, dan masih bisa dikembalikan dengan tombol **Kembalikan** sebelum impor dijalankan (tujuan impornya pulih seperti semula). Sesudah Anda menekan **Impor Sekarang**, muncul **dialog Konfirmasi Impor** yang merangkum persis apa yang akan diimpor menurut pemetaan Anda saat itu: daftar sheet yang akan diimpor per tujuan (Gedung, Biaya Non-Gedung, Data Referensi) beserta jumlah baris dan nilainya, serta total nilai keseluruhan — sheet yang dilewati hanya disebut jumlahnya, tidak dirinci. Periksa ringkasan itu, lalu tekan **Ya, Impor Sekarang** untuk menulis data — atau **Batal** untuk kembali memeriksa. Tombol **Batal — Jangan Impor** membatalkan tahap tanpa mengubah data apa pun. Saran bawaan aplikasi mengikuti deteksi otomatis (sheet BOQ → Gedung, *Prcn dan pgws*, *ADMINISTRASI* & *Perabot* → Biaya Non-Gedung, sheet kerja/gambar → Lewati), jadi untuk berkas yang rapi Anda cukup memeriksa sekilas lalu mengimpor.

### Cara mengimpor workbook perencana

1. **Pilih sekolah tujuan** pada formulir (setiap cara impor punya pemilih sekolahnya sendiri; sekolah aktif otomatis berpindah ke tujuan).
2. Pilih salah satu dari tiga cara:
   - **Cara 1 — Dari Folder (disarankan):** salin berkas `.xlsx` ke folder `writable/impor` di folder aplikasi, muat ulang halaman, pilih berkasnya, klik impor. Cara ini **tanpa batas ukuran berkas**.
   - **Cara 2 — Impor Ulang:** mengimpor ulang berkas terakhir yang pernah diunggah (tersimpan di server).
   - **Cara 3 — Unggah:** pilih berkas dari komputer. Bila gagal karena ukuran (berkas perencana umumnya 8–17 MB), pakai Cara 1.
3. Tunggu sampai selesai (workbook besar perlu beberapa detik sampai ±1 menit; jangan tutup halaman).

Sesudah impor berhasil, pesan sukses langsung memuat **verifikasi anggaran**: total BOQ seluruh gedung aktif dibandingkan dengan pagu anggaran sekolah. Perbandingan yang sama selalu tampil di panel **Verifikasi Anggaran** di halaman Impor dan di baris bawah panel Pembangunan Gedung pada Dashboard — total BOQ adalah penjumlahan biaya (volume × harga satuan) semua pekerjaan dari gedung yang aktif, ditambah **biaya non-gedung** (Bab 7), pagu diambil dari isian di Pengaturan, dan selisihnya diberi status *Sesuai / Di bawah pagu / Melebihi pagu*. Bila total BOQ melebihi pagu, periksa kembali gedung mana saja yang memang termasuk paket yang didanai, atau sesuaikan pagu di Pengaturan bila ada perubahan anggaran resmi.

Yang dibaca dari workbook: daftar analisa AHSP beserta komponennya (bahan, upah, alat — lengkap dengan satuan dan harga per komponen), harga satuan pekerjaan dari sheet Harsat, BOQ tiap gedung, daftar harga, dan total bantuan dari Rekap RAB. **Mengimpor ulang berkas yang sama aman** — gedung dan analisa diperbarui, bukan digandakan.

Sesudah impor, periksa panel **Riwayat & Verifikasi** di halaman yang sama: jumlah gedung, analisa, komponen, dan **komponen belum tertaut**. Dengan cara baca saat ini komponen seharusnya tertaut semua (0 belum tertaut). Bila masih ada yang belum tertaut, panel di halaman impor menyediakan saran padanan bahan — tautkan satu per satu atau gunakan **tautkan massal** untuk saran yang kemiripannya tinggi.

### Template manual (jalur pasti untuk RAB format apa pun)

Bila workbook perencana tidak terbaca rapi (perencana lain, PDF, atau hitungan manual):

1. Klik **Unduh Template Kosong** — berkas Excel berisi 6 sheet: Petunjuk, Sekolah, Harga, Analisa, Harsat, BOQ, lengkap dengan baris CONTOH (baris contoh diawali kata "CONTOH" dan dilewati saat impor).
2. Isi kelima sheet data:
   - **Sekolah** — identitas dan total anggaran (format kunci–nilai).
   - **Harga** — daftar bahan & upah: kode, jenis (Bahan/Upah), nama, satuan, harga.
   - **Analisa** — satu baris per komponen: kode analisa, uraian pekerjaan, satuan pekerjaan, jenis komponen, nama komponen, koefisien, satuan komponen, dan **Harga Komponen** (harga per satuan komponen, mis. Semen Rp 1.860 per **kg**).
   - **Harsat** — harga satuan pekerjaan resmi per kode analisa.
   - **BOQ** — satu baris per pekerjaan per gedung: gedung, bab, uraian, kode analisa, volume, satuan, dan kolom Harga Satuan (opsional).
3. Impor berkasnya lewat salah satu dari tiga cara di atas — aplikasi mengenali format template secara otomatis.

**Urutan acuan harga pekerjaan** saat impor template: (1) kolom Harga Satuan di BOQ bila diisi; (2) sheet Harsat; (3) hitungan dari komponen Analisa.

Sebaliknya, data sekolah aktif dapat **diekspor menjadi template terisi** (tombol **Ekspor Template Terisi**) untuk diperiksa/dikoreksi di Excel lalu diimpor kembali.

### Pembersihan Hasil Impor

Menu **Perencanaan → Pembersihan Hasil Impor** merapikan sisa-sisa impor workbook perencana yang tidak terbaca sempurna: halaman ini menampilkan temuan (mis. analisa tanpa komponen, komponen yang belum tertaut ke master bahan/upah, atau baris ganda), dan Anda dapat membersihkannya dari sana sesudah meninjau daftarnya. Jalankan halaman ini bila sesudah impor ada angka kebutuhan yang terasa janggal, sebelum mulai menyusun rencana belanja.

### Menu Analisa Pekerjaan

Menu **Perencanaan → Analisa Pekerjaan** adalah jantung hitungan aplikasi: daftar analisa (AHSP) sekolah beserta harga satuannya. Buka satu analisa untuk melihat dan mengurus **komponennya** — bahan, upah, dan peralatan beserta koefisien dan tarifnya:

- Komponen dapat **ditambah, diubah, dan dihapus**; harga satuan analisa terhitung ulang dari komponennya. Saat menambah komponen jenis Bahan, memilih bahan dari master mengisi otomatis nama komponen, satuan, dan tarifnya (tarif = harga acuan bahan: negosiasi, lalu penawaran, lalu harga RAB) — ketiganya tetap bisa diubah sebelum disimpan. Editor komponen berjalan di **modal live** — ubah koefisien atau tarif, nilai koefisien × tarif terhitung langsung saat mengetik, dan penyimpanan dilakukan lewat AJAX tanpa memuat ulang halaman (tabel & ringkasan disegarkan otomatis).
- Komponen yang belum tertaut ke master bahan/upah dikumpulkan di panel **Komponen Belum Tertaut** — tautkan dari sana agar kebutuhan bahan/upah terbaca di BOQ dan Rencana Belanja.
- Untuk bahan yang satuan komponennya berbeda dari satuan beli master (mis. semen kg → zak), isi **konversi satuan manual** pada komponennya; konversi manual ini mengalahkan konversi bawaan aplikasi di semua hitungan.
- Bila analisa berpadanan dengan **Acuan AHSP sekolah ini**, kolom **Koef. Acuan** dan **Nilai Acuan** tampil di samping setiap komponen, dan koefisien acuan ditampilkan sebagai petunjuk di modal Ubah. Acuan hanya pembanding — perbaikan koefisien tetap dilakukan di sini; tidak ada tombol yang menyalin koefisien acuan otomatis.
- Panel **Dipakai di Pekerjaan** di bawah analisa menampilkan daftar pekerjaan + gedung yang memakai analisa ini (lewat relasi id, cadangan kode), masing-masing bertautan ke baris pekerjaan itu di BOQ gedungnya (halaman digulir, baris disorot, rinciannya terbuka).
- Pencarian di halaman Analisa juga menemukan analisa dari **nama bahan/komponen** di baliknya (indeks cari per baris), tanpa mencemari pengurutan kolom dan ekspor CSV.

### Memeriksa Analisa — Acuan AHSP Nasional & Validasi Harga Satuan

Dua menu pemeriksaan membantu memastikan analisa dan harga pekerjaan sekolah masuk akal:

- **Acuan AHSP** (menu **Perencanaan → Acuan AHSP**): membandingkan **koefisien** analisa sekolah (dari dokumen perencana) terhadap set acuan **milik sekolah itu sendiri**, yang disalin dari acuan nasional **SE Dirjen Bina Konstruksi 68/SE/Dk/2024** (dasar Permen PUPR 8/2023). Setiap sekolah punya salinannya sendiri — set satu sekolah tidak mengubah sekolah lain. Pencocokan lewat kode analisa; harga acuan dihitung ulang memakai tarif komponen sekolah sendiri, sehingga yang dibandingkan murni koefisiennya. Sekolah baru otomatis menerima salinan acuan nasional saat dibuat; sekolah yang setnya masih kosong menampilkannya sebagai keadaan kosong dengan tombol **Siapkan Acuan dari Nasional**. Dari folder aplikasi, acuan juga bisa dimuat per sasaran: `php spark impor:acuan` (templat nasional), `php spark impor:acuan <id-sekolah>`, atau `php spark impor:acuan semua`. Halaman ini **hanya pembanding** — perbaikan koefisien tetap dilakukan di **Analisa Pekerjaan**; tidak ada tombol yang menyalin koefisien acuan secara otomatis.
- **Validasi Harga Satuan** (menu **Perencanaan → Validasi Harga Satuan**): membandingkan harga satuan pekerjaan untuk **kode analisa yang sama** — antar pekerjaan itu sendiri dan terhadap harga hasil kalkulasi komponen analisanya. Bila ada pekerjaan yang harganya menyimpang dari saudara sekodenya, halaman ini menandainya dan menyediakan tombol untuk **menyamakan** harga dalam satu kode (satu pekerjaan, atau semua sekaligus). Harga berjalan yang berasal dari BA Revisi yang sudah disahkan **tidak disentuh** oleh penyamaan ini. Tindakan menyamakan harga hanya untuk peran **operator** dan **admin**.

## 5. Dashboard & Panel Perlu Perhatian

Dashboard merangkum sekolah aktif: **Total RAB**, **Total SPK Terdaftar**, **Realisasi Dibayar** (bersih sesudah PPh 22), dan **Sisa Anggaran**.

Panel **Pembangunan Gedung — Dari Dokumen Perencana** membandingkan, per gedung aktif:
- **Nilai BOQ Asli** = angka BOQ dokumen perencana yang tersimpan pada gedung itu (terisi saat impor workbook). Nilai ini **beku**: tidak berubah oleh edit volume/harga pekerjaan — betulkan hanya lewat isian *Nilai BOQ dokumen* pada panel Ubah Gedung bila angka dokumennya memang direvisi,
- **Nilai Analisa** = nilai AHSP berjalan (total verifikasi anggaran),
- **Perubahan** = selisihnya bertanda +/− dan persennya terhadap BOQ asli, dengan baris total di kaki tabel.

Perbandingan inilah yang menunjukkan dampak perbaikan harga/tarif analisa terhadap nilai gedung. Panel **Ringkasan per SPK** menampilkan nomor, tanggal, penyedia, paket, jumlah bahan, nilai, dan status (belum/tuntaskan) per SPK.

Panel kuning **Perlu perhatian** menandai hal yang harus diperiksa, masing-masing dengan tautan langsung ke halaman pemilik datanya:
- kemajuan fisik ≥ 50% tetapi **dana tahap 2 (30%) belum diterima** (ajukan pencairan di Penerimaan Dana),
- **baris rincian SPK yang belum bernomor SPK** (pasangkan nomornya),
- **invoice yang nilainya tidak sama** dengan total SPK-nya (periksa di Register Invoice),
- **sisa bahan negatif** (pemakaian melebihi RAB),
- **pola pemecahan pembelian** (pembelian besar yang dicicil lewat SPK kecil berturut-turut),
- **ALERT MASTER**: bahan/jenis tenaga sama dengan satuan atau harga berbeda,
- **bahan tanpa harga acuan** (terhitung Rp 0 di kebutuhan & rencana belanja),
- **jenis upah tanpa tarif** (terhitung Rp 0 di kebutuhan upah).

Panel **Biaya Non-Gedung** berdiri sendiri di bawah panel gedung: merinci kelompok, uraian, dan jumlah seluruh biaya non-gedung sekolah aktif beserta totalnya (tidak digabung ke total panel gedung).

Di bawah panel utama tampil juga **verifikasi anggaran**: total BOQ gedung aktif + biaya non-gedung dibandingkan dengan pagu anggaran, beserta selisih dan status (sesuai/melebihi).

## 6. Gedung & BOQ — kebutuhan bahan, kebutuhan upah, realisasi

Menu **Pembangunan Gedung → Kelola Gedung** (atau klik nama gedung di menu samping). Setiap gedung dapat ditambah, diubah namanya, dan dihapus (menghapus gedung ikut menandai BOQ dan realisasinya).

Halaman detail gedung mempunyai empat tab:

### Tab BOQ Pekerjaan

Daftar pekerjaan gedung itu: bab/kelompok, uraian, kode analisa, volume, satuan, harga satuan, dan jumlah. Volume dapat **diubah langsung** di barisnya (kebutuhan bahan/upah dan nilai gedung terhitung ulang). Pekerjaan baru dapat ditambah; pekerjaan dapat dihapus lewat tombol hapus (dengan modal konfirmasi).

Hal yang perlu diketahui:
- **Subtotal per bab** tampil pada baris judul bab (kolom Jumlah Berjalan = jumlah seluruh Jumlah Berjalan pekerjaan dalam bab itu), dihitung atas semua baris bab tersebut (bukan hanya yang tampil di halaman paginasi aktif).
- **Lipat bab**: tombol panah di awal baris judul bab melipat/membuka seluruh baris pekerjaan bab itu — subtotal bab tetap terlihat saat terlipat, jadi daftar panjang bisa diringkas per bab. Keadaan lipatan diingat per gedung di peramban yang sama. Mengarahkan kursor ke nama pekerjaan mengubah warna hurufnya menjadi kuning (tanpa latar biru).
- **Kode analisa** pada baris pekerjaan adalah tautan ke halaman analisa itu; nama pekerjaan yang punya analisa dapat diklik untuk membuka rincian bahan & upah di tempat.
- **Dasar nilai pekerjaan** adalah hasil AHSP: harga berjalan = harga revisi yang disahkan (bila ada) > jumlah koefisien × tarif komponen analisa terkini (tarif upah memakai yang terbesar antara tarif komponen dan Master Upah) > harga satuan dokumen BOQ (hanya untuk pekerjaan tanpa analisa/komponen). Total BOQ dashboard, verifikasi anggaran, nilai realisasi, Kurva S, dan RAB Gabungan LPJ semuanya memakai dasar ini.
- Nama pekerjaan yang punya analisa dapat diklik untuk membuka rincian bahan & upahnya di tempat.

### Tab Kebutuhan Bahan

Hitungan kebutuhan bahan gedung ini: untuk setiap bahan — satuan **komponen** dari analisa (mis. kg), jumlah kebutuhan, satuan **beli** (mis. zak), jumlah beli, dan nilai rupiahnya. Konversi yang dipakai: semen kg → zak (÷50), pasir kg → m³ (÷1.400), kerikil/batu kg → m³ (÷1.600), keramik bh → ktk (sesuai ukuran). Bila ada komponen yang belum tertaut ke master bahan, formulir penautan tersedia di tab ini.

### Tab Kebutuhan Upah

Hitungan kebutuhan tenaga per jenis: **Pekerja** dan **Tukang** — dua jenis kanonik master upah. Orang-hari (OH) dihasilkan dari perkalian volume × koefisien analisa, tarif per OH dari master upah, dan total upahnya dijumlahkan per jenis.

### Tab Realisasi

Catatan realisasi fisik per tanggal: persen atau volume terealisasi dan keterangan. Persen fisik gedung dihitung dari catatan ini dan tampil di daftar gedung, dashboard, dan LPJ.

### BA Revisi Pekerjaan & RAB Berjalan

Di lapangan, pekerjaan dapat berubah: volume bertambah/berkurang, pekerjaan baru muncul, atau pekerjaan diganti/dihapus. Menu **Pembangunan Gedung → BA Revisi Pekerjaan** mencatat perubahan itu secara resmi:

- Setiap revisi dicatat dengan jenisnya (**volume, baru, ganti, hapus**), uraian perubahan, dan nomor berita acaranya. Revisi yang dicatat berstatus **draft** dan **belum mengubah angka apa pun**.
- **Pengesahan revisi hanya dapat dilakukan peran ketua, kepsek, atau admin** — bukan pencatat revisi biasa. Gunakan tombol **Sahkan** pada baris revisi; tindakan ini tercatat di Jejak Audit.
- Sesudah berita acara ditandatangani dan revisi **disahkan**, barulah **RAB berjalan** pekerjaan itu bergerak (volume/harga revisi). **RAB awal tidak pernah berubah** — yang tampil di BOQ dan hitungan kebutuhan adalah nilai berjalan, dan riwayat perubahannya tersimpan di halaman ini.
- Berita acara revisi dapat **dicetak** untuk ditandatangani Kepala Sekolah, Ketua P2SP, dan Tim Teknis.

Prinsip yang sama berlaku untuk harga: nilai BOQ di Dashboard adalah gabungan nilai asli dokumen dan nilai hasil hitungan analisa berjalan; selisih keduanya ditampilkan berdampingan di panel Dashboard agar keputusan revisi anggaran (BA Revisi ke pemberi bantuan) punya dasar angka.

### Kurva S

Menu **Pembangunan Gedung → Kurva S** menggambar garis rencana kumulatif melawan realisasi kumulatif mingguan (dari catatan realisasi BOQ), dalam format dokumen jadwal Juknis:

- Bila **rencana resmi Time Schedule perencana** sudah diimpor (berkas JSON kurva, tombol impor di halaman ini), garis rencana memakai bobot resmi per minggu kalender proyek itu.
- Bila belum diimpor, garis rencana digambar sebagai **kurva S perkiraan** mengikuti tanggal SPK gedung — cukup untuk memantau keterlambatan secara kasar, tetapi imporlah rencana resmi agar perbandingannya sahih.
- **Tombol Buat Rencana Belanja di setiap baris minggu** (hanya bila rencana resmi sudah diimpor): membuat Rencana Mingguan untuk minggu itu dengan target pekerjaan terisi otomatis dari kurva — angka rencana kerja dan persen tidak diketik ulang, dan rencana belanjanya langsung tercatat kebutuhan pekerjaannya. Pada tampilan Total Sekolah, tombol membuat rencana untuk semua gedung sekaligus; pada tampilan per gedung, untuk gedung itu. Minggu yang rencananya sudah ada menampilkan tombol **Lihat Rencana** — rencana yang sudah bertarget tidak pernah ditimpa.
- **Kolom Realisasi Simulasi** (tampilan per gedung): isi persen realisasi minggu itu untuk mesin Simulasi Mingguan langsung dari tabel ini. Nilainya sama dengan formulir masukan di halaman Simulasi Mingguan — cukup isi di salah satu tempat, keduanya membaca dan menulis data yang sama.

### Laporan Kemajuan

Menu **Pembangunan Gedung → Laporan Kemajuan** menyusun laporan kemajuan pekerjaan per gedung untuk dicetak: ringkasan kemajuan fisik terhadap rencana pada periode laporan, dan **cetak akhir** proyek. Gunakan bersama Kurva S: kurva untuk membaca deviasi, laporan kemajuan untuk dokumen resminya.

### Foto Progres

Menu **Pembangunan Gedung → Foto Progres** menyimpan dokumentasi foto per gedung dalam tiga kategori: kondisi **sebelum**, **selama (proses)**, dan **sesudah** pekerjaan. Unggah foto dari halaman ini (tersimpan di folder aplikasi dan tampil sebagai kartu foto yang dapat dicentang untuk dihapus banyak sekaligus); kumpulan foto ini menjadi lampiran laporan kemajuan dan laporan akhir sesuai Juknis.

## 7. Biaya Non-Gedung

Menu **Perencanaan → Biaya Non-Gedung** menampung biaya tingkat sekolah yang berada di luar BOQ gedung, sebagaimana lazimnya dokumen RAB revitalisasi:

- **Perencanaan & Pengawasan** — terbaca otomatis dari sheet *Prcn dan pgws* dokumen perencana. Pada contoh SMPN 3 Ranto Baek SATAP: Perencana Rp 21.017.634 (1,65% dari nilai konstruksi) dan Pengawas Rp 23.565.226 (1,85%), total Rp 44.582.860.
- **Biaya Pengelolaan** — terbaca otomatis dari sheet *ADMINISTRASI*: honor P2SP, ATK, komunikasi, konsumsi rapat, transportasi, dan pelaporan. Pada contoh yang sama totalnya Rp 20.435.000.
- **Perabot** — terbaca otomatis dari sheet *Perabot*: daftar perabot per ruangan (meja, kursi, lemari, papan, dst.). Hanya baris yang **terisi jumlah harga** yang diimpor, dikelompokkan per ruangan — sebagian besar sheet adalah daftar kebutuhan yang volumenya belum diisi. Pada contoh yang sama: 24 baris terisi senilai Rp 152.413.610 (Ruang Kelas Unit 1 Rp 94.710.000 dan blok Administrasi Rp 57.703.610).

Hal yang perlu diketahui:

1. Baris bersumber **Impor** diganti segar setiap impor ulang (workbook maupun template); baris **Manual** yang Anda tambahkan sendiri tidak ikut terhapus.
2. Menambah baris manual: isi formulir — jumlah dihitung otomatis sebagai **volume × periode (bila ada) × harga satuan**. Tombol **Ubah** memuat baris ke formulir yang sama; **Hapus** memakai modal konfirmasi dan bersifat hapus aman (Bab 19).
3. Total biaya non-gedung ikut dihitung dalam **verifikasi anggaran**: total keseluruhan = total BOQ gedung aktif + biaya non-gedung, dibandingkan dengan pagu.
4. Pada template manual, biaya ini berada di sheet **BiayaLain** dan ikut diekspor/terimpor kembali.

## 8. Rencana Belanja & Membuat SPK

Menu **Perencanaan → Rencana Belanja** menyusun paket belanja dari pekerjaan yang dipilih, dalam empat langkah pada satu halaman:

1. **Langkah 1 — Pilih Pekerjaan:** centang pekerjaan (dari gedung mana pun), sesuaikan volume rencana per pekerjaan, dan isi **target hari kerja** (dipakai untuk hitungan jumlah pekerja di Langkah 4), lalu klik **Hitung Kebutuhan**.
2. **Langkah 2 — Analisa yang Dipakai:** daftar analisa di balik pekerjaan terpilih beserta nilai pekerjaannya.
3. **Langkah 3 — Kebutuhan Bahan:** kebutuhan bahan hasil hitungan (volume × koefisien analisa), dikurangi yang sudah dipesan pada SPK lain, sehingga terlihat **sisa kebutuhan**. Centang bahan yang akan dibeli, sesuaikan qty rencana (boleh dibeli bertahap), pilih gedung tujuan, lalu klik **Buat SPK dari Pilihan**.
4. **Langkah 4 — Kebutuhan Pekerja:** kebutuhan tenaga dari pekerjaan terpilih — OH (orang-hari) per jenis tenaga dan perkiraan jumlah orang menurut target hari kerja.

SPK yang dibuat dari rencana bernomor urut berikutnya, bertanggal hari ini, dan berisi baris bahan yang dicentang. **Catatan kebutuhan tenaga kerjanya ikut terisi otomatis** dari hitungan Langkah 4 (lihat Bab 11). Lengkapi penyedia dan tanggalnya di Register SPK atau halaman rincian SPK.

Harga satuan pada rencana boleh diisi melebihi harga dokumen — yang dikunci hanya **total keseluruhan terhadap pagu**.

### Rencana Mingguan

Menu **Perencanaan → Rencana Mingguan** memecah pekerjaan menjadi target per minggu untuk satu gedung:

1. Buat rencana mingguan: pilih gedung dan tanggal mulai minggu.
2. Isi **target volume** tiap pekerjaan untuk minggu itu, atau gunakan tombol **Ambil Target dari Kurva S** bila rencana Kurva S resmi sudah diimpor — target per bab dihitung dari rencana kumulatif dokumen s.d. minggu itu dikurangi realisasi tercatat bab itu (pola kejar ketertinggalan), dibagi ke pekerjaan menurut porsi sisa nilainya.
3. Aplikasi menghitung **daftar belanja bersih minggu itu**: kebutuhan kotor (target × koefisien analisa) dikurangi **stok tersedia** (barang sudah diterima/BAST dan saldo Buku Material) dan **pesanan berjalan** (SPK aktif yang belum di-BAST) — jadi yang dibeli hanya kekurangannya. Kebutuhan upah minggu itu tampil dalam OH.
4. Dari daftar belanja bersih itu, SPK mingguannya dapat dibuat langsung dari halaman ini.

**SPK gabungan** (tombol **Gabungkan Beberapa Rencana Jadi 1 SPK** di halaman ini): beberapa rencana mingguan beda gedung disatukan jadi satu SPK. Kebutuhan kotor per bahan per gedung dijumlahkan, stok tersedia & pesanan berjalan (sifatnya se-sekolah) dikurangkan **satu kali** dari gabungan lalu dialokasikan ke gedung menurut porsi kebutuhannya (sisa pembulatan ke gedung berporsi terbesar). SPK gabungan ber-gedung NULL; rinciannya terpisah per (bahan, gedung) dengan keterangan "Untuk <gedung>", Detail SPK menampilkan kolom Gedung Tujuan, dan cetakan SPK mengelompokkan rincian per gedung hanya bila multi-gedung.

**Buku Material per gedung**: keluar-masuk material dicatat per gedung (kolom Gedung Tujuan pada formulir, boleh kosong untuk gudang bersama) dan halaman Buku Material menampilkan Rekap per Gedung; saldo stok utama tetap gabungan se-sekolah.

### Simulasi Mingguan

Menu **Perencanaan → Simulasi Mingguan** adalah mesin simulasi satu proyek penuh mengikuti Kurva S resmi (syarat: rencana Kurva S sudah diimpor di halaman Kurva S):

1. **Isi realisasi mingguan**: pada tabel masukan, isi bobot realisasi tiap minggu per gedung — persen terhadap total proyek, satuan yang sama dengan bobot Kurva S (lihat Laporan Kemajuan). Minggu terakhir yang terisi dianggap minggu berjalan. Nilai yang sama bisa diisi per baris dari halaman Kurva S (tampilan per gedung, kolom Realisasi Simulasi) — kedua tempat menulis data yang sama.
2. **Simpan & Generate Ulang**: realisasi disebar ke bab mengikuti porsi bobot rencana minggu itu; sisa volume tiap bab dijadwalkan ulang ke minggu-minggu berikutnya mengikuti pola Kurva S bab itu (bab yang tertinggal dikejar pada minggu berikutnya).
3. **Baca hasilnya**: panel kedudukan per gedung (rencana kumulatif vs realisasi kumulatif), ringkasan mingguan, dan rincian per minggu — jenis pekerjaan yang berjalan beserta volume targetnya, rencana belanja bahan (kebutuhan kotor pada harga acuan), dan kebutuhan pekerja (OH + perkiraan orang pada 6 hari kerja). Baris Perabot/Persiapan/Biaya Manajemen tampil sebagai biaya non-BOQ terjadwal.

Simulasi ini memakai realisasi mingguan yang diisi di halaman ini — terpisah dari catatan Realisasi per pekerjaan. Ubah masukannya kapan pun, simpan, dan seluruh minggu terhitung ulang. Untuk daftar belanja bersih (sesudah stok & SPK berjalan) tetap gunakan Rencana Mingguan.

### SPK Gabungan Beberapa Gedung

Bila rencana mingguan beberapa gedung sudah dibuat dan belanjanya mau disatukan (satu toko, satu pengiriman), gunakan tombol **Gabungkan Beberapa Rencana Jadi 1 SPK** di halaman Rencana Mingguan:

1. Centang dua rencana atau lebih (boleh beda gedung), klik **Lihat Pratinjau Gabungan**.
2. Di pratinjau, kebutuhan bahan semua rencana dijumlahkan; **stok tersedia & pesanan berjalan dikurangkan satu kali** dari gabungan (bukan sekali per gedung), lalu sisanya dibagi ke tiap gedung menurut porsi kebutuhannya. Jumlah beli masih bisa disesuaikan per baris.
3. Klik **Buat 1 SPK dari Gabungan Ini**. SPK terbentuk sebagai SPK gabungan: rincian di dalamnya **terpisah per gedung tujuan** — Detail SPK menampilkan kolom Gedung Tujuan dan cetakan SPK mengelompokkan rincian menurut gedungnya.

Keluar–masuk material per gedung dicatat di **Buku Material**: kolom **Gedung Tujuan** pada formulir catatan (boleh dikosongkan untuk gudang bersama). Halaman Buku Material menampilkan **Rekap Keluar–Masuk per Gedung** — dari situlah terlihat berapa bahan yang diterima dan dipakai masing-masing gedung, termasuk bahan yang dibeli lewat SPK gabungan. Saldo stok utama tetap gabungan se-sekolah.

## 9. Pembayaran Upah

Menu **Keuangan → Pembayaran Upah** mencatat pembayaran tenaga kerja: tanggal, gedung (dan pekerjaan bila tertaut), jenis tenaga, nama penerima, orang-hari, dan tarif per OH (kosongkan untuk memakai tarif master). Jumlah bayar = orang-hari × tarif, dan **diperiksa ke pagu sebelum disimpan** — pembayaran yang membuat total belanja melewati pagu ditolak. Pencatatan pembayaran hanya dapat dilakukan peran **bendahara** atau **admin**.

Di halaman yang sama ada **Master Tarif Upah**: tarif resmi per jenis tenaga untuk sekolah ini. Master upah hanya mengenal **dua jenis kanonik: Pekerja dan Tukang** (tarif per OH). Varian nama lain (mis. Tukang Batu, Tukang Kayu) dan komponen Mandor/Kepala Tukang **tidak dipakai lagi**; rapikan data lama dengan tombol **Rapikan ke 2 Jenis** di halaman **Material → Master Bahan & Upah**. Formulir "Tambah ke Master" hanya menawarkan jenis kanonik yang belum ada.

## 10. Master Bahan

Menu **Material → Master Bahan & Upah**: daftar bahan sekolah aktif (kode, nama, satuan beli, harga RAB, dan kebutuhan per gedung warisan format lama). **Kotak pencarian di atas tabel menyaring langsung** — ketik sebagian kode atau nama, daftar berubah seketika tanpa memuat ulang halaman (kotak pencarian di topbar juga menuju ke sini). Bahan dapat ditambah, diubah, dan dihapus. Kode bahan unik per sekolah. Master ini terisi otomatis dari impor dokumen perencana dan menjadi acuan harga pertama pada rincian SPK.

**Hapus banyak sekaligus (bulk):** centang kotak di kiri baris (kotak di kepala tabel mencentang semua), lalu klik **Hapus Terpilih (n)** dan konfirmasi. Bahan yang sudah dipakai di rincian SPK tidak ikut terhapus — ia dipertahankan dan namanya disebutkan pada laporan hasil. Hal yang sama berlaku di tabel **Master Upah** di bawahnya: kotak saring menyaring jenis tenaga seketika, dan baris tercentang dapat dihapus sekaligus.

**Tombol Kosongkan:** **Kosongkan Master Bahan** / **Kosongkan Master Upah** menghapus seluruh isi master sekolah aktif dalam sekali klik (sesudah dialog konfirmasi). Semua penghapusan di halaman ini bersifat **hapus lunak** — data tidak musnah. Bahan yang sudah dipakai di rincian SPK tetap dipertahankan saat pengosongan bahan. Menambahkan kembali bahan berkode sama dengan yang pernah dihapus akan **menghidupkan kembali** baris lamanya.

**Fitur yang sama di semua modul.** Pencarian langsung, hapus terpilih (centang), dan tombol Kosongkan juga tersedia di: Kelola Gedung, BOQ Pekerjaan per gedung, Biaya Non-Gedung, Register SPK, Survei Harga dan Negosiasi (centang per bahan), Invoice, Buku Material, Riwayat Pembayaran Upah, Master Penyedia (di Pengaturan), Transaksi Manual Buku Kas, Penerimaan Dana, Bukti Setor & Pajak, Foto Progres (centang pada kartu foto), dan BA Revisi Pekerjaan. Penjaga tiap modul tetap berlaku: menghapus SPK membuat rinciannya belum bernomor, penyedia yang dipakai SPK dilewati, dan foto ikut menghapus berkasnya. Perbedaan yang disengaja: **Manajemen User** hanya mempunyai hapus terpilih (akun sendiri dan admin terakhir selalu dilewati; tidak ada Kosongkan), **Kosongkan Buku Kas** hanya menghapus transaksi yang dicatat manual (baris dari jurnal pembayaran, upah, dan bukti setor dihapus di modul sumbernya), sedangkan halaman **Kelola Sekolah** dan **Dokumen BAP/BAST** hanya mendapat kotak pencarian langsung.

### Tab Dipakai Gedung & Tidak Dipakai Gedung, Gabungkan Duplikat, Sinkronkan Harga

Halaman Master Bahan terbagi dua tab: **Dipakai Gedung** (bahan yang dipakai analisa gedung aktif yang didanai) dan **Tidak Dipakai Gedung**. Tiga alat perapian tersedia di halaman ini:

- **Perbaiki & Gabungkan (duplikat):** impor dapat melahirkan bahan yang sama dengan nama persis sama tetapi kode/satuan/harga berbeda. Alat ini mendeteksi kelompok nama yang sama dan menggabungkannya ke satu bahan utama: seluruh referensi (komponen analisa, rincian SPK, dsb.) dialihkan ke bahan utama, lalu duplikatnya dihapus. Berlaku juga untuk **Master Upah**. Deteksinya hanya untuk **nama yang persis sama** — beda ejaan dianggap bahan berbeda.
- **Sinkronkan Harga ke Analisa:** menulis harga master bahan ke komponen-komponen analisa yang tertaut, agar harga AHSP hasil hitungan mengikuti harga master terbaru. Harga baru disesuaikan ke satuan komponen lewat faktor konversi (mis. harga per zak menjadi per kg), dan perbandingannya memakai tarif yang dinormalkan ke satuan master — jadi tarif yang setara tidak dianggap berubah. Komponen yang satuan komponennya berbeda dari satuan master **tanpa faktor konversi (manual maupun bawaan)** dilewati, bukan disalin mentah, agar AHSP tidak rusak (mis. baut per buah tidak tertimpa harga per dus). Hanya komponen yang analisanya benar-benar dipakai pekerjaan di gedung didanai yang disinkronkan; komponen pada analisa yang tidak dipakai pekerjaan gedung mana pun tidak diubah. Sesudah tombol ditekan, modal konfirmasi menampilkan **hanya tarif yang benar-benar berubah** (yang tetap, yang dilewati karena satuan, dan yang bahannya belum berharga ditampilkan sebagai ringkasan), baru sesudah Anda menyetujui harga ditulis.
- **Konversi satuan manual:** untuk bahan yang satuan komponen analisanya berbeda dari satuan belinya (mis. semen kg → zak), faktor konversi manual yang diisi di halaman Analisa **mengalahkan** konversi bawaan aplikasi.

- **Kelengkapan Harga & Tarif:** panel waspada di puncak halaman (hanya muncul bila ada yang kosong) menampilkan **bahan tanpa harga acuan** dan **jenis upah tanpa tarif** dalam satu formulir — isi semuanya lalu klik **Simpan Semua Isian**. Yang sudah terisi tidak ditampilkan lagi. Bahan/upah tanpa harga terhitung Rp 0 di kebutuhan dan rencana belanja.

- **Tarif Analisa per Gedung** (kolom di tabel Master Bahan): menampilkan tarif komponen analisa per gedung didanai, dengan lencana **Sama/Beda** terhadap harga acuan bahan. Bila seragam di semua gedung, diringkas "Semua Gedung". Perbandingan menormalkan tarif komponen ke satuan master lewat faktor konversi, sehingga tarif per kg yang setara per zak tetap terbaca Sama.

- **Rapikan ke 2 Jenis (Master Upah):** menyeragamkan semua varian upah menjadi **Pekerja** dan **Tukang**, menghapus komponen Mandor/Kepala Tukang, menautkan ulang komponen ke master kanonik, dan menghapus lunak master lain. Jalankan sekali sesudah impor data lama.>

## 11. SPK & Rincian — termasuk Belum Bernomor

Menu **Belanja → Register SPK**: register Surat Perintah Kerja. Tambah SPK (nomor, tanggal, masa mulai–selesai, penyedia, paket, gedung tertaut bila dari rencana), ubah, atau hapus (menghapus SPK membuat rinciannya kembali **belum bernomor**, bukan ikut terhapus).

**Pembelian dari masyarakat.** Untuk belanja seperti pasir, batu, atau kayu yang dibeli dari warga, pilih penyedia khusus **"Masyarakat"**, lalu isi kolom **Nama Penerima** dengan nama orangnya. Nama itu **diwarisi ke semua dokumen turunan** SPK tersebut — cetak SPK, BAP, BAST, dan Invoice mencantumkan nama penerima itu sebagai pihak kedua, tanpa perlu membuat satu record penyedia per orang.

### Rincian SPK

Klik nomor SPK untuk membuka rinciannya: tambah baris bahan, isi jumlah dan harga. **Harga acuan berjenjang**: aplikasi menyarankan harga dari negosiasi → penawaran survei → harga RAB, dan Anda tetap dapat mengetik harga lain. Total SPK dan PPN (11% dari dasar pengenaan) terhitung otomatis.

### Kebutuhan Tenaga Kerja

Panel **Kebutuhan Tenaga Kerja** pada halaman rincian SPK mencatat perkiraan tenaga kerja untuk paket itu: **jenis tenaga** (Pekerja/Tukang), **keahlian** (mis. Batu, Kayu, Besi, Cat), **kebutuhan OH** (orang-hari), dan **perkiraan jumlah orang** — supaya panitia tahu berapa tenaga yang harus disediakan sebelum pekerjaan dimulai.

- Bila SPK dibuat dari **Rencana Belanja**, panel ini **terisi otomatis** dari hitungan kebutuhan pekerja (Langkah 4): OH per jenis tenaga, dan jumlah orang = OH dibagi target hari kerja.
- Untuk SPK yang dibuat manual, isi lewat formulir **+ Tambah Baris** di panel yang sama; baris dapat dihapus per baris, dan total OH serta total orang terhitung di kaki tabel.
- Catatan ini bersifat **perkiraan untuk panitia** — bukan dokumen pembayaran. Pembayaran upah yang sebenarnya tetap dicatat di menu Pembayaran Upah (Bab 9).
- Bila ada barisnya, catatan ini **ikut tercetak pada SPK** (dan pada Cetak SPJ Lengkap).

### Belum Bernomor

Menu **Belanja → Belum Bernomor SPK** mengumpulkan baris rincian yang belum masuk SPK mana pun (mis. hasil impor data lama atau SPK yang dihapus). Pasangkan baris ke SPK tujuan dari halaman ini, atau **hapus** baris yang memang tidak terpakai lagi lewat tombol **Hapus** pada barisnya (sesudah dihapus, baris tidak bisa dipasangkan lagi).

## 12. Survei Harga & Negosiasi

- **Survei Harga** (menu **Belanja → Survei Harga**): catat harga penawaran beberapa toko untuk bahan yang sama; kolom Termurah dan Terpilih terhitung otomatis.
- **Negosiasi** (menu **Belanja → Negosiasi**): catat harga hasil negosiasi per bahan — harga ini menjadi **acuan pertama** saat mengisi rincian SPK.

## 13. BAP & BAST — dan mencetak dokumen

### Alur tercepat: Tuntaskan SPK satu panel

Sesudah barang SPK diterima, seluruh rantai SPJ dapat diselesaikan **tanpa berpindah menu**, dari panel **Tuntaskan SPK — Satu Panel** di halaman rincian SPK:

1. Isi **Tanggal terima barang** (otomatis terisi tanggal selesai SPK), lalu klik **Buat BAP + BAST + Invoice Sekaligus**. Aplikasi membuat dalam satu langkah: **BAP** berisi semua rincian SPK dengan kondisi "Cukup", **BAST** yang tertaut ke BAP itu, dan **Invoice** senilai total SPK — masing-masing bernomor urut berikutnya.
2. Panel berubah menampilkan ringkasan invoice (nilai, DPP, PPN, PPh 22, dan bersih yang diterima penyedia). Klik **Catat Bayar** di panel yang sama untuk mencatat pembayarannya — aturan pajak dan pemeriksaan pagu sama persis dengan Jurnal Pembayaran (Bab 14).
3. Klik **Cetak SPJ Lengkap (SPK + BAP + BAST + Invoice)** untuk mencetak keempat dokumen dalam **satu berkas**, masing-masing mulai di halaman baru.

Syaratnya: SPK **belum mempunyai BAP maupun Invoice**. SPK yang rantainya sudah berjalan sebagian (mis. BAP sudah dibuat lewat menu Dokumen) tidak memakai panel ini — lanjutkan lewat jalur manual di bawah. Panel juga menampilkan **ceklis kelengkapan** SPK (SPK/BAP/BAST/Invoice/bukti bayar dan penanda manual seperti kuitansi fisik), sehingga kekurangan berkas terlihat sekilas.

### Jalur manual lewat menu Dokumen

Menu **Belanja → BAP & BAST / Cetak** (halaman Dokumen, satu baris per SPK): untuk setiap SPK, buat **BAP** (Berita Acara Pemeriksaan hasil pekerjaan), periksa/ubah isinya (termasuk rincian hasil pemeriksaan), lalu buat **BAST** (Berita Acara Serah Terima) dari BAP itu. Aturannya ketat: tombol Buat BAP hilang bila seluruh rincian sudah ter-BAP, dan **satu BAP hanya mempunyai satu BAST**. Dokumen yang dapat dicetak dari aplikasi (berkop sekolah, lengkap dengan terbilang dan blok tanda tangan):

- **SPK**, **BAP**, **BAST** — dari halaman ini (nomor surat format `422/<JENIS>/<urut>/<tahun>`),
- **Invoice** — dari halaman Invoice,
- **SPJ Lengkap** (SPK + BAP + BAST + Invoice dalam satu berkas) — dari panel Tuntaskan SPK di rincian SPK,
- **Papan Nama Proyek** — dari halaman Dokumen, untuk dipasang di lokasi pekerjaan,
- **Laporan** dan **LPJ** — dari halaman masing-masing.

## 14. Invoice & Jurnal Pembayaran

Menu **Keuangan → Invoice**: catat tagihan penyedia (nomor urut, toko, tanggal, SPK/BAP/BAST terkait, nilai). Invoice dapat diubah, dihapus, dan **dicetak** (nomor `422/INV/<urut>/<tahun>`).

> **Invoice terkunci sesudah dibayar.** Invoice yang sudah memiliki jurnal pembayaran aktif tidak dapat diubah, dihapus, atau ikut dikosongkan. Untuk mengoreksi, hapus dulu jurnal pembayarannya (tercatat di Jejak Audit), baru invoice dapat diperbaiki. Ini menjaga agar buku kas dan laporan tidak berubah tanpa jejak.

### Jurnal Pembayaran

Menu **Keuangan → Jurnal Pembayaran** mencatat pembayaran invoice: pilih invoice, tanggal bayar, dan cara bayar. Aplikasi menghitung **PPh 22 sebesar 1,5%** secara otomatis, menandai invoice **Sudah Bayar**, dan memeriksa total terhadap pagu — jurnal yang menembus pagu ditolak. Baris jurnal dapat dihapus bila keliru catat (invoice kembali Belum Bayar). Pencatatan jurnal hanya untuk peran **bendahara** atau **admin**.

Pembayaran invoice sebuah SPK juga dapat dicatat langsung dari panel **Tuntaskan SPK** di rincian SPK (Bab 13) — hasil dan aturannya sama, panel hanya menghemat perpindahan menu.

### Penerimaan Dana Bantuan

Menu **Keuangan → Penerimaan Dana** mencatat pencairan Bantuan Pemerintah yang diterima sekolah. Sesuai Juknis Revitalisasi SMP 2026, pencairan bertahap: **Tahap 1 sebesar 70%**, dan **Tahap 2 sebesar 30%** sesudah kemajuan pekerjaan mencapai minimal 50%. Catat tanggal, tahap, jumlah, dan kanal penerimaannya — penerimaan otomatis tampil sebagai kas masuk di Buku Kas, dan kuitansi penerimaannya dapat **dicetak** mengikuti format dokumen Juknis. Pencatatan hanya untuk peran **bendahara** atau **admin**.

### Buku Kas (BKU)

Menu **Keuangan → Buku Kas** adalah pembukuan resmi menurut Juknis: **Buku Kas Umum (BKU), Buku Bank, dan Buku Pembantu Kas Tunai**. Isinya gabungan otomatis dari:

- transaksi manual yang dicatat di halaman ini (penerimaan dana, koreksi),
- pembayaran invoice (Jurnal Pembayaran),
- pembayaran upah, dan
- bukti setor pajak.

Buku ditutup per bulan (saldo awal, mutasi, saldo akhir) dan dapat **dicetak** mengikuti format dokumen Juknis. **Transaksi manual** hanya dapat dicatat peran **bendahara** atau **admin**. Mengosongkan Buku Kas hanya menghapus transaksi manualnya — baris dari modul lain dihapus di modul sumbernya masing-masing — dan hanya dapat dilakukan **admin**.

### Bukti Setor & Pajak

Menu **Keuangan → Bukti Setor & Pajak** mencatat penyetoran pajak yang dipungut/dipotong bendahara (**PPh 22, PPN, PPh 21, PPh 23, PPh Pasal 4 ayat (2)**) ke kas negara: tanggal setor, masa pajak, kode e-billing, dan **NTPN**. Register ini membandingkan setoran terhadap kewajiban pajak yang timbul dari transaksi, dan juga mencatat pengembalian sisa dana bantuan lewat **SIMPONI** — sesuai tata cara penyetoran pada Juknis. Pencatatan hanya untuk peran **bendahara** atau **admin**.

## 15. Buku Material & Sisa Bahan

- **Buku Material** (menu **Material → Buku Material**): catatan keluar–masuk bahan per tanggal (dari pembelian/SPK dan pemakaian), menjadi dasar stok. Kolom **Gedung Tujuan** boleh diisi untuk mencatat pemakaian per gedung; saldo utama tetap se-sekolah.
- Panel **Kebutuhan Bahan per Gedung** membandingkan kebutuhan BOQ tiap gedung dengan yang sudah keluar ke gedung itu (sisa kebutuhan; baris merah berarti keluar sudah melebihi kebutuhan atau bahan itu bukan kebutuhan gedung tersebut) — periksa panel ini sebelum mencatat agar tidak salah gedung. Panel dapat **disaring per gedung**, dan gedung **diurutkan dari persentase sisa kebutuhan terbesar** (yang paling belum terpenuhi tampil lebih dulu; persennya tertulis pada judul tiap gedung).
- Pada form catat, **pilihan bahan hanya menampilkan bahan yang masih berstok** (sudah di-BAST + saldo buku) beserta sisa stoknya; sesudah gedung dan bahan dipilih, petunjuk di bawah form menampilkan stok tersedia serta kebutuhan, sudah keluar, dan sisa kebutuhan gedung itu untuk bahan tersebut. Jumlah **Keluar** yang melebihi stok tersedia **ditolak** aplikasi.
- **Sisa Bahan** (menu **Material → Sisa Bahan**): perbandingan kebutuhan RAB dengan realisasi pemakaian per bahan; sisa negatif ditandai (juga muncul di panel Perlu Perhatian dashboard).

## 16. Laporan & Ekspor Excel

Menu **Laporan → Laporan & Ekspor**: ringkasan belanja sekolah, rekap per gedung, dan rekap per SPK. Tersedia:

- **Cetak laporan** ber-kop sekolah,
- **Ekspor Excel**: Rekap SPK, Buku Material, dan Sisa Bahan (berkas `.xlsx`).

### Template Laporan — dokumen berformat bebas dari template Excel

Menu **Laporan → Template Laporan** adalah mesin laporan yang mengikuti cara kerja SPJ BOSP: format dokumen tidak dikunci aplikasi, melainkan ditentukan berkas template Excel yang Bapak/Ibu tata sendiri.

1. **Unduh template contoh** untuk jenis yang diinginkan — Dokumen SPK (konteks satu SPK), Laporan Gedung (satu gedung), atau Laporan Sekolah (rekap). Berkas contoh berisi semua penanda yang tersedia.
2. **Tata ulang di Excel** sebebasnya (kop, tabel, tanda tangan): yang penting penanda seperti `{{NAMA_SEKOLAH}}`, `{{TOTAL_SPK_RP}}`, `{{TANGGAL_SPK_PANJANG}}` ejaannya tidak diubah. Untuk tabel rincian, tulis penanda baris (`{{RINCIAN_NAMA}}`, `{{RINCIAN_QTY}}`, dst.) pada **satu baris contoh** — saat render, baris itu digandakan mengikuti jumlah data.
3. **Unggah** berkasnya di halaman Template Laporan. Aplikasi memindai dan mencatat penanda yang terdeteksi.
4. **Render** dari data nyata: pilih SPK/gedung pada baris template, atau langsung dari tombol *Unduh dari Template* di Detail SPK dan halaman Gedung. Hasilnya berkas .xlsx siap cetak.

Aturan mesinnya: nilai uang tersedia dua bentuk — angka polos (mis. `{{TOTAL_SPK}}` → 8750000, bisa dijumlah Excel) dan terformat (`{{TOTAL_SPK_RP}}` → Rp 8.750.000). Bila ada penanda yang tidak dikenal atau tidak ada nilainya, **render dibatalkan** dan penandanya disebutkan — perbaiki ejaan penanda pada template terhadap katalog di halaman yang sama (katalog menampilkan contoh nilai dari data nyata). Cetakan HTML bawaan aplikasi tetap tersedia seperti biasa; template adalah jalur tambahan untuk format khusus (mis. format dinas kabupaten).

Kemampuan blok berulang: satu sheet boleh memuat banyak tabel berulang — blok contoh adalah baris-baris berurutan berpenanda awalan yang sama (boleh lebih dari satu baris), dan awalan yang sama boleh muncul lagi di tempat lain pada sheet yang sama; semuanya digandakan mengikuti datanya, dan seluruh isi baris contoh (termasuk label tanpa penanda) ikut tersalin. Penanda boleh ditebalkan sebagian di Excel — sel berformat campuran tetap terbaca dan diganti (hasil rendernya menjadi teks polos satu format untuk sel itu). Pada konteks SPK tersedia juga blok daftar lengkap `RPT_BAP_…`, `RPT_BAST_…`, dan `RPT_INV_…` yang memuat SEMUA BAP/BAST/Invoice SPK itu (penanda biasa `BAP_…`/`BAST_…`/`INVOICE_…` tetap hanya dokumen pertamanya). Kolom `KERJA_…` pada laporan gedung memakai harga berjalan (revisi disahkan → analisa AHSP → dokumen), basis yang sama dengan Nilai Analisa, jadi total kolom `KERJA_NILAI` menyamai `NILAI_ANALISA` (selisih pembulatan per baris paling beberapa rupiah); harga dokumen asli tersedia sebagai `KERJA_HARGA_DOKUMEN`/`KERJA_NILAI_DOKUMEN`. Ukuran berkas template dibatasi 5 MB, dan berkas template ikut tercadangkan: Backup dari Pemeliharaan Database menjadi paket .zip (database + template) dan memulihkannya mengembalikan template juga.

## 17. LPJ — sekolah dan per gedung

Menu **Laporan → LPJ & Realisasi**: pertanggungjawaban per gedung (anggaran BOQ, realisasi belanja bahan + upah, persen fisik, sisa) dan rekap gabungan satu sekolah. LPJ dapat dibuka per gedung maupun **dicetak** untuk sekolah dan per gedung. Menu **Laporan → RAB Gabungan** menyajikan RAB beberapa gedung sekaligus (pekerjaan sama dijumlahkan lintas gedung) beserta cetaknya.

## 18. Manajemen Pengguna

Menu **Pengaturan → Manajemen User** (khusus admin): tambah pengguna, ubah nama/peran/kata sandi, dan hapus pengguna. Peran yang tersedia: **admin, ketua, bendahara, pelaksana, kepsek, operator**. Perlindungan bawaan: akun Anda sendiri tidak dapat dihapus, dan admin terakhir tidak dapat dihapus/diturunkan perannya. Pengguna yang dinonaktifkan atau terhapus tidak dapat masuk lagi.

**Akun baru wajib ganti sandi.** Setiap akun yang dibuat admin — dan akun bawaan `admin/admin123` — memiliki status *harus ganti sandi*. Sesudah masuk, pengguna hanya bisa membuka **Profil** sampai sandinya diganti; menu lain menolak akses. Admin dapat mengatur ulang sandi pengguna dari halaman ini, dan pengguna itu akan diminta menggantinya lagi saat masuk.

**Aksi sensitif dibatasi peran di server**, bukan hanya disembunyikan di tampilan:

| Aksi | Peran yang diizinkan |
|---|---|
| Pengesahan BA Revisi Pekerjaan | ketua, kepsek, admin |
| Jurnal pembayaran, penerimaan dana, bukti setor, pembayaran upah, transaksi manual Buku Kas | bendahara, admin |
| Impor dokumen, Rapikan ke 2 Jenis, Samakan harga di Validasi Harga | operator, admin |
| Mengosongkan master/modul (Kosongkan Bahan, Kosongkan Buku Kas, dll.) | admin |

Admin selalu lolos semua gerbang. Bila tombol aksi tidak muncul padahal peran Anda seharusnya berhak, periksa pembagian peran di halaman ini.

Tampilan dapat disesuaikan per pengguna: **tema gelap** (tombol bulan di topbar) dan **ukuran huruf** (tombol A−/A+); keduanya diingat oleh peramban.

### Hak Akses Menu per Peran

Menu **Pengaturan → Akses Menu** (khusus admin) mengatur menu apa saja yang boleh dibuka tiap peran selain admin (admin selalu boleh semua). Bawaan aplikasi: semua peran boleh semua menu. Hilangkan centang pada menu yang tidak perlu diakses suatu peran, lalu simpan — menu itu hilang dari tampilan peran tersebut dan alamatnya pun ditolak bila diketik langsung. Tombol **Atur Ulang** mengembalikan akses semua peran ke bawaan. Gunakan ini, misalnya, agar bendahara tidak membuka menu perencanaan, atau pelaksana hanya melihat menu pembangunan dan belanja.

### Jejak Audit

Menu **Pengaturan → Jejak Audit** mencatat siapa melakukan apa dan kapan: pengguna, modul, jenis tindakan (tambah/ubah/hapus/pengesahan/dsb.), dan keterangan ringkasnya — misalnya pembuatan SPK, pengesahan BA Revisi, perubahan hak akses menu, dan pemulihan data. Halaman ini bahan penelusuran bila ada angka yang berubah tanpa diketahui siapa yang mengubahnya.

## 19. Menghapus Data dengan Aman

- Setiap tombol **Hapus** membuka **modal konfirmasi** gaya TailAdmin — data tidak terhapus sebelum Anda menekan **Ya, Hapus**. Klik **Batal**, tekan **Esc**, atau klik di luar modal untuk membatalkan.
- Penghapusan bersifat **lunak (soft delete)**: baris ditandai terhapus dan disembunyikan dari semua daftar, laporan, dan hitungan, tetapi masih tersimpan di database dan dapat dipulihkan oleh admin database bila terlanjur.
- Menghapus **gedung** ikut menyembunyikan BOQ dan realisasinya; menghapus **SPK** mengembalikan rinciannya menjadi belum bernomor.

### Pemeliharaan Database (khusus admin)

Menu **Pengaturan → Pemeliharaan Database** adalah ruang perawatan data aplikasi:

- **Informasi & ukuran tabel** — melihat isi database per tabel.
- **Backup SQL** — membuat cadangan seluruh database sebagai berkas `.sql`, mengunduhnya, atau menghapus cadangan lama. Buat backup **sebelum** pekerjaan besar (impor ulang, pengosongan master, pemulihan data).
- **Pulihkan** — memulihkan database dari berkas backup yang diunggah. Pemulihan menimpa keadaan sekarang; pastikan backup terakhir sudah dibuat sebelum memulihkan.
- **Optimasi tabel** — merapikan tabel database (setara OPTIMIZE TABLE) agar berkas database tidak menggelembung sesudah banyak penghapusan.
- **Sampah** — daftar baris yang terhapus lunak dari seluruh modul. Dari sini baris dapat **dipulihkan** (kembali tampil seperti semula) atau **dihapus permanen**. Inilah jalan resmi memulihkan data yang terlanjur dihapus — termasuk bahan/upah hasil Gabungkan Duplikat dan baris Belum Bernomor.

## 20. Pemecahan Masalah (FAQ)

**Impor unggah gagal / berkas ditolak karena ukuran.**
Berkas perencana besar (8–17 MB) sering melewati batas unggah PHP. Pakai **Cara 1 (folder `writable/impor`)** — tanpa batas ukuran.

**Sesudah impor, nilai BOQ gedung tidak sama persis dengan Grand Total di sheet.**
Wajar pada sebagian kecil gedung: ada sheet yang Grand Total-nya memang tidak sama dengan jumlah barisnya di dokumen perencana sendiri, dan ada kode analisa yang harga BOQ-nya berbeda dari harga analisanya. Aplikasi mengikuti jumlah baris pekerjaan; selisihnya terlihat di panel Riwayat & Verifikasi. Konfirmasikan ke perencana bila selisihnya besar.

**Harga satuan pekerjaan terhitung 0.**
Kode analisa pekerjaan tidak ditemukan di Analisa maupun Harsat (mis. kode varian khusus di BOQ). Isi harga lewat kolom Harga Satuan pada template, atau perbaiki kode analisanya di tab BOQ.

**Jenis upah di dokumen perencana berbeda-beda (Tukang Batu, Tukang Kayu, dan seterusnya).**
Aplikasi hanya memakai dua jenis upah kanonik: **Pekerja** dan **Tukang**; komponen Kepala Tukang dan Mandor sudah dihapus. Jalankan tombol **Rapikan ke 2 Jenis** di halaman **Material → Master Bahan & Upah** sekali sesudah impor data lama — semua varian nama diseragamkan dan ditautkan ke master Pekerja/Tukang, lalu tarifnya diatur di **Keuangan → Pembayaran Upah**.

**Transaksi ditolak karena pagu.**
Total belanja (invoice terbayar + pembayaran upah) akan melewati pagu anggaran sekolah. Periksa sisa pagu di Rencana Belanja/Pengaturan; naikkan pagu di Pengaturan bila memang ada perubahan anggaran resmi.

**Tampilan masih versi lama sesudah pembaruan aplikasi.**
Tekan **Ctrl+F5** di peramban untuk memuat ulang tanpa cache.

**Lupa kata sandi.**
Admin dapat mengatur ulang sandi pengguna mana pun dari menu **Pengaturan → Manajemen User**. Pengguna tersebut akan diminta mengganti sandi lagi saat masuk berikutnya.

## 21. Lampiran — Ringkasan Hasil QA

Pengujian browser menyeluruh terakhir: **22 November 2026**, terhadap halaman dan alur utama saat itu:

- **34/34 pemeriksaan halaman & tampilan lulus** — login (termasuk penolakan sandi salah), dashboard, kelola sekolah, pengaturan, impor, daftar & detail gedung (4 tab), rencana belanja, pembayaran upah, master bahan, SPK & rincian, belum bernomor, survei, negosiasi, BAP & BAST, invoice, jurnal, buku material, sisa bahan, laporan, LPJ sekolah & per gedung, pengguna, profil, dan halaman cetak (SPK, laporan, LPJ).
- **11/11 pemeriksaan fungsi lulus** — ekspor Excel (rekap SPK, buku material, sisa bahan), unduh template kosong, ekspor template terisi, cetak invoice, pembuatan BAP, simpan + hapus pembayaran upah melalui modal, modal konfirmasi hapus, tema gelap, dan **0 dialog bawaan peramban** di seluruh pengujian.
- Uji impor-balik template: template terisi SMP Negeri 3 Ranto Baek SATAP diimpor kembali dengan selisih nilai **Rp 0**; jalur harga Harsat saja berselisih +1,7% (sisa selisih berasal dari inkonsistensi dokumen perencana sendiri).
- Catatan data (bukan kerusakan aplikasi): panel Perlu Perhatian menandai data warisan yang menunggu keputusan sekolah — 159 baris rincian belum bernomor, ketidaksamaan nilai Invoice No. 1 dengan SPK 1, dan dua sisa bahan negatif.

**Belum diuji ulang secara menyeluruh** (fitur sesudah 22 November): Acuan AHSP Nasional, Validasi Harga Satuan, Simulasi Mingguan, SPK Gabungan dari Rencana Mingguan, Tuntaskan SPK satu panel, dan Template Laporan. Langkah kerjanya sudah ditulis di panduan ini berdasarkan kode dan uji coba terbatas, tetapi belum melewati putaran QA browser penuh. Selain itu, **9 tes unit** (`composer test`) mencakup fungsi dasar `BaseController` (terbilang, rupiah, pajak, ketentuan PBJ, faktor satuan) — bukan pengganti QA antarmuka.

---

## Lampiran — Glosarium

- **BOQ** (Bill of Quantity): daftar pekerjaan gedung dengan volume dan harga satuan; dasar nilai gedung.
- **AHSP**: Analisa Harga Satuan Pekerjaan — komponen (bahan, upah, peralatan) × koefisien yang membentuk harga satuan.
- **SPK**: Surat Perintah Kerja — dokumen pembelian bahan/pekerjaan ke penyedia.
- **BAP**: Berita Acara Pemeriksaan hasil pekerjaan.
- **BAST**: Berita Acara Serah Terima (tertatu ke satu BAP).
- **LPJ**: Laporan Pertanggungjawaban (per gedung dan gabungan sekolah).
- **RAB**: Rencana Anggaran Biaya.
- **OH**: Orang-hari (satuan kebutuhan tenaga).
- **Pagu**: batas total belanja yang boleh dikeluarkan.
- **Soft delete**: penghapusan lunak (baris ditandai terhapus, dapat dipulihkan).

*Panduan ini diperbarui 24 November 2026 mengikuti kode aplikasi (hingga migrasi 000028). Data contoh yang disebut berasal dari SMP Negeri 3 Ranto Baek SATAP; tampilan sekolah Anda sama, isinya mengikuti data sekolah Anda.*
