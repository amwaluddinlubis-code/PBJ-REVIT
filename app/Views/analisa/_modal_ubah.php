<?php /* Partial analisa/detail: modal Ubah Komponen — dirender helper form_modal() (Hapus komponen ada di modal ini); keterangan baris + pratinjau di _skrip (jembatan). */ ?>
<?= form_modal([
    'id' => 'modal-ubah-komp',
    'judul' => 'Ubah Komponen',
    'aksi' => base_url('analisa/' . $a['id'] . '/komponen-ubah'),
    'aksi_hapus' => base_url('analisa/' . $a['id'] . '/komponen-hapus/__ID__'),
    'konfirmasi_hapus' => 'Hapus komponen ini dari analisa?',
    'lebar' => 440,
    'tersembunyi' => ['komponen_id'],
    'info_html' => '<p class="kecil-teks" style="margin:0 0 12px"><span id="fu-jenis"></span> · <strong id="fu-nama"></strong></p>',
    'kolom' => [
        ['nama' => 'koefisien', 'label' => 'Koefisien', 'penuh' => true],
        ['nama' => 'satuan', 'label' => 'Satuan', 'penuh' => true],
        ['nama' => 'tarif', 'label' => 'Tarif (Rp)', 'jenis' => 'uang', 'penuh' => true],
    ],
    'catatan_html' => '<p class="kecil-teks" style="margin:12px 0 0">Nilai per satuan analisa: <strong id="nilai-ubah">Rp 0</strong> <span style="opacity:.75">(terhitung langsung saat mengetik)</span></p><p class="kecil-teks" id="fu-acuan" style="margin:6px 0 0" hidden>Koefisien acuan AHSP Nasional untuk komponen ini: <strong id="fu-koef-acuan"></strong> — samakan bila memang ingin mengikuti acuan.</p>',
    'segarkan' => ['#wadah-komponen', '#ringkas-analisa'],
    'tombol' => ['simpan_teks' => 'Simpan Komponen'],
]) ?>
