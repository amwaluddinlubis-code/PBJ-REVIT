<?php /* Partial bahan/index: modal Tambah/Ubah Bahan memakai helper
   form_modal — simpan AJAX ke bahan/simpan (id tersembunyi membedakan
   baru/ubah; bahan/perbarui/{id} tetap untuk halaman form mandiri
   bahan/form.php). Sesudah simpan, tabel disegarkan tanpa reload
   (segarkan #wadah-tabel-bahan). Jembatan bukaTambah/bukaUbah ada di
   komponen Alpine induk (index.php). */ ?>
<?= form_modal([
    'id' => 'modal-bahan',
    'judul' => 'Tambah Bahan',
    'judul_ubah' => 'Ubah Bahan',
    'aksi' => base_url('bahan/simpan'),
    'segarkan' => ['#wadah-tabel-bahan'],
    'kolom' => [
        ['nama' => 'kode', 'label' => 'Kode', 'wajib' => true],
        ['nama' => 'nama', 'label' => 'Nama Bahan', 'wajib' => true],
        ['nama' => 'satuan', 'label' => 'Satuan'],
        ['nama' => 'harga_rab', 'label' => 'Harga RAB (Rp)', 'jenis' => 'uang', 'wajib' => true],
    ],
    'tombol' => ['simpan_teks' => 'Simpan', 'simpan_teks_ubah' => 'Simpan'],
]) ?>
