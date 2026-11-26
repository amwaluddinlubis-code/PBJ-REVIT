<?php

namespace App\Controllers;

use App\Libraries\MarkdownMini;

/**
 * Halaman Panduan Penggunaan dalam aplikasi: merender
 * docs/Panduan_Penggunaan.md (sumber yang sama dengan dokumentasi repo)
 * agar pengguna tidak perlu membuka berkas di luar aplikasi.
 */
class Panduan extends BaseController
{
    public function index()
    {
        $berkas = dirname(APPPATH) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'Panduan_Penggunaan.md';
        $md     = is_file($berkas) ? (string) file_get_contents($berkas) : '';

        return $this->tampil('panduan/index', [
            'isi' => $md !== '' ? MarkdownMini::keHtml($md) : '<p>Panduan tidak ditemukan pada instalasi ini.</p>',
            'bab' => $md !== '' ? MarkdownMini::daftarBab($md) : [],
        ], 'Panduan Penggunaan — Aplikasi PBJ Sekolah');
    }
}
