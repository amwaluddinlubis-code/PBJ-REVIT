# Arsitektur Data — Aplikasi Analisa Perencana

- Terakhir diverifikasi: 26 November 2026 (migrasi 000033)
- Cakupan: tabel aplikasi, relasi, soft delete, isolasi sekolah
- Sumber aturan: `AGENTS.md` (bagian Data dan Database), `app/Database/Migrations/`

## 1. Ringkasan

Aplikasi memakai **43 tabel aplikasi** (44 termasuk tabel `migrations` yang dicatat
CodeIgniter). Semua perubahan skema dibuat sebagai **migration baru** di
`app/Database/Migrations/` (000001–000033); migration lama tidak diubah. Operasi
yang menyentuh beberapa tabel memakai **transaksi** dengan rollback saat gagal.

## 2. Tabel aplikasi (43)

| Kelompok | Tabel |
|---|---|
| Inti multi-sekolah | `sekolah` (hasil rename `identitas` di migrasi 000002), `users`, `aplikasi_pengaturan`, `akses_menu` |
| Gedung & BOQ | `gedung`, `pekerjaan`, `realisasi`, `revisi_pekerjaan`, `dokumen_spk` |
| Analisa AHSP | `analisa`, `analisa_komponen`, `ahsp_acuan`, `ahsp_acuan_komponen` |
| Bahan & konversi | `bahan`, `bahan_konversi`, `upah_master`, `upah_bayar` |
| SPK & belanja | `spk`, `spk_detail`, `spk_tenaga`, `survei_harga`, `negosiasi`, `biaya_lain`, `penyedia` |
| Dokumen SPJ | `bap`, `bap_detail`, `bast`, `invoice`, `pembayaran` |
| Material & stok | `buku_material`, `rencana_mingguan`, `rencana_mingguan_detail`, `simulasi_realisasi` |
| Kurva S | `kurva_minggu`, `kurva_rencana` |
| Keuangan & kas | `kas_transaksi` (termasuk penerimaan dana), `bukti_setor`, `tutup_buku`, `tutup_buku_baris` |
| Audit & impor | `audit_events`, `impor_log`, `foto_progres` |
| Laporan | `laporan_template` |

> Daftar lengkap terverifikasi dari berkas migrasi (44 baris termasuk
> `migrations` pada `SHOW TABLES`). Penerimaan dana tidak mempunyai tabel
> sendiri — modul Penerimaan Dana mencatat ke `kas_transaksi`. Selalu
> jalankan `php spark migrate` sampai 000033.

## 3. Isolasi sekolah (wajib)

- **Semua data milik sekolah** membawa `sekolah_id`.
- Sekolah aktif diambil dari `sid()` (helper), **bukan** dari form biasa.
- Baca, ubah, hapus wajib menyertakan `sekolah_id` pada klausa WHERE.
- Hanya alur tujuan-impor yang mendukung pilihan sekolah boleh menerima
  `sekolah_id` dari request, dan harus memvalidasinya.
- Data turunan dijamin kepemilikannya melalui induknya: pekerjaan melalui gedung,
  invoice melalui SPK, komponen analisa melalui analisa, dll.

## 4. Soft delete

- Pola: kolom `is_deleted` (tinyint) dan `deleted_at` (datetime).
- Penghapusan bisnis memakai `hapusLunak()`; daftar aktif selalu mengecualikan
  baris terhapus (`is_deleted = 0`).
- Hard-delete hanya untuk pembersihan permanen dari halaman **Sampah**
  (khusus admin) atau migration.
- Nomor SPK berikutnya dihitung dari `MAX(nomor)` **seluruh baris termasuk yang
  terhapus lunak**, karena kunci unik `spk_sekolah_nomor (sekolah_id, nomor)`
  tetap dipegang baris terhapus.

## 5. Relasi id vs teks (migrasi 000018–000019)

Relasi baru wajib memakai kolom id + foreign key bila memungkinkan; jangan menambah
relasi berbasis teks/kode/nama baru.

- `pekerjaan.analisa_id` → `analisa.id` (join baca mengutamakan id; `kode_analisa`
  hanya fallback untuk baris yang belum terisi id-nya dan tetap disimpan untuk
  tampilan/impor).
- `analisa_komponen.upah_id` → `upah_master.id` (tarif master dibaca lewat id
  lebih dulu, nama hanya fallback).
- `spk_detail.gedung_id`, `buku_material.gedung_id` → `gedung.id` (opsional, NULL
  = gudang bersama / SPK tunggal).
- `bast.bap_id` → `bap.id` (1 BAP = 1 BAST).
- `bahan_konversi` unik per `(bahan_id, satuan_komponen)`.

Semua jalur tulis (impor perencana/template, form pekerjaan, sahkan revisi, tambah
komponen, gabung/rename jenis upah) wajib mengisi id ini mengikuti kode/nama.

Menghapus bahan wajib memutus tautan komponennya (`bahan_id` → NULL) agar
komponen muncul di panel Belum Tertaut, bukan menunjuk bahan nonaktif.

## 6. Kolom warisan yang dipertahankan

- `bahan.qty_rkb/qty_adm/qty_toilet` tidak lagi tampil di form bahan (nilai lama
  dipertahankan saat ubah bila tidak terkirim).
- Kolom teks `kode_analisa`, `jenis` (upah) tetap disimpan untuk tampilan/impor
  meskipun relasi id sudah ada.

## 7. Aturan unik penting

- `spk`: kunci unik `(sekolah_id, nomor)`.
- `upah_master`: kunci unik `(sekolah_id, jenis)`.
- `bahan`: kode unik per sekolah.
- `bahan_konversi`: unik per `(bahan_id, satuan_komponen)`.
- `negosiasi`: unik per `(bahan, toko)`; `survei_harga`: unik per `(bahan, toko)`.

Operasi massal (gabung duplikat bahan) wajib memakai pola `Bahan::alihkanUnik()`
agar tidak menabrak kunci unik.

## 8. Catatan upgrade

- Selalu jalankan `php spark migrate` sampai 000033.
- Backup database sebelum migrasi besar (Pemeliharaan Database → Backup SQL).
- Migration bersifat idempoten untuk kolom yang ditambahkan (menambahkan bila belum ada).

Rujuk `AGENTS.md` untuk aturan lengkap Data dan Database.