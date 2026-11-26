<?php /* Partial gedung/detail tab BOQ: panel Tambah Pekerjaan Manual — form kini modal helper form_modal (simpan AJAX ke Gedung::simpanPekerjaan; gedung_id lewat input tersembunyi). */ ?><div class="panel">
  <h2>Tambah Pekerjaan Manual</h2>
  <button type="button" class="tombol" onclick="bukaFormModal('modal-pekerjaan', { gedung_id: <?= (int) $g['id'] ?>, volume: '0', harga_satuan: '0' })">+ Tambah Pekerjaan</button>
</div>
<?= form_modal([
    'id' => 'modal-pekerjaan',
    'judul' => 'Tambah Pekerjaan Manual',
    'aksi' => base_url('gedung/pekerjaan/simpan'),
    'tersembunyi' => ['gedung_id'],
    'kolom' => [
        ['nama' => 'nama', 'label' => 'Nama pekerjaan', 'wajib' => true],
        ['nama' => 'bab', 'label' => 'Bab', 'placeholder' => 'mis. II. PEKERJAAN TANAH'],
        ['nama' => 'kode_analisa', 'label' => 'Kode analisa', 'placeholder' => 'mis. B.01'],
        ['nama' => 'satuan', 'label' => 'Satuan', 'placeholder' => 'm3 / m2 / ls'],
        ['nama' => 'volume', 'label' => 'Volume', 'jenis' => 'angka'],
        ['nama' => 'harga_satuan', 'label' => 'Harga satuan (Rp)', 'jenis' => 'uang'],
    ],
    'tombol' => ['hapus' => false, 'simpan_teks' => 'Tambah Pekerjaan'],
]) ?>
