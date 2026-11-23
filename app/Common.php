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
