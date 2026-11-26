# Changelog — Aplikasi Analisa Perencana

Format: rilis per migrasi. Rincian lengkap ada di `git log` dan `AGENTS.md`.

## 26 November 2026 — migrasi 000034 (Generate SPK dari Biaya Non-Gedung)

- 000034: tautan `spk_detail.biaya_lain_id` (+ indeks) ke baris
  `biaya_lain` sumbernya.
- Halaman Biaya Non-Gedung: tombol **Buat SPK dari Terpilih**
  (`biaya-non-gedung/buat-spk`) membuat satu SPK ber-gedung NULL dari
  baris terpilih (rincian manual: uraian/satuan, qty = volume × periode,
  harga = harga satuan, penanda PPN rincian 0); baris yang sudah tertaut
  ke SPK aktif dilewati (anti-dobel), menampilkan lencana "SPK {nomor}",
  dan kotak centangnya nonaktif. SPK Batal/terhapus membebaskan barisnya.
- `komponenBulk` (`bulk.js`): `kirim()` menerima formulir kedua opsional;
  `pilihSemua` melewati kotak centang nonaktif.

## 26 November 2026 — migrasi 000029–000033

- 000029: Acuan AHSP per sekolah (`ahsp_acuan.sekolah_id`, unik
  `sekolah_id` + `kode_kita`).
- 000030: `sekolah.bulan_bekerja` (cache Bulan Terbuka).
- 000031: Tutup Buku bulanan (`tutup_buku` + `tutup_buku_baris`); Bulan
  Terbuka = bulan terkecil yang belum ditutup.
- 000032: Fase 2 keuangan — kolom rekonsiliasi `tutup_buku`,
  `kas_transaksi.koreksi_ref` (jurnal balik), `penyedia.no_rekening` /
  `nama_bank`; snapshot beku + nomor bukti BKU, SPJ Bulanan, Buku Pajak,
  saldo per kanal tidak boleh minus.
- 000033: kanal pembayaran (`pembayaran.kanal`, bawaan tunai — hanya
  pembayaran non-tunai masuk Buku Bank); baris pembayaran bruto + baris
  pungut pajak terpisah.
- Di luar migrasi: panel gabungan BAP, BAST & Invoice di Detail SPK
  (tombol Buat Invoice membuka modal), isian rupiah rata kanan
  (`data-uang`), Rekap Pemakaian Non-Gedung, tab empat buku gaya BOQ
  sejajar baris aksi, lapisan token desain `--ui-*` + skala huruf
  7 langkah (`docs/Desain_Token.md`), pembungkusan gulir tabel otomatis
  oleh `paginasi.js`.

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