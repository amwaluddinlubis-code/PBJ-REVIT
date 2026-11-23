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
