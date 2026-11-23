# Matriks Hak Akses — Aplikasi Analisa Perencana

- Terakhir diverifikasi: 24 November 2026
- Sumber: `app/Libraries/AksesMenu.php`, `app/Filters/AuthFilter.php`, `app/Controllers/*`
  (`penjagaPeran`), `app/Views/layout/main.php`

## 1. Peran

Peran yang dikenal aplikasi: **admin, ketua, bendahara, pelaksana, kepsek, operator**.
Admin selalu lolos semua gerbang; peran lain dibatasi per menu (Akses Menu) dan per
aksi sensitif (penjagaPeran).

## 2. Akses menu (Akses Menu per Peran)

Menu yang dapat diatur per peran (selain admin): katalog `AksesMenu::katalog()`.
Bawaan aplikasi: semua peran boleh semua menu. Admin dapat menghilangkan centang
per peran di **Pengaturan → Akses Menu**; menu yang tidak diizinkan hilang dari
tampilan dan alamatnya ditolak bila diketik langsung. Tombol **Atur Ulang**
mengembalikan bawaan.

Menu yang **tidak** masuk katalog dan tidak dapat dibatasi di sini:
Dashboard, Profil (termasuk ganti sandi wajib), ganti sekolah, keluar, serta menu
khusus admin yang sudah dijaga `penjagaPeran` (Manajemen User, Pemeliharaan
Database, Jejak Audit).

Grup dan kunci menu (kunci = segmen pertama URL):

| Grup | Kunci |
|---|---|
| Perencanaan | impor, analisa, acuan, validasi-harga, bersih, rencana-belanja, rencana-mingguan, simulasi, biaya-non-gedung |
| Pembangunan Gedung | gedung, foto-progres, kurva-s, laporan-kemajuan, revisi-pekerjaan |
| Belanja | spk, belum-bernomor, survei, negosiasi, dokumen |
| Material | bahan, material, sisa-bahan |
| Keuangan | penerimaan-dana, invoice, buku-kas, bukti-setor, upah |
| Laporan | lpj, laporan, template-laporan |
| Pengaturan | pengaturan, sekolah |

## 3. Gerbang aksi sensitif (penjagaPeran)

Aksi yang menyentuh uang dan persetujuan wajib dibatasi peran di server (bukan
hanya disembunyikan di tampilan). Daftar dari kode:

| Aksi | Peran yang diizinkan |
|---|---|
| Pengesahan BA Revisi Pekerjaan | ketua, kepsek, admin |
| Jurnal pembayaran, penerimaan dana, bukti setor, pembayaran upah, transaksi manual Buku Kas, bayar cepat dari panel Tuntaskan | bendahara, admin |
| Impor dokumen, impor Kurva S, sinkron harga ke analisa, Rapikan ke 2 Jenis, samakan harga di Validasi Harga | operator, admin |
| Mengosongkan master/modul (Kosongkan Bahan/Upah/Buku Kas/BOQ gedung, dll.) | admin |
| Akses Menu, Manajemen User, Pemeliharaan Database, Jejak Audit | admin |

Admin selalu lolos semua gerbang. Bila tombol aksi tidak muncul padahal peran Anda
seharusnya berhak, periksa pembagian peran di halaman Manajemen User.

## 4. Status akun

- Akun memiliki status `aktif` (0/1) dan `harus_ganti_sandi` (0/1).
- Akun bawaan awal (`admin/admin123`) dan akun yang baru dibuat admin memiliki
  `harus_ganti_sandi = 1`: sesudah masuk, aplikasi hanya mengizinkan membuka
  **Profil** sampai sandi diganti; menu lain ditolak (`AuthFilter`).
- Akun nonaktif tidak dapat masuk.
- Admin dapat mengatur ulang sandi pengguna; pengguna itu akan diminta mengganti
  sandi lagi saat masuk berikutnya.

## 5. Invoice terkunci

Invoice yang sudah memiliki jurnal pembayaran aktif **terkunci**: tidak dapat
diubah, dihapus, atau ikut dikosongkan. Koreksi dilakukan dengan menghapus dulu
jurnal pembayarannya (tercatat di Jejak Audit). Aturan ini menjaga buku kas dan
laporan tidak berubah tanpa jejak.

## 6. Jejak audit

Aksi yang menyentuh uang dan persetujuan wajib dicatat dengan
`catatAudit(entitas, id, aksi, keterangan)` pada tabel `audit_events`. Halaman
**Jejak Audit** menampilkan siapa melakukan apa dan kapan.

Rujuk `AGENTS.md` untuk aturan gerbang peran yang lebih rinci.