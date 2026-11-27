<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('tgl')) {
    /**
     * Format tanggal untuk DITAMPILKAN ke pengguna: dd/mm/yyyy.
     * Penyimpanan di database tetap format baku (yyyy-mm-dd) — fungsi ini
     * hanya untuk lapisan tampil. Nilai kosong/tidak valid → $kosong.
     */
    function tgl($iso, string $kosong = '—'): string
    {
        if ($iso === null || $iso === '' || $iso === '0000-00-00') {
            return $kosong;
        }
        $t = strtotime((string) $iso);
        return $t === false ? (string) $iso : date('d/m/Y', $t);
    }
}

if (! function_exists('tglWaktu')) {
    /** Seperti tgl(), tetapi untuk datetime: dd/mm/yyyy HH:ii. */
    function tglWaktu($iso, string $kosong = '—'): string
    {
        if ($iso === null || $iso === '' || $iso === '0000-00-00' || $iso === '0000-00-00 00:00:00') {
            return $kosong;
        }
        $t = strtotime((string) $iso);
        return $t === false ? (string) $iso : date('d/m/Y H:i', $t);
    }
}

if (! function_exists('namaBulanIndo')) {
    /** Nama bulan bahasa Indonesia dari nomor bulan 1–12. */
    function namaBulanIndo(int $b): string
    {
        return [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$b] ?? '';
    }
}

if (! function_exists('bulanTahun')) {
    /** "Mei 2026" dari string 'Y-m' atau tanggal ISO — nama bulan selalu bahasa Indonesia. */
    function bulanTahun($ym): string
    {
        $t = strtotime(substr((string) $ym, 0, 7) . '-01');
        return $t === false ? (string) $ym : namaBulanIndo((int) date('n', $t)) . ' ' . date('Y', $t);
    }
}

if (! function_exists('tglPanjang')) {
    /** Tanggal panjang bahasa Indonesia: "26 November 2026". */
    function tglPanjang($iso): string
    {
        $t = strtotime((string) $iso);
        return $t === false ? (string) $iso : (int) date('j', $t) . ' ' . namaBulanIndo((int) date('n', $t)) . ' ' . date('Y', $t);
    }
}

if (! function_exists('namaHariIndo')) {
    /** Nama hari bahasa Indonesia dari tanggal ISO: "Kamis". */
    function namaHariIndo($iso): string
    {
        $t = strtotime((string) $iso);
        if ($t === false) {
            return '';
        }
        return ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'][date('l', $t)] ?? '';
    }
}

if (! function_exists('ikonSvg')) {
    /**
     * Ikon SVG bersama (peta path 24x24) untuk seluruh tampilan —
     * padanan "komponen ikon" pola TALL: satu sumber, dipakai layout
     * dan view mana pun lewat ikonSvg('nama'). Nama tak dikenal → svg
     * kosong. Peta dipindah dari layout/main.php (2026-11-24) tanpa
     * mengubah satu path pun.
     */
    function ikonSvg(string $kunci): string
    {
        static $ikon = null;
        if ($ikon === null) {
    $ikon = [
      'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
      'impor'  => '<path d="M12 16V4m0 0l-4 4m4-4l4 4"/><path d="M4 16v3a1 1 0 001 1h14a1 1 0 001-1v-3"/>',
      'gedung' => '<path d="M4 21V8l8-5 8 5v13"/><path d="M2 21h20M9 21v-6h6v6"/>',
      'kotak'  => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
      'spk'    => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M10 12h5M10 16h5"/>',
      'survei' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
      'nego'   => '<path d="M8 7h13M8 12h13M8 17h13"/><circle cx="4" cy="7" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="17" r="1"/>',
      'dok'    => '<path d="M9 12h6M9 16h4"/><path d="M14 3H7v18h10V7l-3-4z"/><path d="M14 3v4h3"/>',
      'inv'    => '<path d="M6 3h12v18l-2-1.5L14 21l-2-1.5L10 21l-2-1.5L6 21V3z"/><path d="M9 8h6M9 12h6"/>',
      'jurnal' => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20V3H6.5A2.5 2.5 0 004 5.5v14z"/><path d="M4 19.5A2.5 2.5 0 006.5 22H20v-5"/>',
      'upah'   => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20c.8-3.2 3.6-5 7-5s6.2 1.8 7 5"/>',
      'mat'    => '<path d="M4 7c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3z"/><path d="M4 7v10c0 1.7 3.6 3 8 3s8-1.3 8-3V7"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
      'sisa'   => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v3M12 18.2v3M2.8 12h3M18.2 12h3M5.5 5.5l2.1 2.1M16.4 16.4l2.1 2.1M18.5 5.5l-2.1 2.1M7.6 16.4l-2.1 2.1"/>',
      'rencana'=> '<circle cx="9" cy="20" r="1.6"/><circle cx="17" cy="20" r="1.6"/><path d="M3 3h2l2.4 12h10.4l2.2-9H6"/>',
      'lpj'    => '<path d="M4 19V5m0 14h16"/><path d="M7 15c3-1 4-6 7-7 2-.7 3 1 5 0"/>',
      'gear'   => '<circle cx="12" cy="12" r="3.2"/><path d="M19.4 15a1.7 1.7 0 00.34 1.87l.06.06a2 2 0 11-2.83 2.83l-.06-.06A1.7 1.7 0 0015 19.4a1.7 1.7 0 00-1 1.55V21a2 2 0 11-4 0v-.09A1.7 1.7 0 009 19.4a1.7 1.7 0 00-1.87.34l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.7 1.7 0 004.6 15a1.7 1.7 0 00-1.55-1H3a2 2 0 110-4h.09A1.7 1.7 0 004.6 9a1.7 1.7 0 001-1.87l-.06-.06a2 2 0 112.83-2.83l.06.06A1.7 1.7 0 009 4.6a1.7 1.7 0 001-1.55V3a2 2 0 114 0v.09c0 .68.4 1.3 1 1.51.61.22 1.3.1 1.9-.34l.06-.06a2 2 0 112.83 2.83l-.06.06a1.7 1.7 0 00-.34 1.9V9c.24.6.86 1 1.51 1H21a2 2 0 110 4h-.09c-.65 0-1.27.4-1.51 1z" stroke-linejoin="round"/>',
      'sekolah'=> '<path d="M3 21h18M5 21V9l7-5 7 5v12"/><path d="M9 21v-5h6v5M12 4V2.5"/>',
      'user'   => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
      'users'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
      'keluar' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
      'bulan'  => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
      'matahari'=> '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
      'panah'  => '<path d="m6 9 6 6 6-6"/>',
      'buku'   => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V4a2 2 0 0 0-2-2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>',
      'pensil' => '<path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>',
    ];
        }
        return '<svg viewBox="0 0 24 24">' . ($ikon[$kunci] ?? '') . '</svg>';
    }
}

if (! function_exists('rp')) {
    /**
     * Format uang untuk DITAMPILKAN: 'Rp 1.234.567' (ribuan '.', tanpa
     * desimal). Satu-satunya sumber format rupiah tampilan — menggantikan
     * closure $rp lokal yang dulu disalin di ±49 berkas view (2026-11-24).
     * View yang butuh varian lain (null → '—', atau tanpa awalan 'Rp')
     * tetap mendefinisikan formatter lokalnya sendiri.
     */
    function rp($n): string
    {
        return 'Rp ' . number_format((float) $n, 0, ',', '.');
    }
}

if (! function_exists('sel_tabel_html')) {
    /**
     * Render satu sel untuk tabel_bantu(): string -> diescape; array
     * berkunci 'html' -> mentah; 'teks' -> diescape, dengan opsi 'angka'
     * (rata kanan), 'class', 'colspan', 'gaya'.
     */
    function sel_tabel_html($sel): string
    {
        if (! is_array($sel)) {
            return '<td>' . esc((string) $sel) . '</td>';
        }
        $isi = array_key_exists('html', $sel) ? (string) $sel['html'] : esc((string) ($sel['teks'] ?? ''));
        $atribut = '';
        $kelas = trim((string) ($sel['class'] ?? '') . (! empty($sel['angka']) ? ' angka' : ''));
        if ($kelas !== '') {
            $atribut .= ' class="' . esc($kelas, 'attr') . '"';
        }
        if (! empty($sel['colspan'])) {
            $atribut .= ' colspan="' . (int) $sel['colspan'] . '"';
        }
        if (! empty($sel['gaya'])) {
            $atribut .= ' style="' . esc((string) $sel['gaya'], 'attr') . '"';
        }
        if (! empty($sel['title'])) {
            $atribut .= ' title="' . esc((string) $sel['title'], 'attr') . '"';
        }
        return '<td' . $atribut . '>' . $isi . '</td>';
    }
}

if (! function_exists('tabel_bantu')) {
    /**
     * Helper TABEL standar PBJ (keputusan AMWAL 25 Nov 2026): merender
     * tabel Tabel Standar v2 (atribut data-cari / data-paginasi untuk
     * paginasi.js, baris grup kelas baris-grup, baris total kelas
     * baris-total) dari konfigurasi — perubahan pola tabel cukup diubah
     * di sini, bukan di tiap view. Modul berpindah ke helper ini
     * bertahap; contoh pemakaian: biayalain/_kelompok.
     *
     * Konfig:
     * - 'kolom': daftar ['judul' => teks, 'judul_html' => mentah,
     *   'angka' => bool, 'lebar' => '110px', 'class' => ...]
     * - 'baris': daftar baris; tiap baris salah satu dari:
     *     ['sel' => [sel, ...], 'class' => ..., 'atribut' => 'data-...']
     *     ['grup' => 'Teks grup'] (atau 'grup_html') -> baris-grup
     *     ['total' => [sel, ...], 'class' => tambahan] -> baris-total
     *   Sel: lihat sel_tabel_html().
     * - 'cari': petunjuk pencarian (string) atau false (bawaan false)
     * - 'paginasi': baris per halaman (int) atau false (bawaan false)
     * - 'class': kelas tambahan tabel; 'id': id tabel
     */
    function tabel_bantu(array $konfig): string
    {
        $kolom = $konfig['kolom'] ?? [];
        $atribut = '';
        if (! empty($konfig['id'])) {
            $atribut .= ' id="' . esc((string) $konfig['id'], 'attr') . '"';
        }
        if (isset($konfig['cari']) && $konfig['cari'] !== false) {
            $atribut .= ' data-cari="' . esc((string) $konfig['cari'], 'attr') . '"';
        }
        if (isset($konfig['paginasi']) && $konfig['paginasi'] !== false) {
            $atribut .= ' data-paginasi="' . (int) $konfig['paginasi'] . '"';
        }
        $atribut .= ' class="tf-tabel' . (! empty($konfig['class']) ? ' ' . esc((string) $konfig['class'], 'attr') : '') . '"';
        $h = '<table' . $atribut . '><thead><tr>';
        foreach ($kolom as $k) {
            $ka = '';
            $kelas = trim((string) ($k['class'] ?? '') . (! empty($k['angka']) ? ' angka' : ''));
            if ($kelas !== '') {
                $ka .= ' class="' . esc($kelas, 'attr') . '"';
            }
            if (! empty($k['lebar'])) {
                $ka .= ' style="width:' . esc((string) $k['lebar'], 'attr') . '"';
            }
            if (! empty($k['atribut'])) {
                $ka .= ' ' . $k['atribut'];
            }
            $h .= '<th' . $ka . '>' . (isset($k['judul_html']) ? (string) $k['judul_html'] : esc((string) ($k['judul'] ?? ''))) . '</th>';
        }
        $h .= '</tr></thead><tbody>';
        $jmlKolom = max(1, count($kolom));
        foreach ($konfig['baris'] ?? [] as $b) {
            if (isset($b['grup']) || isset($b['grup_html'])) {
                $h .= '<tr class="baris-grup"><td colspan="' . $jmlKolom . '">'
                    . (isset($b['grup_html']) ? (string) $b['grup_html'] : esc((string) $b['grup'])) . '</td></tr>';
                continue;
            }
            $adalahTotal = isset($b['total']);
            $sel = $adalahTotal ? $b['total'] : ($b['sel'] ?? []);
            $kelas = trim(($adalahTotal ? 'baris-total ' : '') . (string) ($b['class'] ?? ''));
            $h .= '<tr' . ($kelas !== '' ? ' class="' . esc($kelas, 'attr') . '"' : '')
                . (! empty($b['atribut']) ? ' ' . $b['atribut'] : '') . '>';
            foreach ($sel as $s) {
                $h .= sel_tabel_html($s);
            }
            $h .= '</tr>';
        }
        return $h . '</tbody></table>';
    }
}

if (! function_exists('form_modal')) {
    /**
     * Helper FORM MODAL CRUD (keputusan AMWAL 25 Nov 2026): merender
     * modal form pola .mk-lapik/.mk-kartu dari konfigurasi saja — modul
     * tidak lagi menulis markup + skrip modalnya sendiri. Perilaku
     * (buka/isi/tutup, kirim AJAX berbalasan JSON {ok, pesan}, hapus
     * lewat modal konfirmasi standar, validasi wajib via tooltip) ada
     * di public/assets/form-modal.js: bukaFormModal(id) untuk Tambah,
     * bukaFormModal(id, data) untuk Ubah (data = nilai field menurut
     * nama, termasuk 'id').
     *
     * Tampilan (koreksi AMWAL 25 Nov 2026): judul RATA KIRI pada pita
     * kepala yang sedikit lebih gelap, kaki tombol juga sedikit lebih
     * gelap, dan LABEL FIELD DUDUK DI BORDER isian (gaya label-on-border)
     * dengan tanda * merah untuk field wajib.
     *
     * Konfig:
     * - 'id' (wajib), 'aksi' (URL simpan, wajib)
     * - 'judul' (bawaan 'Tambah'), 'judul_ubah' (bawaan = judul)
     * - 'aksi_hapus': URL hapus berpenanda __ID__ (BUKAN {id} — kurung
     *   kurawal ter-encode oleh pembangun URL); 'konfirmasi_hapus': teks
     * - 'kolom': daftar field: ['nama', 'label', 'jenis' => teks|angka|
     *   uang|tanggal|pilihan|area, 'opsi' => [nilai => label] (pilihan),
     *   'saran' => [nilai, ...] (datalist untuk teks), 'wajib' => bool,
     *   'petunjuk', 'placeholder', 'penuh' => bool (rentang 2 kolom),
     *   'baris' => jumlah baris (area)]
     * - 'tombol': sakelar bawaan — ['batal' => true, 'hapus' => true,
     *   'simpan' => true, 'simpan_teks', 'simpan_teks_ubah', 'hapus_teks'];
     *   nonaktifkan dengan false. Hapus hanya tampil pada mode Ubah dan
     *   bila 'aksi_hapus' terisi.
     * - 'tersembunyi': daftar nama input hidden tambahan (selain 'id')
     *   yang ikut terisi oleh data bukaFormModal — mis. kunci ganda.
     * - 'info_html': HTML mentah di puncak isi modal (mis. keterangan
     *   baris yang sedang diubah); 'catatan_html': HTML mentah di bawah
     *   field (mis. pratinjau hitungan langsung yang diperbarui skrip
     *   modul). 'opsi' pilihan boleh berbentuk ['label' => ..., 'data'
     *   => [kunci => nilai]] untuk atribut data-* pada <option>.
     * - 'segarkan': daftar pemilih elemen (mis. ['#panel-x']) yang isi
     *   HTML-nya diganti dari muatan segar halaman sesudah simpan
     *   berhasil — TANPA memuat ulang seluruh halaman.
     * - 'catatan': teks kecil di bawah field; 'lebar': max-width (px, 640)
     */
    function form_modal(array $konfig): string
    {
        $id = (string) ($konfig['id'] ?? '');
        $tombol = array_merge(['batal' => true, 'hapus' => true, 'simpan' => true], $konfig['tombol'] ?? []);
        $judul = (string) ($konfig['judul'] ?? 'Tambah');
        $judulUbah = (string) ($konfig['judul_ubah'] ?? $judul);
        $lebar = (int) ($konfig['lebar'] ?? 640);
        $h = '<div id="' . esc($id, 'attr') . '" class="mk-lapik" hidden data-form-modal'
            . ' data-judul-tambah="' . esc($judul, 'attr') . '" data-judul-ubah="' . esc($judulUbah, 'attr') . '"'
            . (! empty($konfig['aksi_hapus']) ? ' data-aksi-hapus="' . esc((string) $konfig['aksi_hapus'], 'attr') . '"' : '')
            . (! empty($konfig['konfirmasi_hapus']) ? ' data-konfirmasi-hapus="' . esc((string) $konfig['konfirmasi_hapus'], 'attr') . '"' : '')
            . (! empty($konfig['segarkan']) ? ' data-fm-segar="' . esc(implode(',', (array) $konfig['segarkan']), 'attr') . '"' : '')
            . '><div class="mk-kartu fm-kartu" role="dialog" aria-modal="true" style="max-width:' . $lebar . 'px;width:94vw">'
            . '<div class="fm-kepala"><h3 data-fm-judul>' . esc($judul) . '</h3></div>'
            . '<form data-fm-form method="post" novalidate action="' . esc((string) ($konfig['aksi'] ?? ''), 'attr') . '">'
            . csrf_field()
            . '<input type="hidden" name="id" value="">'
            . implode('', array_map(static fn ($n) => '<input type="hidden" name="' . esc((string) $n, 'attr') . '" value="">', (array) ($konfig['tersembunyi'] ?? [])))
            . '<div class="fm-isi">'
            . '<div class="galat" data-fm-galat hidden style="margin-bottom:12px"></div>'
            . (! empty($konfig['info_html']) ? (string) $konfig['info_html'] : '')
            . '<div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-4">';
        foreach ($konfig['kolom'] ?? [] as $f) {
            $nama = (string) ($f['nama'] ?? '');
            $jenis = (string) ($f['jenis'] ?? 'teks');
            $label = (string) ($f['label'] ?? $nama);
            $idField = $id . '-' . $nama;
            $wajib = ! empty($f['wajib']) ? ' required' : '';
            $ph = isset($f['placeholder']) ? ' placeholder="' . esc((string) $f['placeholder'], 'attr') . '"' : '';
            $h .= '<div class="fm-field' . (! empty($f['penuh']) ? ' md:col-span-2' : '') . '"><div class="fm-kotak-field">';
            if ($jenis === 'pilihan') {
                $h .= '<select data-select-cari data-native-select id="' . esc($idField, 'attr') . '" name="' . esc($nama, 'attr') . '" data-label="' . esc($label, 'attr') . '"' . $wajib . '>';
                foreach ($f['opsi'] ?? [] as $nilai => $labelOpsi) {
                    $dataOpsi = '';
                    if (is_array($labelOpsi)) {
                        foreach ($labelOpsi['data'] ?? [] as $kd => $vd) {
                            $dataOpsi .= ' data-' . esc((string) $kd, 'attr') . '="' . esc((string) $vd, 'attr') . '"';
                        }
                        $labelOpsi = (string) ($labelOpsi['label'] ?? '');
                    }
                    $h .= '<option value="' . esc((string) $nilai, 'attr') . '"' . $dataOpsi . '>' . esc((string) $labelOpsi) . '</option>';
                }
                $h .= '</select>';
            } elseif ($jenis === 'area') {
                $h .= '<textarea id="' . esc($idField, 'attr') . '" name="' . esc($nama, 'attr') . '" data-label="' . esc($label, 'attr') . '" rows="' . (int) ($f['baris'] ?? 3) . '"' . $wajib . $ph . '></textarea>';
            } else {
                $h .= '<input type="' . ($jenis === 'tanggal' ? 'date' : 'text') . '" id="' . esc($idField, 'attr') . '" name="' . esc($nama, 'attr') . '" data-label="' . esc($label, 'attr') . '"' . $wajib . $ph;
                if ($jenis === 'tanggal') {
                    // Batas tanggal opsional (mis. Bulan Terbuka pada pencatatan uang).
                    if (! empty($f['min'])) {
                        $h .= ' min="' . esc((string) $f['min'], 'attr') . '"';
                    }
                    if (! empty($f['max'])) {
                        $h .= ' max="' . esc((string) $f['max'], 'attr') . '"';
                    }
                }
                if ($jenis === 'uang') {
                    $h .= ' data-uang inputmode="numeric"';
                } elseif ($jenis === 'angka') {
                    $h .= ' inputmode="decimal"';
                }
                if (! empty($f['saran'])) {
                    $h .= ' list="' . esc($idField . '-saran', 'attr') . '"';
                }
                $h .= '>';
                if (! empty($f['saran'])) {
                    $h .= '<datalist id="' . esc($idField . '-saran', 'attr') . '">';
                    foreach ($f['saran'] as $s) {
                        $h .= '<option value="' . esc((string) $s, 'attr') . '">';
                    }
                    $h .= '</datalist>';
                }
            }
            $h .= '<label class="fm-label" for="' . esc($idField, 'attr') . '">' . esc($label)
                . (! empty($f['wajib']) ? ' <span class="fm-wajib" aria-hidden="true">*</span>' : '') . '</label></div>';
            if (! empty($f['petunjuk'])) {
                $h .= '<span class="kecil-teks" style="display:block;margin-top:3px">' . esc((string) $f['petunjuk']) . '</span>';
            }
            $h .= '</div>';
        }
        $h .= '</div>';
        if (! empty($konfig['catatan'])) {
            $h .= '<p class="kecil-teks" style="margin:12px 0 2px">' . esc((string) $konfig['catatan']) . '</p>';
        }
        if (! empty($konfig['catatan_html'])) {
            $h .= (string) $konfig['catatan_html'];
        }
        $h .= '</div><div class="fm-kaki">';
        if ($tombol['hapus'] && ! empty($konfig['aksi_hapus'])) {
            $h .= '<button type="button" class="tombol bahaya" data-fm-hapus hidden style="margin-right:auto">' . esc((string) ($tombol['hapus_teks'] ?? 'Hapus')) . '</button>';
        }
        if ($tombol['batal']) {
            $h .= '<button type="button" data-fm-tutup>Batal</button>';
        }
        if ($tombol['simpan']) {
            $h .= '<button type="submit" class="tombol" data-fm-simpan data-teks-tambah="' . esc((string) ($tombol['simpan_teks'] ?? 'Simpan'), 'attr')
                . '" data-teks-ubah="' . esc((string) ($tombol['simpan_teks_ubah'] ?? 'Simpan Perubahan'), 'attr') . '">'
                . esc((string) ($tombol['simpan_teks'] ?? 'Simpan')) . '</button>';
        }
        return $h . '</div></form></div></div>';
    }
}
