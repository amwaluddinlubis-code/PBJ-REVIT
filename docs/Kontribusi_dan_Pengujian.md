# Kontribusi dan Pengujian

- Terakhir diverifikasi: 26 November 2026
- Sumber: `AGENTS.md` (bagian Pengujian dan Penyelesaian), `phpunit.dist.xml`, `composer.json`

## 1. Standar sebelum mengirim perubahan

- Baca implementasi, pemanggil, helper, test, dan dokumentasi yang langsung
  terkait sebelum mengubah kode.
- Buat perubahan terkecil yang menyelesaikan kebutuhan; jangan merapikan area
  yang tidak terkait.
- Pertahankan API, route, nama field, bentuk data, dan perilaku lama kecuali
  perubahan itu memang diminta.
- Jangan tambah dependency tanpa kebutuhan dan alasan yang jelas.
- Jangan commit/push tanpa instruksi eksplisit.

## 2. Pemeriksaan teknis

- Lint PHP untuk view/controller yang disentuh: `php -l <berkas>`.
- Cek JavaScript yang disentuh: `node --check <berkas>`.
- Uji logika yang punya cakupan test: `composer test`.
- `git diff --check` sebelum selesai (deteksi whitespace/blank line error).

## 3. Uji browser (wajib untuk UI)

Uji perilaku utama di browser, bukan hanya keberhasilan render:
- buka/tutup komponen (modal, details, dropdown),
- submit/validasi form,
- pencarian/paginasi/urut tabel,
- state dinamis (kosong, error, disabled, loading).

Periksa screenshot desktop dan ponsel untuk halaman yang layout-nya berubah. Uji
perubahan CSS pada tema terang dan gelap bila selector terkait. Uji zoom A−/A+.

## 4. Daftar periksa UI

- Setiap kontrol punya label yang terlihat atau nama aksesibel; ikon aksi punya
  `aria-label`/tooltip.
- Alur keyboard: fokus terlihat, Tab berurutan, Escape menutup overlay yang
  sesuai, fokus kembali ke pemicu.
- Modal memakai semantik dialog, fokus terkelola, tidak mengirim form sebelum
  konfirmasi.
- Target sentuh ~44 px pada layar sempit.
- Teks tidak bertumpuk/terpotong tanpa cara melihatnya; hanya komponen yang
  semestinya yang menggulir.
- Tema terang dan gelap: state hover, fokus, disabled, error, kosong, loading
  tetap terbaca.

## 5. Tes otomatis yang ada

`tests/Unit/BaseControllerTest.php` (9 tes, 49 assertion) mencakup:
- `terbilang`, `rp`, `pajakPembelian` (PPh 22), ketentuan PBJ (ambang dokumen &
  penyedia), `faktorSatuan`.

`tests/Unit/DocsConsistencyTest.php` (3 tes) menjaga dokumentasi tetap selaras:
- setiap kunci katalog `AksesMenu` muncul sebagai tautan di sidebar,
- gambar yang dirujuk berkas markdown benar-benar ada,
- tautan lokal antar-berkas markdown tidak putus.

`tests/Database/` (23 tes, memakai database tes `pbj_test`) mencakup mesin uang:
- `HargaBerjalanTest` — harga berjalan/AHSP dan total BOQ,
- `NomorSpkTest` — penomoran SPK termasuk aturan nomor SPK terhapus,
- `KunciInvoiceTest` — kunci invoice yang punya jurnal pembayaran aktif,
- `HitungPilihanTest` — hitungan kebutuhan bersih Rencana Belanja (`hitungPilihan`).

`tests/_support/TestableBaseController.php` menyediakan controller uji yang
memakai `id` bahan 0 agar `faktorSatuan()` tidak menyentuh database.

Peringatan "No code coverage driver available" tidak menggagalkan tes; exit code 1
hanya karena peringatan itu.

## 6. Cakupan yang belum teruji otomatis

- Tes basis data hanya mencakup mesin uang di atas; alur controller/route/view
  lainnya tetap diuji via browser manual.
- Pemeriksaan otomatis tautan/gambar markdown dan kesesuaian katalog menu
  dengan sidebar sudah ada (`DocsConsistencyTest`, P3.7 rencana dokumentasi),
  tetapi tidak memeriksa kesesuaian isi teks dokumentasi dengan perilaku kode.

Rujuk `AGENTS.md` (bagian Pengujian dan Penyelesaian) untuk aturan lengkap.