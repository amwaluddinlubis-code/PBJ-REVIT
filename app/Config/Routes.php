<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('login', 'Auth::index');
$routes->post('login', 'Auth::proses');
$routes->get('logout', 'Auth::keluar');

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Dashboard::index');

    // Master bahan
    $routes->get('bahan', 'Bahan::index');
    $routes->get('bahan/baru', 'Bahan::baru');
    $routes->post('bahan/simpan', 'Bahan::simpan');
    $routes->get('bahan/ubah/(:num)', 'Bahan::ubah/$1');
    $routes->post('bahan/perbarui/(:num)', 'Bahan::perbarui/$1');
    $routes->post('bahan/hapus/(:num)', 'Bahan::hapus/$1');

    // SPK + detail
    $routes->get('spk', 'Spk::index');
    $routes->get('spk/baru', 'Spk::baru');
    $routes->post('spk/simpan', 'Spk::simpan');
    $routes->get('spk/ubah/(:num)', 'Spk::ubah/$1');
    $routes->post('spk/perbarui/(:num)', 'Spk::perbarui/$1');
    $routes->post('spk/hapus/(:num)', 'Spk::hapus/$1');
    $routes->get('spk/detail/(:num)', 'Spk::detail/$1');
    $routes->post('spk/detail-tambah/(:num)', 'Spk::detailTambah/$1');
    $routes->post('spk/detail-ubah/(:num)', 'Spk::detailUbah/$1');
    $routes->post('spk/detail-hapus/(:num)', 'Spk::detailHapus/$1');

    // Detail yang belum bernomor SPK
    $routes->get('belum-bernomor', 'Spk::belumBernomor');
    $routes->post('belum-bernomor/pasang/(:num)', 'Spk::pasangNomor/$1');
    // Survei & Negosiasi
    $routes->get('survei', 'Survei::index');
    $routes->post('survei/simpan', 'Survei::simpan');
    $routes->get('negosiasi', 'Negosiasi::index');
    $routes->post('negosiasi/simpan', 'Negosiasi::simpan');

    // BAP / BAST / cetak
    $routes->get('dokumen', 'Dokumen::index');
    $routes->post('dokumen/bap-buat/(:num)', 'Dokumen::bapBuat/$1');
    $routes->get('dokumen/bap-ubah/(:num)', 'Dokumen::bapUbah/$1');
    $routes->post('dokumen/bap-simpan/(:num)', 'Dokumen::bapSimpan/$1');
    $routes->post('dokumen/bast-buat/(:num)', 'Dokumen::bastBuat/$1');
    $routes->get('dokumen/bast-ubah/(:num)', 'Dokumen::bastUbah/$1');
    $routes->post('dokumen/bast-simpan/(:num)', 'Dokumen::bastSimpan/$1');
    $routes->get('dokumen/cetak-spk/(:num)', 'Dokumen::cetakSpk/$1');
    $routes->get('dokumen/cetak-bap/(:num)', 'Dokumen::cetakBap/$1');
    $routes->get('dokumen/cetak-bast/(:num)', 'Dokumen::cetakBast/$1');

    // Invoice & jurnal
    $routes->get('invoice', 'Invoice::index');
    $routes->get('invoice/baru', 'Invoice::baru');
    $routes->post('invoice/simpan', 'Invoice::simpan');
    $routes->get('invoice/ubah/(:num)', 'Invoice::ubah/$1');
    $routes->post('invoice/perbarui/(:num)', 'Invoice::perbarui/$1');
    $routes->post('invoice/hapus/(:num)', 'Invoice::hapus/$1');
    $routes->get('invoice/jurnal', 'Invoice::jurnal');
    $routes->post('invoice/jurnal-tambah', 'Invoice::jurnalTambah');
    $routes->post('invoice/jurnal-hapus/(:num)', 'Invoice::jurnalHapus/$1');

    // Buku material & sisa bahan
    $routes->get('material', 'Material::index');
    $routes->post('material/simpan', 'Material::simpan');
    $routes->post('material/hapus/(:num)', 'Material::hapus/$1');
    $routes->get('sisa-bahan', 'Material::sisa');

    // Multi-sekolah
    $routes->get('sekolah', 'Sekolah::index');
    $routes->get('sekolah/baru', 'Sekolah::baru');
    $routes->post('sekolah/simpan', 'Sekolah::simpan');
    $routes->post('sekolah/pilih', 'Sekolah::pilih');
    $routes->get('sekolah/pilih/(:num)', 'Sekolah::pilih/$1');
    $routes->post('sekolah/hapus/(:num)', 'Sekolah::hapus/$1');

    // Pengaturan
    $routes->get('pengaturan', 'Pengaturan::index');
    $routes->post('pengaturan/simpan', 'Pengaturan::simpan');
    $routes->post('pengaturan/penyedia-tambah', 'Pengaturan::penyediaTambah');
    $routes->post('pengaturan/penyedia-hapus/(:num)', 'Pengaturan::penyediaHapus/$1');
});
