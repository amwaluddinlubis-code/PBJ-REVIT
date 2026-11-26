<?php /* Partial sekolah/index: modal Tambah Sekolah memakai helper
   form_modal — simpan AJAX ke sekolah/simpan; halaman form mandiri
   sekolah/form.php tetap ada sebagai cadangan (POST non-AJAX). */ ?>
<?= form_modal([
    'id' => 'modal-sekolah',
    'judul' => 'Tambah Sekolah',
    'aksi' => base_url('sekolah/simpan'),
    'lebar' => 720,
    'info_html' => '<p class="kecil-teks" style="margin:0 0 12px">Isi data pokok sekolah. Kop surat, pejabat lengkap, dan nama toko survei dapat dilengkapi sesudahnya di <strong>Identitas &amp; Pengaturan</strong>. Sekolah baru langsung menjadi sekolah aktif dengan data kosong.</p>',
    'kolom' => [
        ['nama' => 'nama_sekolah', 'label' => 'Nama Sekolah', 'jenis' => 'teks', 'wajib' => true],
        ['nama' => 'npsn', 'label' => 'NPSN', 'jenis' => 'teks'],
        ['nama' => 'alamat', 'label' => 'Alamat', 'jenis' => 'teks'],
        ['nama' => 'desa', 'label' => 'Desa/Kelurahan', 'jenis' => 'teks'],
        ['nama' => 'kecamatan', 'label' => 'Kecamatan', 'jenis' => 'teks'],
        ['nama' => 'kabupaten', 'label' => 'Kabupaten', 'jenis' => 'teks'],
        ['nama' => 'provinsi', 'label' => 'Provinsi', 'jenis' => 'teks'],
        ['nama' => 'email', 'label' => 'Email Sekolah', 'jenis' => 'teks'],
        ['nama' => 'kode_pos', 'label' => 'Kode Pos', 'jenis' => 'teks'],
        ['nama' => 'tahun_anggaran', 'label' => 'Tahun Anggaran', 'jenis' => 'teks'],
        ['nama' => 'paket_pekerjaan', 'label' => 'Paket Pekerjaan', 'jenis' => 'teks'],
        ['nama' => 'kepsek_nama', 'label' => 'Kepala Sekolah', 'jenis' => 'teks'],
        ['nama' => 'kepsek_nip', 'label' => 'NIP Kepala Sekolah', 'jenis' => 'teks'],
        ['nama' => 'ketua_nama', 'label' => 'Ketua Panitia P2SP', 'jenis' => 'teks'],
        ['nama' => 'sekretaris_nama', 'label' => 'Sekretaris / Logistik', 'jenis' => 'teks'],
        ['nama' => 'pelaksana_nama', 'label' => 'Kepala Pelaksana', 'jenis' => 'teks'],
    ],
    'tombol' => ['simpan_teks' => 'Simpan & Aktifkan'],
]) ?>
