# Changelog — Aplikasi Analisa Perencana

Format: rilis per migrasi. Rincian lengkap ada di `git log` dan `AGENTS.md`.

## 24 November 2026 — migrasi 000028 (Mesin Laporan / Template Laporan)

- Template .xlsx berformat bebas dengan penanda `{{KUNCI}}`, baris berulang
  `RINCIAN_/TENAGA_/KERJA_/REKAP_`, render dibatalkan bila ada penanda tak terisi.
- Menu Laporan → Template Laporan; render dari Detail SPK & halaman Gedung.
- Dokumentasi: `docs/Panduan_Penggunaan.md`, `AGENTS.md`.

## 23 November 2026 — migrasi 000023–000027

- 000023: penyedia "Masyarakat" + `spk.penerima_manual`; tampilan efektif lewat
  `COALESCE(NULLIF(TRIM(...)))`.
- 000024: `bast.bap_id` (1 BAP = 1 BAST); form BAP/BAST jadi modal; tombol cetak
  ikon XS berwarna.
- 000025: `spk_tenaga` (kebutuhan tenaga kerja SPK).
- 000026: `simulasi_realisasi` (Simulasi Mingguan).
- 000027: SPK gabungan (`spk_detail.gedung_id`, `buku_material.gedung_id`).

## 22 November 2026 — migrasi 000012–000022

- 000012: gedung didanai.
- 000013: RAB berjalan (BA Revisi).
- 000014: `bahan_konversi`.
- 000015: audit & akun (`audit_events`, `aktif`, `harus_ganti_sandi`).
- 000016: Rencana Mingguan.
- 000017: aplikasi pengaturan.
- 000018: relasi id analisa/upah.
- 000019: unik bahan_konversi.
- 000020: Kurva S (`kurva_minggu`, `kurva_rencana`).
- 000021: Akses Menu (`akses_menu`).
- 000022: Acuan AHSP Nasional (`ahsp_acuan`, `ahsp_acuan_komponen`).

## 21 November 2026 — migrasi 000001–000011

- 000001: tabel inti PBJ (users, identitas, bahan, penyedia, spk, ...).
- 000002: multi-sekolah.
- 000003: modul perencana (gedung, pekerjaan, analisa, upah, impor_log).
- 000004: kolom soft delete.
- 000005: harga satuan analisa.
- 000006: biaya non-gedung.
- 000007: pajak biaya lain.
- 000008: pembukuan kas.
- 000009: foto progres.
- 000010: dokumen SPK.
- 000011: revisi pekerjaan.

## Cara membaca

Setelah `git pull`, jalankan `php spark migrate` (migrasi terakhir 000028) lalu
cek `docs/Panduan_Instalasi_Upgrade.md` §3.