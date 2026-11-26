# Token Desain (Fase 0 — 26 November 2026)

Lapisan token bersama untuk `public/assets/app.css`. Tujuan: satu tempat untuk
mengubah warna, tipografi, bentuk, elevasi, dan gerak — bukan menambah aturan
penimpa baru di akhir berkas.

## Kontrak

- **Aturan baru WAJIB memakai `--ui-*`.**
- Nama lama tetap berlaku sebagai **alias**, jadi aturan lama tidak perlu diubah:
  - `--ta-biru`, `--ta-teks`, `--ta-garis`, `--ta-latar`, `--ta-radius-*`,
    `--ta-shadow-*` → menunjuk ke token `--ui-*` yang setara.
  - `--ina-*` (palet mentah IDDS) dan `--fm-*` (form modal) tidak berubah.
- **Tema gelap hanya menukar token** di blok `html.gelap` (`--ui-*`), bukan
  menambah selektor `html.gelap .komponen`. Aturan komponen gelap yang masih ada
  (119 selektor) adalah warisan yang dihapus bertahap pada Fase 3.
- Jangan mendefinisikan ulang token di tengah/akhir berkas. Semua definisi
  berada di blok `:root` "Token desain" di awal `app.css` dan di `html.gelap`.

## Daftar token

| Kelompok | Token | Nilai sekarang |
|---|---|---|
| Permukaan | `--ui-surface` / `--ui-surface-glass` / `--ui-surface-raised` | `#fff` / `rgba(255,255,255,.92)` / `#fff` |
| | `--ui-surface-soft` / `--ui-surface-input` / `--ui-surface-hover` / `--ui-surface-total` | `#f7f9fc` / `#fbfcfe` / `#f8fbff` / `#f3f7fc` |
| | `--ui-surface-sunken` / `--ui-surface-tint` | `#f9fafb` / `#eef5f7` |
| Garis | `--ui-border` / `--ui-border-strong` / `--ui-hairline` | `rgba(15,35,70,.09)` / `#d3dce8` / `#edf1f5` |
| | `--ui-stroke` / `--ui-stroke-strong` | `#e5e5e5` / `#a3a3a3` |
| Teks | `--ui-text` / `--ui-text-2` / `--ui-text-3` / `--ui-label` | `#1f1f1f` / `#525252` / `#737373` / `#526175` |
| Aksen | `--ui-accent` / `--ui-accent-500` / `--ui-accent-soft` / `--ui-accent-hover` / `--ui-accent-ring` | `#0049b8` / `#0968f6` / `#eff5fd` / `#003a94` / `#dbeafe` |
| Status | `--ui-danger` / `--ui-danger-hover` | `#800000` / `#660000` |
| Tipografi | `--ui-font` | `"Inter", "Segoe UI", system-ui, sans-serif` |
| | `--ui-text-2xs` / `-xs` / `-sm` / `-base` / `-lg` / `-xl` / `-2xl` / `-3xl` | `10px` / `11.5px` / `13px` / `14.5px` / `17px` / `20px` / `24px` / `28px` |
| | `--ui-text-display` | `clamp(36px, 5vw, 70px)` (judul layar masuk) |
| Bentuk | `--ui-radius-xs` / `-sm` / `-md` / `-lg` / `-xl` / `-pill` | `8px` / `12px` / `14px` / `18px` / `20px` / `999px` |
| | `--ui-radius` (alias lama) | `var(--ui-radius-lg)` |
| Elevasi | `--ui-shadow-sm` / `-md` / `-soft` / `-hover` / `-overlay` | lihat `app.css` |
| Gerak | `--ui-durasi-cepat` / `-durasi` / `-sedang` | `.15s` / `.18s` / `.2s` |
| | `--ui-ease` / `--ui-ease-keluar` | `ease` / `cubic-bezier(.22,1,.36,1)` |

## Fase 0 — apa yang dikerjakan

1. Blok token `:root` tunggal di awal `app.css`; tiga blok token tersebar
   (dua `:root --ui-*` yang saling menimpa dan satu `:root` radius/bayangan)
   dihapus dan nilainya dipusatkan.
2. `html.gelap` menukar token `--ui-*`, bukan `--ta-*`. Alias mengikuti otomatis.
3. Deklarasi pemenang pada komponen inti (body, tombol, isian, panel, tabel,
   KPI, judul halaman) memakai token. Nilai token = nilai efektif sebelumnya,
   jadi **tampilan tidak berubah**.
4. `--fm-latar` memakai `--ui-surface-raised`; penimpa `html.gelap .fm-kartu`
   dihapus karena token sudah membawa nilai gelapnya.

## Verifikasi Fase 0

Harness `uji-css.html` (sementara, dihapus setelah uji) merender 48 selektor
komponen dan membandingkan 26 properti computed style sebelum vs sesudah:
**1.222 nilai identik**, satu-satunya selisih adalah `height` `.samping`
(`100vh` mengikuti tinggi viewport saat pengukuran, bukan perubahan CSS).
Token gelap diverifikasi langsung: `--ta-teks` → `#e7edf5`,
`--ta-garis` → `#22334f`, panel `rgba(15,26,46,.94)`.

## Fase 1 — skala huruf (26 Nov 2026)

Sebelumnya 38 ukuran berbeda dipakai langsung sebagai literal (8.8 / 9 / 9.2 /
9.5 / 9.6 / 10 / 10.4 / 10.5 / 10.8 / 11 / 11.2 / 11.5 / 11.6 / 12 / 12.4 /
12.5 / 13 / 13.5 / 13.6 / 14 / 14.4 / 14.5 / 15 / 15.2 / 16 / 17 / 18 / 19 /
19.2 / 21 / 22.4 / 26.4 / 27 / 30 px + `clamp`). Sekarang semua menunjuk ke
7 langkah + satu ukuran display:

| Langkah | Nilai | Dipakai untuk |
|---|---|---|
| `--ui-text-2xs` | 10px | lencana, hitungan sidebar, kepala tabel (huruf besar), teks bantu |
| `--ui-text-xs` | 11.5px | label isian, keterangan, kontrol padat (paginasi, font A−/A+) |
| `--ui-text-sm` | 13px | **UI kerja**: isi tabel, tombol, isian, pesan, tab, breadcrumb |
| `--ui-text-base` | 14.5px | **teks isi**: paragraf, judul panel, summary, isi modal |
| `--ui-text-lg` | 17px | sub-judul, nilai KPI ringkas |
| `--ui-text-xl` | 20px | judul halaman (`.atas`), nilai KPI, h1 panduan |
| `--ui-text-2xl` | 24px | judul hero dashboard, judul form masuk |
| `--ui-text-3xl` | 28px | judul layar masuk (login) |
| `--ui-text-display` | `clamp(36px,5vw,70px)` | h1 layar masuk |

Aturan pemakaian: pilih langkah menurut **peran**, bukan menurut ukuran lama.
Jangan menulis `font-size: 12px` baru; kalau suatu elemen butuh ukuran berbeda,
ubah nilainya lewat token, bukan menambah literal.

Hasil akhir: **tidak ada lagi `font-size` literal di `app.css`** selain
`font-size: .92em` pada kode dalam panduan (sengaja relatif). Ukuran display
layar masuk memakai `--ui-text-display`; breakpoint 860/520px hanya menukar
variabel lokal `--masuk-judul` di `.login-v2-intro h1`, bukan menulis ukuran
baru.

Perubahan perilaku yang disengaja: teks isi 13.5px → **14.5px**; isi tabel
11.2px → 13px; tombol & isian 11.2px → 13px; kepala tabel 9px → 10px; judul
halaman 19px → 20px; judul hero 21px → 24px. Konsekuensinya baris tabel lebih
tinggi dan beberapa kolom teks membungkus lebih awal — itu memang tujuan
kenaikan skala, bukan regresi.

Dua penyesuaian tata letak yang menyertai kenaikan skala:

- Jarak ikon–teks menu sidebar 10px → **8px** (`.samping nav a` dan
  `.samping nav .parent`). Pada 13px, label terpanjang "Pembangunan Gedung"
  membungkus dua baris bila jaraknya 10px.
- Sel angka (`td.angka`, `th.angka`) **tidak** dipaksa `nowrap`; nilai uang
  panjang boleh membungkus di dalam selnya sendiri alih-alih melebarkan tabel
  dan terpotong `overflow: clip` panel.

## Verifikasi Fase 1

Harness `uji-fase1.html` (sementara) merender 54 selektor komponen dan
membandingkan 13 properti computed style sebelum vs sesudah:

- Hanya `fontSize` (51 selektor), `lineHeight` (51), dan `letterSpacing` (15)
  yang berubah — `letterSpacing` ikut karena satuannya `em`.
- **Nol perubahan** pada `color`, `backgroundColor`, `borderRadius`,
  `padding`, `minHeight`, `fontWeight`, `fontFamily`, `textAlign`,
  `textTransform`: kenaikan skala tidak menggeser warna, bentuk, atau jarak.
- Diukur langsung pada aplikasi (1056px): body 14.5px, judul halaman 20px,
  judul hero 24px, isi tabel 13px, kepala tabel 10px, tombol/isian 13px,
  label 11.5px, sidebar 13px, lencana 10px.
- 390px (iframe): halaman tidak melebar (`scrollWidth` 375), sidebar
  terlipat, hero/KPI/tabel tetap terbaca.
- Layar masuk diukur pada 1056 / 900 / 700 / 520 / 375px: h1 52.8 / 45 / 42 /
  35 / 35px, h2 28 / 28 / 28 / 24 / 24px, lead 17 → 14.5px di bawah 520px,
  label & isian 13px, catatan 11.5px — sama seperti sebelum bertoken, tanpa
  luber horizontal di semua lebar.
- Tema gelap diperiksa ulang: warna token gelap utuh, ukuran baru berlaku.

## Sisa pekerjaan (fase berikutnya)

- **Fase 2 — sidebar, topbar, gerak, ikon.** Sidebar masih gradasi navy→teal;
  tombol aplikasi masih radius 12px (aturan pil di `AGENTS.md` hanya berlaku
  pada lapisan dasar, `body:not(.login) button` menimpanya), glyph unicode
  `⌕ ✓ × › A−` masih bercampur ikon SVG.
- **Fase 3 — form & tema gelap.** Hapus 119 selektor `html.gelap .komponen`
  dengan melengkapi token permukaan/garsi versi gelap; `.tombol-buka-form`
  (10px) dan `.biaya-modal-*` (sisa komponen modal lama) dibersihkan.
- ~~**Tabel lebar yang terpotong.**~~ **Selesai 26 Nov 2026 (keputusan AMWAL):**
  tabel yang lebih lebar dari panelnya kini dibungkus wadah gulir mendatar
  `.bungkus-tabel` otomatis oleh `paginasi.js` — kolom paling kanan dapat
  dicapai (Master Bahan 1008px di panel 717px, Kelola Gedung 888px). Kotak
  cari dan paginasi tetap di luar wadah; konsekuensi yang diterima: kepala
  tabel tempel tidak berlaku pada tabel yang dibungkus (tabel yang muat tetap
  memakai `kepala-tempel`, mis. tabel Buku Material). Di cetakan wadah
  digulir dibuka (`overflow: visible`) agar tabel tidak terpotong.
- **Beberapa nilai huruf di luar `app.css`.** Cetakan (`app/Views/cetak/*`,
  `lpj/cetak_*`) punya skala dokumen sendiri (Times New Roman, mm/px cetak) dan
  sengaja tidak memakai token layar; lencana inline di view masih memakai
  `font-size:11px` literal.
