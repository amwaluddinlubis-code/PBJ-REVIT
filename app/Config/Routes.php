<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('login', 'Auth::index');
$routes->post('login', 'Auth::proses');
$routes->get('logout', 'Auth::keluar');

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Dashboard::index');

    // Master bahan
    $routes->get('analisa', 'Analisa::index');
    $routes->get('analisa/(:num)', 'Analisa::detail/$1');
    $routes->post('analisa/(:num)/komponen-simpan', 'Analisa::komponenSimpan/$1');
    $routes->post('analisa/(:num)/komponen-ubah', 'Analisa::komponenUbah/$1');
    $routes->post('analisa/(:num)/komponen-tambah', 'Analisa::komponenTambah/$1');
    $routes->post('analisa/(:num)/komponen-hapus/(:num)', 'Analisa::komponenHapus/$1/$2');
    $routes->post('analisa/simpan-konversi', 'Analisa::simpanKonversi');
    $routes->post('analisa/hapus-konversi', 'Analisa::hapusKonversi');
    $routes->get('bahan', 'Bahan::index');
    $routes->get('bahan/sinkron-pratinjau', 'Bahan::sinkronPratinjau');
    $routes->post('bahan/sinkron-analisa', 'Bahan::sinkronAnalisa');
    $routes->get('bahan/kode-baru', 'Bahan::kodeBaru');
    $routes->get('bahan/baru', 'Bahan::baru');
    $routes->post('bahan/simpan', 'Bahan::simpan');
    $routes->post('bahan/kelengkapan', 'Bahan::kelengkapanSimpan');
    $routes->get('bahan/ubah/(:num)', 'Bahan::ubah/$1');
    $routes->post('bahan/perbarui/(:num)', 'Bahan::perbarui/$1');
    $routes->post('bahan/hapus/(:num)', 'Bahan::hapus/$1');
    $routes->post('bahan/gabung', 'Bahan::gabung');
    $routes->post('bahan/upah-simpan', 'Bahan::upahSimpan');
    $routes->post('bahan/upah-perbarui/(:num)', 'Bahan::upahPerbarui/$1');
    $routes->post('bahan/upah-hapus/(:num)', 'Bahan::upahHapus/$1');
    $routes->post('bahan/upah-gabung', 'Bahan::upahGabung');
    $routes->post('bahan/upah-gabung-manual', 'Bahan::upahGabungManual');
    $routes->get('bahan/tabel', 'Bahan::tabel');
    $routes->post('bahan/hapus-massal', 'Bahan::hapusMassal');
    $routes->post('bahan/kosongkan', 'Bahan::kosongkan');
    $routes->post('bahan/upah-hapus-massal', 'Bahan::upahHapusMassal');
    $routes->post('bahan/upah-kosongkan', 'Bahan::upahKosongkan');
    $routes->post('bahan/upah-rapikan', 'Bahan::upahRapikan');

    // SPK + detail
    $routes->get('spk', 'Spk::index');
    $routes->get('spk/baru', 'Spk::baru');
    $routes->post('spk/simpan', 'Spk::simpan');
    $routes->get('spk/ubah/(:num)', 'Spk::ubah/$1');
    $routes->post('spk/perbarui/(:num)', 'Spk::perbarui/$1');
    $routes->post('spk/hapus/(:num)', 'Spk::hapus/$1');
    $routes->post('spk/hapus-massal', 'Spk::hapusMassal');
    $routes->post('spk/kosongkan', 'Spk::kosongkan');
    $routes->get('spk/detail/(:num)', 'Spk::detail/$1');
    $routes->post('spk/ceklis/(:num)', 'Spk::ceklis/$1');
    $routes->post('spk/tuntaskan/(:num)', 'Spk::tuntaskan/$1');
    $routes->post('spk/bayar-cepat/(:num)', 'Spk::bayarCepat/$1');
    $routes->post('spk/tenaga-tambah/(:num)', 'Spk::tenagaTambah/$1');
    $routes->post('spk/tenaga-hapus/(:num)', 'Spk::tenagaHapus/$1');
    $routes->post('spk/detail-tambah/(:num)', 'Spk::detailTambah/$1');
    $routes->post('spk/detail-ubah/(:num)', 'Spk::detailUbah/$1');
    $routes->post('spk/detail-hapus/(:num)', 'Spk::detailHapus/$1');

    // Detail yang belum bernomor SPK
    $routes->get('belum-bernomor', 'Spk::belumBernomor');
    $routes->post('belum-bernomor/pasang/(:num)', 'Spk::pasangNomor/$1');
    // Survei & Negosiasi
    $routes->get('survei', 'Survei::index');
    $routes->post('survei/simpan', 'Survei::simpan');
    $routes->post('survei/hapus-massal', 'Survei::hapusMassal');
    $routes->post('survei/kosongkan', 'Survei::kosongkan');
    $routes->get('negosiasi', 'Negosiasi::index');
    $routes->post('negosiasi/simpan', 'Negosiasi::simpan');
    $routes->post('negosiasi/hapus-massal', 'Negosiasi::hapusMassal');
    $routes->post('negosiasi/kosongkan', 'Negosiasi::kosongkan');

    // BAP / BAST / cetak
    $routes->get('dokumen', 'Dokumen::index');
    $routes->post('dokumen/bap-buat/(:num)', 'Dokumen::bapBuat/$1');
    $routes->get('dokumen/bap-ubah/(:num)', 'Dokumen::bapUbah/$1');
    $routes->post('dokumen/bap-simpan/(:num)', 'Dokumen::bapSimpan/$1');
    $routes->post('dokumen/bast-buat/(:num)', 'Dokumen::bastBuat/$1');
    $routes->get('dokumen/bast-ubah/(:num)', 'Dokumen::bastUbah/$1');
    $routes->post('dokumen/bast-simpan/(:num)', 'Dokumen::bastSimpan/$1');
    $routes->get('dokumen/cetak-spk/(:num)', 'Dokumen::cetakSpk/$1');
    $routes->get('dokumen/cetak-spj/(:num)', 'Dokumen::cetakSpj/$1');
    $routes->get('dokumen/cetak-bast-aset/(:num)', 'Dokumen::cetakBastAset/$1');
    $routes->get('dokumen/cetak-papan-nama', 'Dokumen::cetakPapanNama');
    $routes->get('dokumen/cetak-bap/(:num)', 'Dokumen::cetakBap/$1');
    $routes->get('dokumen/cetak-bast/(:num)', 'Dokumen::cetakBast/$1');

    // Invoice & jurnal
    $routes->get('invoice', 'Invoice::index');
    $routes->get('invoice/baru', 'Invoice::baru');
    $routes->post('invoice/simpan', 'Invoice::simpan');
    $routes->get('invoice/ubah/(:num)', 'Invoice::ubah/$1');
    $routes->post('invoice/perbarui/(:num)', 'Invoice::perbarui/$1');
    $routes->post('invoice/hapus/(:num)', 'Invoice::hapus/$1');
    $routes->post('invoice/hapus-massal', 'Invoice::hapusMassal');
    $routes->post('invoice/kosongkan', 'Invoice::kosongkan');
    $routes->get('invoice/cetak/(:num)', 'Invoice::cetak/$1');
    $routes->get('invoice/jurnal', 'Invoice::jurnal');
    $routes->post('invoice/jurnal-tambah', 'Invoice::jurnalTambah');
    $routes->post('invoice/jurnal-hapus/(:num)', 'Invoice::jurnalHapus/$1');

    // Buku material & sisa bahan
    $routes->get('material', 'Material::index');
    $routes->post('material/simpan', 'Material::simpan');
    $routes->post('material/hapus/(:num)', 'Material::hapus/$1');
    $routes->post('material/hapus-massal', 'Material::hapusMassal');
    $routes->post('material/kosongkan', 'Material::kosongkan');
    $routes->get('sisa-bahan', 'Material::sisa');

    // Laporan & ekspor (Fase 3)
    $routes->get('laporan', 'Laporan::index');
    $routes->get('laporan/cetak', 'Laporan::cetak');
    $routes->get('laporan/ekspor-spk', 'Laporan::eksporSpk');
    $routes->get('laporan/ekspor-material', 'Laporan::eksporMaterial');
    $routes->get('laporan/ekspor-sisa', 'Laporan::eksporSisa');

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
    $routes->post('pengaturan/login-tampilan', 'Pengaturan::loginTampilan');
    $routes->get('pengaturan/akses-menu', 'Pengaturan::aksesMenu');
    $routes->post('pengaturan/akses-menu', 'Pengaturan::aksesMenuSimpan');
    $routes->post('pengaturan/penyedia-tambah', 'Pengaturan::penyediaTambah');
    $routes->post('pengaturan/penyedia-ubah/(:num)', 'Pengaturan::penyediaUbah/$1');
    $routes->post('pengaturan/penyedia-hapus/(:num)', 'Pengaturan::penyediaHapus/$1');
$routes->post('pengaturan/penyedia-hapus-massal', 'Pengaturan::penyediaHapusMassal');
$routes->post('pengaturan/penyedia-kosongkan', 'Pengaturan::penyediaKosongkan');
});

// Modul Analisa Perencana
$routes->get('impor', 'Impor::index', ['filter' => 'auth']);
$routes->post('impor/proses', 'Impor::proses', ['filter' => 'auth']);
$routes->get('impor/petakan', 'Impor::petakan', ['filter' => 'auth']);
$routes->post('impor/eksekusi', 'Impor::eksekusi', ['filter' => 'auth']);
$routes->post('impor/batal', 'Impor::batal', ['filter' => 'auth']);
$routes->get('gedung', 'Gedung::index', ['filter' => 'auth']);
$routes->post('gedung/hapus-massal', 'Gedung::hapusMassal', ['filter' => 'auth']);
$routes->post('gedung/kosongkan', 'Gedung::kosongkan', ['filter' => 'auth']);
$routes->post('gedung/pekerjaan/hapus-massal', 'Gedung::hapusPekerjaanMassal', ['filter' => 'auth']);
$routes->post('gedung/(:num)/kosongkan-boq', 'Gedung::kosongkanPekerjaan/$1', ['filter' => 'auth']);
$routes->post('gedung/simpan', 'Gedung::simpan', ['filter' => 'auth']);
$routes->post('gedung/(:num)/perbarui', 'Gedung::perbarui/$1', ['filter' => 'auth']);
$routes->post('gedung/(:num)/hapus', 'Gedung::hapus/$1', ['filter' => 'auth']);
$routes->post('gedung/(:num)/didanai', 'Gedung::didanai/$1', ['filter' => 'auth']);
$routes->get('gedung/(:num)', 'Gedung::detail/$1', ['filter' => 'auth']);
$routes->get('gedung/(:num)/(:segment)', 'Gedung::detail/$1/$2', ['filter' => 'auth']);
$routes->post('gedung/pekerjaan/simpan', 'Gedung::simpanPekerjaan', ['filter' => 'auth']);
$routes->post('gedung/pekerjaan/(:num)/volume', 'Gedung::volumePekerjaan/$1', ['filter' => 'auth']);
$routes->post('gedung/pekerjaan/(:num)/hapus', 'Gedung::hapusPekerjaan/$1', ['filter' => 'auth']);
$routes->post('gedung/pekerjaan/(:num)/realisasi', 'Gedung::simpanRealisasi/$1', ['filter' => 'auth']);
$routes->post('gedung/realisasi/(:num)/hapus', 'Gedung::hapusRealisasi/$1', ['filter' => 'auth']);
$routes->post('gedung/tautkan', 'Gedung::tautkan', ['filter' => 'auth']);
$routes->get('rencana-belanja', 'Rencana::index', ['filter' => 'auth']);
$routes->get('biaya-non-gedung', 'BiayaLain::index', ['filter' => 'auth']);
$routes->post('biaya-non-gedung/simpan', 'BiayaLain::simpan', ['filter' => 'auth']);
$routes->post('biaya-non-gedung/hapus/(:num)', 'BiayaLain::hapus/$1', ['filter' => 'auth']);
$routes->post('biaya-non-gedung/hapus-massal', 'BiayaLain::hapusMassal', ['filter' => 'auth']);
$routes->post('biaya-non-gedung/kosongkan', 'BiayaLain::kosongkan', ['filter' => 'auth']);
$routes->post('rencana-belanja/buat-spk', 'Rencana::buatSpk', ['filter' => 'auth']);
$routes->post('rencana-belanja/hapus-bahan/(:num)', 'Rencana::hapusBahan/$1', ['filter' => 'auth']);
$routes->get('rencana-mingguan', 'RencanaMingguan::index', ['filter' => 'auth']);
$routes->get('simulasi', 'Simulasi::index', ['filter' => 'auth']);
$routes->post('simulasi/simpan', 'Simulasi::simpan', ['filter' => 'auth']);
$routes->post('rencana-mingguan/buat', 'RencanaMingguan::buat', ['filter' => 'auth']);
$routes->get('rencana-mingguan/(:num)', 'RencanaMingguan::detail/$1', ['filter' => 'auth']);
$routes->post('rencana-mingguan/(:num)/target', 'RencanaMingguan::simpanTarget/$1', ['filter' => 'auth']);
$routes->post('rencana-mingguan/(:num)/target-kurva', 'RencanaMingguan::targetKurva/$1', ['filter' => 'auth']);
$routes->post('rencana-mingguan/(:num)/buat-spk', 'RencanaMingguan::buatSpk/$1', ['filter' => 'auth']);
$routes->post('rencana-mingguan/(:num)/hapus', 'RencanaMingguan::hapus/$1', ['filter' => 'auth']);
$routes->get('upah', 'Upah::index', ['filter' => 'auth']);
$routes->get('upah/cetak-daftar', 'Upah::cetakDaftar', ['filter' => 'auth']);
$routes->post('upah/simpan', 'Upah::simpan', ['filter' => 'auth']);
$routes->post('upah/master', 'Upah::masterSimpan', ['filter' => 'auth']);
$routes->post('upah/tarif/(:num)', 'Upah::tarif/$1', ['filter' => 'auth']);
$routes->post('upah/(:num)/hapus', 'Upah::hapus/$1', ['filter' => 'auth']);
$routes->post('upah/hapus-massal', 'Upah::hapusMassal', ['filter' => 'auth']);
$routes->post('upah/kosongkan', 'Upah::kosongkan', ['filter' => 'auth']);
$routes->get('penerimaan-dana', 'Penerimaan::index', ['filter' => 'auth']);
$routes->post('penerimaan-dana/simpan', 'Penerimaan::simpan', ['filter' => 'auth']);
$routes->post('penerimaan-dana/hapus-massal', 'Penerimaan::hapusMassal', ['filter' => 'auth']);
$routes->post('penerimaan-dana/kosongkan', 'Penerimaan::kosongkan', ['filter' => 'auth']);
$routes->get('penerimaan-dana/kuitansi/(:num)', 'Penerimaan::cetakKuitansi/$1', ['filter' => 'auth']);
$routes->get('buku-kas', 'BukuKas::index', ['filter' => 'auth']);
$routes->post('buku-kas/simpan', 'BukuKas::simpan', ['filter' => 'auth']);
$routes->post('buku-kas/hapus/(:num)', 'BukuKas::hapus/$1', ['filter' => 'auth']);
$routes->post('buku-kas/hapus-massal', 'BukuKas::hapusMassal', ['filter' => 'auth']);
$routes->post('buku-kas/kosongkan', 'BukuKas::kosongkan', ['filter' => 'auth']);
$routes->get('buku-kas/cetak', 'BukuKas::cetak', ['filter' => 'auth']);
$routes->get('bersih', 'Bersih::index', ['filter' => 'auth']);
$routes->get('bersih/grup/(:num)', 'Bersih::grup/$1', ['filter' => 'auth']);
$routes->post('bersih/langkah2', 'Bersih::langkah2', ['filter' => 'auth']);
$routes->get('bersih/analisa', 'Bersih::analisa', ['filter' => 'auth']);
$routes->post('bersih/langkah3', 'Bersih::langkah3', ['filter' => 'auth']);
$routes->get('bersih/bahan', 'Bersih::bahan', ['filter' => 'auth']);
$routes->post('bersih/eksekusi', 'Bersih::eksekusi', ['filter' => 'auth']);
$routes->post('bersih/batal', 'Bersih::batal', ['filter' => 'auth']);
$routes->get('kurva-s', 'Kurva::index', ['filter' => 'auth']);
$routes->post('kurva-s/impor', 'Kurva::impor', ['filter' => 'auth']);
$routes->get('acuan', 'Acuan::index', ['filter' => 'auth']);
$routes->get('acuan/(:num)', 'Acuan::detail/$1', ['filter' => 'auth']);
$routes->get('validasi-harga', 'ValidasiHarga::index', ['filter' => 'auth']);
$routes->post('validasi-harga/samakan-pekerjaan', 'ValidasiHarga::samakanPekerjaan', ['filter' => 'auth']);
$routes->post('validasi-harga/samakan-kalkulasi', 'ValidasiHarga::samakanKalkulasi', ['filter' => 'auth']);
$routes->post('validasi-harga/samakan-kalkulasi-semua', 'ValidasiHarga::samakanKalkulasiSemua', ['filter' => 'auth']);
$routes->get('revisi-pekerjaan', 'Revisi::index', ['filter' => 'auth']);
$routes->post('revisi-pekerjaan/simpan', 'Revisi::simpan', ['filter' => 'auth']);
$routes->post('revisi-pekerjaan/sahkan/(:num)', 'Revisi::sahkan/$1', ['filter' => 'auth']);
$routes->post('revisi-pekerjaan/batal-sah/(:num)', 'Revisi::batalSah/$1', ['filter' => 'auth']);
$routes->post('revisi-pekerjaan/hapus/(:num)', 'Revisi::hapus/$1', ['filter' => 'auth']);
$routes->post('revisi-pekerjaan/hapus-massal', 'Revisi::hapusMassal', ['filter' => 'auth']);
$routes->post('revisi-pekerjaan/kosongkan', 'Revisi::kosongkan', ['filter' => 'auth']);
$routes->get('revisi-pekerjaan/cetak/(:num)', 'Revisi::cetak/$1', ['filter' => 'auth']);
$routes->get('bukti-setor', 'Setor::index', ['filter' => 'auth']);
$routes->post('bukti-setor/simpan', 'Setor::simpan', ['filter' => 'auth']);
$routes->post('bukti-setor/hapus/(:num)', 'Setor::hapus/$1', ['filter' => 'auth']);
$routes->post('bukti-setor/hapus-massal', 'Setor::hapusMassal', ['filter' => 'auth']);
$routes->post('bukti-setor/kosongkan', 'Setor::kosongkan', ['filter' => 'auth']);
$routes->get('foto-progres', 'Foto::index', ['filter' => 'auth']);
$routes->post('foto-progres/unggah', 'Foto::unggah', ['filter' => 'auth']);
$routes->post('foto-progres/hapus/(:num)', 'Foto::hapus/$1', ['filter' => 'auth']);
$routes->post('foto-progres/hapus-massal', 'Foto::hapusMassal', ['filter' => 'auth']);
$routes->post('foto-progres/kosongkan', 'Foto::kosongkan', ['filter' => 'auth']);
$routes->get('laporan-kemajuan', 'Kemajuan::index', ['filter' => 'auth']);
$routes->get('laporan-kemajuan/cetak-kemajuan', 'Kemajuan::cetakKemajuan', ['filter' => 'auth']);
$routes->get('laporan-kemajuan/cetak-akhir', 'Kemajuan::cetakAkhir', ['filter' => 'auth']);
$routes->get('lpj', 'Lpj::index', ['filter' => 'auth']);
$routes->get('lpj/gedung/(:num)', 'Lpj::gedung/$1', ['filter' => 'auth']);
$routes->get('lpj/cetak', 'Lpj::cetakSekolah', ['filter' => 'auth']);
$routes->get('lpj/cetak/gedung/(:num)', 'Lpj::cetakGedung/$1', ['filter' => 'auth']);
$routes->get('lpj/rab-gabungan', 'Lpj::rabGabungan', ['filter' => 'auth']);
$routes->get('lpj/cetak/rab-gabungan', 'Lpj::cetakRabGabungan', ['filter' => 'auth']);
$routes->get('pengguna', 'Pengguna::index', ['filter' => 'auth']);
$routes->post('pengguna/simpan', 'Pengguna::simpan', ['filter' => 'auth']);
$routes->post('pengguna/(:num)/ubah', 'Pengguna::ubah/$1', ['filter' => 'auth']);
$routes->post('pengguna/(:num)/hapus', 'Pengguna::hapus/$1', ['filter' => 'auth']);
$routes->post('pengguna/hapus-massal', 'Pengguna::hapusMassal', ['filter' => 'auth']);
$routes->get('pemeliharaan-db', 'Pemeliharaan::index', ['filter' => 'auth']);
$routes->get('audit', 'Audit::index', ['filter' => 'auth']);
$routes->post('pemeliharaan-db/backup', 'Pemeliharaan::backup', ['filter' => 'auth']);
$routes->get('pemeliharaan-db/unduh/(:segment)', 'Pemeliharaan::unduh/$1', ['filter' => 'auth']);
$routes->post('pemeliharaan-db/hapus-backup', 'Pemeliharaan::hapusBackup', ['filter' => 'auth']);
$routes->post('pemeliharaan-db/pulih', 'Pemeliharaan::pulih', ['filter' => 'auth']);
$routes->post('pemeliharaan-db/optimasi', 'Pemeliharaan::optimasi', ['filter' => 'auth']);
$routes->post('pemeliharaan-db/sampah-pulihkan', 'Pemeliharaan::sampahPulihkan', ['filter' => 'auth']);
$routes->post('pemeliharaan-db/sampah-hapus', 'Pemeliharaan::sampahHapus', ['filter' => 'auth']);
$routes->get('profil', 'Pengguna::profil', ['filter' => 'auth']);
$routes->post('profil/simpan', 'Pengguna::profilSimpan', ['filter' => 'auth']);
$routes->post('profil/sandi', 'Pengguna::sandiSimpan', ['filter' => 'auth']);
$routes->post('impor/folder', 'Impor::folder', ['filter' => 'auth']);
$routes->post('impor/ulang', 'Impor::ulang', ['filter' => 'auth']);
$routes->post('impor/tautkan', 'Impor::tautkan', ['filter' => 'auth']);
$routes->post('impor/tautkan-massal', 'Impor::tautkanMassal', ['filter' => 'auth']);
$routes->get('impor/unduh-template', 'Impor::unduhTemplate', ['filter' => 'auth']);
$routes->get('impor/ekspor-template', 'Impor::eksporTemplate', ['filter' => 'auth']);
