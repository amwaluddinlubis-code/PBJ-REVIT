# Panduan Penggunaan Aplikasi Analisa Perencana (PBJ-REVIT)

**Aplikasi pengelolaan pembangunan/revitalisasi sekolah berbasis dokumen perencana** — dari impor RAB perencana, perhitungan kebutuhan bahan & upah, perencanaan belanja, sampai LPJ.

Versi panduan: 22 November 2026 · Aplikasi: PBJ-REVIT (CodeIgniter 4) · Contoh data pada gambar: SMP Negeri 3 Ranto Baek SATAP

---

## Daftar Isi

1. Pendahuluan — alur besar aplikasi
2. Memulai — masuk, tampilan, profil & sandi
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

## 2. Memulai — masuk, tampilan, profil & sandi

Buka alamat aplikasi (di komputer server, mis. `http://localhost:81/pbj_revit/`), lalu masuk dengan nama pengguna dan kata sandi yang diberikan admin. Akun bawaan awal adalah **admin / admin123** — segera ganti sandinya sesudah masuk pertama kali.

![Halaman masuk](panduan_pbj/01-login.png)

Sesudah masuk, tampilan utama terdiri dari:

- **Menu samping (kiri)** — berkelompok: Perencanaan, Pembangunan Gedung, Belanja, Bahan & Laporan, Pengaturan. Klik nama kelompok untuk membuka/menutup isinya; tombol di topbar menyembunyikan/menampilkan menu samping. Di HP, menu menjadi laci geser.
- **Pengalih sekolah aktif** di bagian atas menu samping.
- **Topbar** — pencarian bahan, cip nama sekolah aktif, tombol ukuran huruf (A- dan A+), tombol tema gelap/terang, dan menu profil.
- **Notifikasi toast** — setiap operasi simpan/ubah/hapus memunculkan pemberitahuan "Berhasil/Gagal" di kanan atas yang hilang sendiri dalam ±5 detik.

![Dashboard aplikasi](panduan_pbj/02-dashboard.png)

### Profil & ganti sandi

Klik avatar/nama Anda di topbar → **Profil**: ubah nama tampilan, dan ganti kata sandi (isi sandi lama + sandi baru). Tombol **Keluar** juga ada di menu yang sama.

![Halaman profil](panduan_pbj/27-profil.png)

## 3. Mengelola Sekolah & Pengaturan

### Menambah & memilih sekolah

Menu **Pengaturan → Kelola Sekolah**: daftar semua sekolah, tombol tambah sekolah baru (form singkat: nama sekolah), pindah sekolah aktif, dan hapus sekolah yang masih kosong. Sekolah yang baru dibuat selanjutnya dilengkapi identitasnya di halaman Pengaturan.

![Kelola sekolah](panduan_pbj/03-sekolah.png)

### Pengaturan sekolah aktif

Menu **Pengaturan → Identitas & Pengaturan** berisi lima kelompok untuk sekolah yang sedang aktif:

1. **Identitas Sekolah** — nama, NPSN, alamat, desa/kecamatan/kabupaten/provinsi, tahun anggaran, paket pekerjaan.
2. **Pagu Anggaran** — total anggaran (pagu) sekolah; ditampilkan juga belanja terbayar dan sisa pagu. Pagu ini yang menjadi kunci kontrol total belanja. Angka boleh diketik berformat ribuan (mis. `2.540.292.000`).
3. **Kop Surat** — baris kop pemerintah dan dinas untuk dokumen cetak.
4. **Panitia & Penandatangan** — kepala sekolah, ketua, sekretaris, pelaksana, dan nama-nama yang menandatangani dokumen.
5. **Pengaturan Lanjutan** (terlipat) — pemeriksa, toko/penyedia bawaan, tanggal survei.

Di halaman yang sama ada **Master Penyedia**: tambah, **Ubah**, dan hapus toko/rekanan yang dipakai pada SPK dan invoice.

![Pengaturan sekolah](panduan_pbj/04-pengaturan.png)

## 4. Impor Dokumen Perencana

Menu **Perencanaan → Impor Dokumen Perencana**. Ada dua format yang dikenali otomatis: **workbook RAB perencana** (format konsultan) dan **template manual** (format aplikasi sendiri).

![Panel impor](panduan_pbj/05-impor.png)

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

- Komponen dapat **ditambah, diubah, dan dihapus**; harga satuan analisa terhitung ulang dari komponennya.
- Komponen yang belum tertaut ke master bahan/upah dikumpulkan di panel **Komponen Belum Tertaut** — tautkan dari sana agar kebutuhan bahan/upah terbaca di BOQ dan Rencana Belanja.
- Untuk bahan yang satuan komponennya berbeda dari satuan beli master (mis. semen kg → zak), isi **konversi satuan manual** pada komponennya; konversi manual ini mengalahkan konversi bawaan aplikasi di semua hitungan.

### Memeriksa Analisa — Acuan AHSP Nasional & Validasi Harga Satuan

Dua menu pemeriksaan membantu memastikan analisa dan harga pekerjaan sekolah masuk akal:

- **Acuan AHSP Nasional** (menu Perencanaan): membandingkan **koefisien** analisa sekolah (dari dokumen perencana) terhadap acuan nasional **SE Dirjen Bina Konstruksi 68/SE/Dk/2024** (dasar Permen PUPR 8/2023). Pencocokan lewat kode analisa; harga acuan dihitung ulang memakai tarif komponen sekolah sendiri, sehingga yang dibandingkan murni koefisiennya. Master acuan diisi sekali lewat perintah `php spark impor:acuan` di folder aplikasi. Selisih koefisien yang besar layak ditanyakan ke perencana.
- **Validasi Harga Satuan** (menu Perencanaan): membandingkan harga satuan pekerjaan untuk **kode analisa yang sama** — antar pekerjaan itu sendiri dan terhadap harga hasil kalkulasi komponen analisanya. Bila ada pekerjaan yang harganya menyimpang dari saudara sekodenya, halaman ini menandainya dan menyediakan tombol untuk **menyamakan** harga dalam satu kode (satu pekerjaan, atau semua sekaligus). Harga berjalan yang berasal dari BA Revisi yang sudah disahkan **tidak disentuh** oleh penyamaan ini.

## 5. Dashboard & Panel Perlu Perhatian

Dashboard merangkum sekolah aktif: Total RAB, Total SPK Terdaftar, Realisasi Dibayar (bersih sesudah PPh 22), dan Sisa Anggaran; panel **Pembangunan Gedung** menampilkan nilai BOQ dan persen fisik tiap gedung; dan panel kuning **Perlu perhatian** menandai hal yang harus diperiksa, misalnya:

- baris rincian SPK yang **belum bernomor SPK** (dengan tautan untuk memasangkannya),
- invoice yang nilainya **tidak sama** dengan total SPK-nya,
- **sisa bahan negatif** (pemakaian melebihi RAB).

![Dashboard dengan panel perhatian](panduan_pbj/02-dashboard.png)

## 6. Gedung & BOQ — kebutuhan bahan, kebutuhan upah, realisasi

Menu **Pembangunan Gedung → Daftar Gedung** (atau klik nama gedung di menu samping). Setiap gedung dapat ditambah, diubah namanya, dan dihapus (menghapus gedung ikut menandai BOQ dan realisasinya).

![Daftar gedung](panduan_pbj/06-gedung.png)

Halaman detail gedung mempunyai empat tab:

### Tab BOQ Pekerjaan

Daftar pekerjaan gedung itu: bab/kelompok, uraian, kode analisa, volume, satuan, harga satuan, dan jumlah. Volume dapat **diubah langsung** di barisnya (kebutuhan bahan/upah dan nilai gedung terhitung ulang). Pekerjaan baru dapat ditambah; pekerjaan dapat dihapus lewat tombol hapus (dengan modal konfirmasi).

![Tab BOQ](panduan_pbj/07-gedung-boq.png)

### Tab Kebutuhan Bahan

Hitungan kebutuhan bahan gedung ini: untuk setiap bahan — satuan **komponen** dari analisa (mis. kg), jumlah kebutuhan, satuan **beli** (mis. zak), jumlah beli, dan nilai rupiahnya. Konversi yang dipakai: semen kg → zak (÷50), pasir kg → m³ (÷1.400), kerikil/batu kg → m³ (÷1.600), keramik bh → ktk (sesuai ukuran). Bila ada komponen yang belum tertaut ke master bahan, formulir penautan tersedia di tab ini.

![Tab kebutuhan bahan](panduan_pbj/08-gedung-bahan.png)

### Tab Kebutuhan Upah

Hitungan kebutuhan tenaga per jenis (Pekerja, Tukang Kayu, Tukang Batu, dst.): orang-hari (OH) hasil perkalian volume × koefisien analisa, tarif per OH dari master upah, dan total upahnya.

![Tab kebutuhan upah](panduan_pbj/09-gedung-upah.png)

### Tab Realisasi

Catatan realisasi fisik per tanggal: persen atau volume terealisasi dan keterangan. Persen fisik gedung dihitung dari catatan ini dan tampil di daftar gedung, dashboard, dan LPJ.

![Tab realisasi](panduan_pbj/10-gedung-realisasi.png)

### BA Revisi Pekerjaan & RAB Berjalan

Di lapangan, pekerjaan dapat berubah: volume bertambah/berkurang, pekerjaan baru muncul, atau pekerjaan diganti/dihapus. Menu **Pembangunan Gedung → BA Revisi Pekerjaan** mencatat perubahan itu secara resmi:

- Setiap revisi dicatat dengan jenisnya (**volume, baru, ganti, hapus**), uraian perubahan, dan nomor berita acaranya. Revisi yang dicatat berstatus **draft** dan **belum mengubah angka apa pun**.
- Sesudah berita acara ditandatangani dan revisi **disahkan**, barulah **RAB berjalan** pekerjaan itu bergerak (volume/harga revisi). **RAB awal tidak pernah berubah** — yang tampil di BOQ dan hitungan kebutuhan adalah nilai berjalan, dan riwayat perubahannya tersimpan di halaman ini.
- Berita acara revisi dapat **dicetak** untuk ditandatangani Kepala Sekolah, Ketua P2SP, dan Tim Teknis.

Prinsip yang sama berlaku untuk harga: nilai BOQ di Dashboard adalah gabungan nilai asli dokumen dan nilai hasil hitungan analisa berjalan; selisih keduanya ditampilkan berdampingan di panel Dashboard agar keputusan revisi anggaran (BA Revisi ke pemberi bantuan) punya dasar angka.

### Kurva S

Menu **Pembangunan Gedung → Kurva S** menggambar garis rencana kumulatif melawan realisasi kumulatif mingguan (dari catatan realisasi BOQ), dalam format dokumen jadwal Juknis:

- Bila **rencana resmi Time Schedule perencana** sudah diimpor (berkas JSON kurva, tombol impor di halaman ini), garis rencana memakai bobot resmi per minggu kalender proyek itu.
- Bila belum diimpor, garis rencana digambar sebagai **kurva S perkiraan** mengikuti tanggal SPK gedung — cukup untuk memantau keterlambatan secara kasar, tetapi imporlah rencana resmi agar perbandingannya sahih.

### Laporan Kemajuan

Menu **Pembangunan Gedung → Laporan Kemajuan** menyusun laporan kemajuan pekerjaan per gedung untuk dicetak: ringkasan kemajuan fisik terhadap rencana pada periode laporan, dan **cetak akhir** proyek. Gunakan bersama Kurva S: kurva untuk membaca deviasi, laporan kemajuan untuk dokumen resminya.

### Foto Progres

Menu **Pembangunan Gedung → Foto Progres** menyimpan dokumentasi foto per gedung dalam tiga kategori: kondisi **sebelum**, **selama (proses)**, dan **sesudah** pekerjaan. Unggah foto dari halaman ini (tersimpan di folder aplikasi dan tampil sebagai kartu foto yang dapat dicentang untuk dihapus banyak sekaligus); kumpulan foto ini menjadi lampiran laporan kemajuan dan laporan akhir sesuai Juknis.

## 7. Biaya Non-Gedung

Menu **Perencanaan → Biaya Non-Gedung** menampung biaya tingkat sekolah yang berada di luar BOQ gedung, sebagaimana lazimnya dokumen RAB revitalisasi:

- **Perencanaan & Pengawasan** — terbaca otomatis dari sheet *Prcn dan pgws* dokumen perencana. Pada contoh SMPN 3 Ranto Baek SATAP: Perencana Rp 21.017.634 (1,65% dari nilai konstruksi) dan Pengawas Rp 23.565.226 (1,85%), total Rp 44.582.860.
- **Biaya Pengelolaan** — terbaca otomatis dari sheet *ADMINISTRASI*: honor P2SP, ATK, komunikasi, konsumsi rapat, transportasi, dan pelaporan. Pada contoh yang sama totalnya Rp 20.435.000.
- **Perabot** — terbaca otomatis dari sheet *Perabot*: daftar perabot per ruangan (meja, kursi, lemari, papan, dst.). Hanya baris yang **terisi jumlah harga** yang diimpor, dikelompokkan per ruangan — sebagian besar sheet adalah daftar kebutuhan yang volumenya belum diisi. Pada contoh yang sama: 24 baris terisi senilai Rp 152.413.610 (Ruang Kelas Unit 1 Rp 94.710.000 dan blok Administrasi Rp 57.703.610).

![Halaman Biaya Non-Gedung](panduan_pbj/30-biaya-non-gedung.png)

![Kelompok Perabot pada Biaya Non-Gedung](panduan_pbj/31-perabot-biaya.png)

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

![Rencana belanja](panduan_pbj/11-rencana.png)

SPK yang dibuat dari rencana bernomor urut berikutnya, bertanggal hari ini, dan berisi baris bahan yang dicentang. **Catatan kebutuhan tenaga kerjanya ikut terisi otomatis** dari hitungan Langkah 4 (lihat Bab 11). Lengkapi penyedia dan tanggalnya di Register SPK atau halaman rincian SPK.

Harga satuan pada rencana boleh diisi melebihi harga dokumen — yang dikunci hanya **total keseluruhan terhadap pagu**.

### Rencana Mingguan

Menu **Perencanaan → Rencana Mingguan** memecah pekerjaan menjadi target per minggu untuk satu gedung:

1. Buat rencana mingguan: pilih gedung dan tanggal mulai minggu.
2. Isi **target volume** tiap pekerjaan untuk minggu itu.
3. Aplikasi menghitung **daftar belanja bersih minggu itu**: kebutuhan kotor (target × koefisien analisa) dikurangi **stok tersedia** (barang sudah diterima/BAST dan saldo Buku Material) dan **pesanan berjalan** (SPK aktif yang belum di-BAST) — jadi yang dibeli hanya kekurangannya. Kebutuhan upah minggu itu tampil dalam OH.
4. Dari daftar belanja bersih itu, SPK mingguannya dapat dibuat langsung dari halaman ini.

### Simulasi Mingguan

Menu **Perencanaan → Simulasi Mingguan** adalah mesin simulasi satu proyek penuh mengikuti Kurva S resmi (syarat: rencana Kurva S sudah diimpor di halaman Kurva S):

1. **Isi realisasi mingguan**: pada tabel masukan, isi bobot realisasi tiap minggu per gedung — persen terhadap total proyek, satuan yang sama dengan bobot Kurva S (lihat Laporan Kemajuan). Minggu terakhir yang terisi dianggap minggu berjalan.
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

Menu **Pembangunan Gedung → Pembayaran Upah** mencatat pembayaran tenaga kerja: tanggal, gedung (dan pekerjaan bila tertaut), jenis tenaga, nama penerima/mandor, orang-hari, dan tarif per OH (kosongkan untuk memakai tarif master). Jumlah bayar = orang-hari × tarif, dan **diperiksa ke pagu sebelum disimpan** — pembayaran yang membuat total belanja melewati pagu ditolak.

Di halaman yang sama ada **Master Tarif Upah**: tarif resmi per jenis tenaga untuk sekolah ini (berasal dari dokumen perencana; dapat diubah, mis. mengisi tarif Kepala Tukang/Mandor yang di dokumen masih 0).

![Pembayaran upah](panduan_pbj/12-upah.png)

## 10. Master Bahan

Menu **Bahan & Laporan → Master Bahan & Upah**: daftar bahan sekolah aktif (kode, nama, satuan beli, harga RAB, dan kebutuhan per gedung warisan format lama). **Kotak pencarian di atas tabel menyaring langsung** — ketik sebagian kode atau nama, daftar berubah seketika tanpa memuat ulang halaman (kotak pencarian di topbar juga menuju ke sini). Bahan dapat ditambah, diubah, dan dihapus. Kode bahan unik per sekolah. Master ini terisi otomatis dari impor dokumen perencana dan menjadi acuan harga pertama pada rincian SPK.

**Hapus banyak sekaligus (bulk):** centang kotak di kiri baris (kotak di kepala tabel mencentang semua), lalu klik **Hapus Terpilih (n)** dan konfirmasi. Bahan yang sudah dipakai di rincian SPK tidak ikut terhapus — ia dipertahankan dan namanya disebutkan pada laporan hasil. Hal yang sama berlaku di tabel **Master Upah** di bawahnya: kotak saring menyaring jenis tenaga seketika, dan baris tercentang dapat dihapus sekaligus.

**Tombol Kosongkan:** **Kosongkan Master Bahan** / **Kosongkan Master Upah** menghapus seluruh isi master sekolah aktif dalam sekali klik (sesudah dialog konfirmasi). Semua penghapusan di halaman ini bersifat **hapus lunak** — data tidak musnah. Bahan yang sudah dipakai di rincian SPK tetap dipertahankan saat pengosongan bahan. Menambahkan kembali bahan berkode sama dengan yang pernah dihapus akan **menghidupkan kembali** baris lamanya.

**Fitur yang sama di semua modul.** Pencarian langsung, hapus terpilih (centang), dan tombol Kosongkan juga tersedia di: Daftar Gedung, BOQ Pekerjaan per gedung, Biaya Non-Gedung, Register SPK, Survei Harga dan Negosiasi (centang per bahan), Invoice, Buku Material, Riwayat Pembayaran Upah, Master Penyedia (di Pengaturan), Transaksi Manual Buku Kas, Penerimaan Dana, Bukti Setor & Pajak, Foto Progres (centang pada kartu foto), dan BA Revisi Pekerjaan. Penjaga tiap modul tetap berlaku: menghapus SPK membuat rinciannya belum bernomor, penyedia yang dipakai SPK dilewati, dan foto ikut menghapus berkasnya. Perbedaan yang disengaja: **Manajemen User** hanya mempunyai hapus terpilih (akun sendiri dan admin terakhir selalu dilewati; tidak ada Kosongkan), **Kosongkan Buku Kas** hanya menghapus transaksi yang dicatat manual (baris dari jurnal pembayaran, upah, dan bukti setor dihapus di modul sumbernya), sedangkan halaman **Kelola Sekolah** dan **Dokumen BAP/BAST** hanya mendapat kotak pencarian langsung.

![Master bahan](panduan_pbj/13-bahan.png)

### Tab Terpakai & Belum Terpakai, Gabungkan Duplikat, Sinkronkan Harga

Halaman Master Bahan terbagi dua tab: **Terpakai** (bahan yang dipakai analisa gedung yang didanai) dan **Belum Terpakai**. Tiga alat perapian tersedia di halaman ini:

- **Perbaiki & Gabungkan (duplikat):** impor dapat melahirkan bahan yang sama dengan nama persis sama tetapi kode/satuan/harga berbeda. Alat ini mendeteksi kelompok nama yang sama dan menggabungkannya ke satu bahan utama: seluruh referensi (komponen analisa, rincian SPK, dsb.) dialihkan ke bahan utama, lalu duplikatnya dihapus. Berlaku juga untuk **Master Upah**. Deteksinya hanya untuk **nama yang persis sama** — beda ejaan dianggap bahan berbeda.
- **Sinkronkan Harga ke Analisa:** menulis harga master bahan ke komponen-komponen analisa yang tertaut, agar harga AHSP hasil hitungan mengikuti harga master terbaru. Sesudah tombol ditekan, modal konfirmasi menampilkan **hanya tarif yang benar-benar berubah** (yang tetap dan yang dilewati karena satuan kemasan berbeda ditampilkan sebagai ringkasan), baru sesudah Anda menyetujui harga ditulis.
- **Konversi satuan manual:** untuk bahan yang satuan komponen analisanya berbeda dari satuan belinya (mis. semen kg → zak), faktor konversi manual yang diisi di halaman Analisa **mengalahkan** konversi bawaan aplikasi.

## 11. SPK & Rincian — termasuk Belum Bernomor

Menu **Belanja → SPK**: register Surat Perintah Kerja. Tambah SPK (nomor, tanggal, masa mulai–selesai, penyedia, paket, gedung tertaut bila dari rencana), ubah, atau hapus (menghapus SPK membuat rinciannya kembali **belum bernomor**, bukan ikut terhapus).

**Pembelian dari masyarakat.** Untuk belanja seperti pasir, batu, atau kayu yang dibeli dari warga, pilih penyedia khusus **"Masyarakat"**, lalu isi kolom **Nama Penerima** dengan nama orangnya. Nama itu **diwarisi ke semua dokumen turunan** SPK tersebut — cetak SPK, BAP, BAST, dan Invoice mencantumkan nama penerima itu sebagai pihak kedua, tanpa perlu membuat satu record penyedia per orang.

![Register SPK](panduan_pbj/14-spk.png)

### Rincian SPK

Klik nomor SPK untuk membuka rinciannya: tambah baris bahan, isi jumlah dan harga. **Harga acuan berjenjang**: aplikasi menyarankan harga dari negosiasi → penawaran survei → harga RAB, dan Anda tetap dapat mengetik harga lain. Total SPK dan PPN (11% dari dasar pengenaan) terhitung otomatis.

![Rincian SPK](panduan_pbj/15-spk-detail.png)

### Kebutuhan Tenaga Kerja

Panel **Kebutuhan Tenaga Kerja** pada halaman rincian SPK mencatat perkiraan tenaga kerja untuk paket itu: **jenis tenaga** (Pekerja/Tukang), **keahlian** (mis. Batu, Kayu, Besi, Cat), **kebutuhan OH** (orang-hari), dan **perkiraan jumlah orang** — supaya panitia tahu berapa tenaga yang harus disediakan sebelum pekerjaan dimulai.

- Bila SPK dibuat dari **Rencana Belanja**, panel ini **terisi otomatis** dari hitungan kebutuhan pekerja (Langkah 4): OH per jenis tenaga, dan jumlah orang = OH dibagi target hari kerja.
- Untuk SPK yang dibuat manual, isi lewat formulir **+ Tambah Baris** di panel yang sama; baris dapat dihapus per baris, dan total OH serta total orang terhitung di kaki tabel.
- Catatan ini bersifat **perkiraan untuk panitia** — bukan dokumen pembayaran. Pembayaran upah yang sebenarnya tetap dicatat di menu Pembayaran Upah (Bab 9).
- Bila ada barisnya, catatan ini **ikut tercetak pada SPK** (dan pada Cetak SPJ Lengkap).

### Belum Bernomor

Menu **Belanja → Belum Bernomor** mengumpulkan baris rincian yang belum masuk SPK mana pun (mis. hasil impor data lama atau SPK yang dihapus). Pasangkan baris ke SPK tujuan dari halaman ini, atau **hapus** baris yang memang tidak terpakai lagi lewat tombol **Hapus** pada barisnya (sesudah dihapus, baris tidak bisa dipasangkan lagi).

![Belum bernomor](panduan_pbj/16-belum-bernomor.png)

## 12. Survei Harga & Negosiasi

- **Survei Harga** (menu Belanja): catat harga penawaran beberapa toko untuk bahan yang sama; kolom Termurah dan Terpilih terhitung otomatis.
- **Negosiasi**: catat harga hasil negosiasi per bahan — harga ini menjadi **acuan pertama** saat mengisi rincian SPK.

![Survei harga](panduan_pbj/17-survei.png)

![Negosiasi](panduan_pbj/18-negosiasi.png)

## 13. BAP & BAST — dan mencetak dokumen

### Alur tercepat: Tuntaskan SPK satu panel

Sesudah barang SPK diterima, seluruh rantai SPJ dapat diselesaikan **tanpa berpindah menu**, dari panel **Tuntaskan SPK — Satu Panel** di halaman rincian SPK:

1. Isi **Tanggal terima barang** (otomatis terisi tanggal selesai SPK), lalu klik **Buat BAP + BAST + Invoice Sekaligus**. Aplikasi membuat dalam satu langkah: **BAP** berisi semua rincian SPK dengan kondisi "Cukup", **BAST** yang tertaut ke BAP itu, dan **Invoice** senilai total SPK — masing-masing bernomor urut berikutnya.
2. Panel berubah menampilkan ringkasan invoice (nilai, DPP, PPN, PPh 22, dan bersih yang diterima penyedia). Klik **Catat Bayar** di panel yang sama untuk mencatat pembayarannya — aturan pajak dan pemeriksaan pagu sama persis dengan Jurnal Pembayaran (Bab 14).
3. Klik **Cetak SPJ Lengkap (SPK + BAP + BAST + Invoice)** untuk mencetak keempat dokumen dalam **satu berkas**, masing-masing mulai di halaman baru.

Syaratnya: SPK **belum mempunyai BAP maupun Invoice**. SPK yang rantainya sudah berjalan sebagian (mis. BAP sudah dibuat lewat menu Dokumen) tidak memakai panel ini — lanjutkan lewat jalur manual di bawah. Panel juga menampilkan **ceklis kelengkapan** SPK (SPK/BAP/BAST/Invoice/bukti bayar dan penanda manual seperti kuitansi fisik), sehingga kekurangan berkas terlihat sekilas.

### Jalur manual lewat menu Dokumen

Menu **Belanja → BAP & BAST** (halaman Dokumen, satu baris per SPK): untuk setiap SPK, buat **BAP** (Berita Acara Pemeriksaan hasil pekerjaan), periksa/ubah isinya (termasuk rincian hasil pemeriksaan), lalu buat **BAST** (Berita Acara Serah Terima) dari BAP itu. Aturannya ketat: tombol Buat BAP hilang bila seluruh rincian sudah ter-BAP, dan **satu BAP hanya mempunyai satu BAST**. Dokumen yang dapat dicetak dari aplikasi (berkop sekolah, lengkap dengan terbilang dan blok tanda tangan):

- **SPK**, **BAP**, **BAST** — dari halaman ini (nomor surat format `422/<JENIS>/<urut>/<tahun>`),
- **Invoice** — dari halaman Invoice,
- **SPJ Lengkap** (SPK + BAP + BAST + Invoice dalam satu berkas) — dari panel Tuntaskan SPK di rincian SPK,
- **Papan Nama Proyek** — dari halaman Dokumen, untuk dipasang di lokasi pekerjaan,
- **Laporan** dan **LPJ** — dari halaman masing-masing.

![BAP dan BAST](panduan_pbj/19-dokumen.png)

## 14. Invoice & Jurnal Pembayaran

Menu **Belanja → Invoice**: catat tagihan penyedia (nomor urut, toko, tanggal, SPK/BAP/BAST terkait, nilai). Invoice dapat diubah, dihapus, dan **dicetak** (nomor `422/INV/<urut>/<tahun>`).

![Daftar invoice](panduan_pbj/20-invoice.png)

### Jurnal Pembayaran

Menu **Belanja → Jurnal Pembayaran** mencatat pembayaran invoice: pilih invoice, tanggal bayar, dan cara bayar. Aplikasi menghitung **PPh 22 sebesar 1,5%** secara otomatis, menandai invoice **Sudah Bayar**, dan memeriksa total terhadap pagu — jurnal yang menembus pagu ditolak. Baris jurnal dapat dihapus bila keliru catat (invoice kembali Belum Bayar).

Pembayaran invoice sebuah SPK juga dapat dicatat langsung dari panel **Tuntaskan SPK** di rincian SPK (Bab 13) — hasil dan aturannya sama, panel hanya menghemat perpindahan menu.

![Jurnal pembayaran](panduan_pbj/21-jurnal.png)

### Penerimaan Dana Bantuan

Menu **Keuangan → Penerimaan Dana** mencatat pencairan Bantuan Pemerintah yang diterima sekolah. Sesuai Juknis Revitalisasi SMP 2026, pencairan bertahap: **Tahap 1 sebesar 70%**, dan **Tahap 2 sebesar 30%** sesudah kemajuan pekerjaan mencapai minimal 50%. Catat tanggal, tahap, jumlah, dan kanal penerimaannya — penerimaan otomatis tampil sebagai kas masuk di Buku Kas, dan kuitansi penerimaannya dapat **dicetak** mengikuti format dokumen Juknis.

### Buku Kas (BKU)

Menu **Keuangan → Buku Kas** adalah pembukuan resmi menurut Juknis: **Buku Kas Umum (BKU), Buku Bank, dan Buku Pembantu Kas Tunai**. Isinya gabungan otomatis dari:

- transaksi manual yang dicatat di halaman ini (penerimaan dana, koreksi),
- pembayaran invoice (Jurnal Pembayaran),
- pembayaran upah, dan
- bukti setor pajak.

Buku ditutup per bulan (saldo awal, mutasi, saldo akhir) dan dapat **dicetak** mengikuti format dokumen Juknis. Mengosongkan Buku Kas hanya menghapus transaksi manualnya — baris dari modul lain dihapus di modul sumbernya masing-masing.

### Bukti Setor & Pajak

Menu **Keuangan → Bukti Setor & Pajak** mencatat penyetoran pajak yang dipungut/dipotong bendahara (**PPh 22, PPN, PPh 21, PPh 23, PPh Pasal 4 ayat (2)**) ke kas negara: tanggal setor, masa pajak, kode e-billing, dan **NTPN**. Register ini membandingkan setoran terhadap kewajiban pajak yang timbul dari transaksi, dan juga mencatat pengembalian sisa dana bantuan lewat **SIMPONI** — sesuai tata cara penyetoran pada Juknis.

## 15. Buku Material & Sisa Bahan

- **Buku Material** (menu Bahan & Laporan): catatan keluar–masuk bahan per tanggal (dari pembelian/SPK dan pemakaian), menjadi dasar stok.
- **Sisa Bahan**: perbandingan kebutuhan RAB dengan realisasi pemakaian per bahan; sisa negatif ditandai (juga muncul di panel Perlu Perhatian dashboard).

![Buku material](panduan_pbj/22-material.png)

![Sisa bahan](panduan_pbj/23-sisa-bahan.png)

## 16. Laporan & Ekspor Excel

Menu **Bahan & Laporan → Laporan**: ringkasan belanja sekolah, rekap per gedung, dan rekap per SPK. Tersedia:

- **Cetak laporan** ber-kop sekolah,
- **Ekspor Excel**: Rekap SPK, Buku Material, dan Sisa Bahan (berkas `.xlsx`).

![Laporan](panduan_pbj/24-laporan.png)

## 17. LPJ — sekolah dan per gedung

Menu **Bahan & Laporan → LPJ**: pertanggungjawaban per gedung (anggaran BOQ, realisasi belanja bahan + upah, persen fisik, sisa) dan rekap gabungan satu sekolah. LPJ dapat dibuka per gedung maupun **dicetak** untuk sekolah dan per gedung.

![LPJ sekolah](panduan_pbj/25-lpj.png)

## 18. Manajemen Pengguna

Menu **Pengaturan → Pengguna** (khusus admin): tambah pengguna, ubah nama/peran/kata sandi, dan hapus pengguna. Peran yang tersedia: **admin, ketua, bendahara, pelaksana, kepsek, operator**. Perlindungan bawaan: akun Anda sendiri tidak dapat dihapus, dan admin terakhir tidak dapat dihapus/diturunkan perannya. Pengguna yang terhapus tidak dapat masuk lagi.

![Manajemen pengguna](panduan_pbj/26-pengguna.png)

Tampilan dapat disesuaikan per pengguna: **tema gelap** (tombol bulan di topbar) dan **ukuran huruf** (tombol A−/A+); keduanya diingat oleh peramban.

![Tema gelap](panduan_pbj/29-tema-gelap.png)

### Hak Akses Menu per Peran

Menu **Pengaturan → Hak Akses Menu** (khusus admin) mengatur menu apa saja yang boleh dibuka tiap peran selain admin (admin selalu boleh semua). Bawaan aplikasi: semua peran boleh semua menu. Hilangkan centang pada menu yang tidak perlu diakses suatu peran, lalu simpan — menu itu hilang dari tampilan peran tersebut dan alamatnya pun ditolak bila diketik langsung. Tombol **Atur Ulang** mengembalikan akses semua peran ke bawaan. Gunakan ini, misalnya, agar bendahara tidak membuka menu perencanaan, atau pelaksana hanya melihat menu pembangunan dan belanja.

### Jejak Audit

Menu **Jejak Audit** mencatat siapa melakukan apa dan kapan: pengguna, modul, jenis tindakan (tambah/ubah/hapus/pengesahan/dsb.), dan keterangan ringkasnya — misalnya pembuatan SPK, pengesahan BA Revisi, perubahan hak akses menu, dan pemulihan data. Halaman ini bahan penelusuran bila ada angka yang berubah tanpa diketahui siapa yang mengubahnya.

## 19. Menghapus Data dengan Aman

- Setiap tombol **Hapus** membuka **modal konfirmasi** gaya TailAdmin — data tidak terhapus sebelum Anda menekan **Ya, Hapus**. Klik **Batal**, tekan **Esc**, atau klik di luar modal untuk membatalkan.
- Penghapusan bersifat **lunak (soft delete)**: baris ditandai terhapus dan disembunyikan dari semua daftar, laporan, dan hitungan, tetapi masih tersimpan di database dan dapat dipulihkan oleh admin database bila terlanjur.
- Menghapus **gedung** ikut menyembunyikan BOQ dan realisasinya; menghapus **SPK** mengembalikan rinciannya menjadi belum bernomor.

![Modal konfirmasi hapus](panduan_pbj/28-modal-hapus.png)

### Pemeliharaan Database (khusus admin)

Menu **Pemeliharaan Database** adalah ruang perawatan data aplikasi:

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

**Tarif Kepala Tukang / Mandor 0.**
Di dokumen perencana kedua tarif ini memang tersimpan 0. Isi lewat **Master Tarif Upah** di halaman Pembayaran Upah.

**Transaksi ditolak karena pagu.**
Total belanja (invoice terbayar + pembayaran upah) akan melewati pagu anggaran sekolah. Periksa sisa pagu di Rencana Belanja/Pengaturan; naikkan pagu di Pengaturan bila memang ada perubahan anggaran resmi.

**Tampilan masih versi lama sesudah pembaruan aplikasi.**
Tekan **Ctrl+F5** di peramban untuk memuat ulang tanpa cache.

**Lupa kata sandi.**
Admin dapat mengatur ulang sandi pengguna mana pun dari menu Pengaturan → Pengguna.

## 21. Lampiran — Ringkasan Hasil QA

Pengujian browser menyeluruh terakhir: **22 November 2026**, terhadap seluruh halaman dan alur utama:

- **34/34 pemeriksaan halaman & tampilan lulus** — login (termasuk penolakan sandi salah), dashboard, kelola sekolah, pengaturan, impor, daftar & detail gedung (4 tab), rencana belanja, pembayaran upah, master bahan, SPK & rincian, belum bernomor, survei, negosiasi, BAP & BAST, invoice, jurnal, buku material, sisa bahan, laporan, LPJ sekolah & per gedung, pengguna, profil, dan halaman cetak (SPK, laporan, LPJ).
- **11/11 pemeriksaan fungsi lulus** — ekspor Excel (rekap SPK, buku material, sisa bahan), unduh template kosong, ekspor template terisi, cetak invoice, pembuatan BAP, simpan + hapus pembayaran upah melalui modal, modal konfirmasi hapus, tema gelap, dan **0 dialog bawaan peramban** di seluruh pengujian.
- Uji impor-balik template: template terisi SMP Negeri 3 Ranto Baek SATAP diimpor kembali dengan selisih nilai **Rp 0**; jalur harga Harsat saja berselisih +1,7% (sisa selisih berasal dari inkonsistensi dokumen perencana sendiri).
- Catatan data (bukan kerusakan aplikasi): panel Perlu Perhatian menandai data warisan yang menunggu keputusan sekolah — 159 baris rincian belum bernomor, ketidaksamaan nilai Invoice No. 1 dengan SPK 1, dan dua sisa bahan negatif.

---

*Panduan ini disusun bersamaan dengan QA aplikasi pada 22 November 2026. Gambar memakai data contoh SMP Negeri 3 Ranto Baek SATAP; tampilan sekolah Anda sama, isinya mengikuti data sekolah Anda.*
