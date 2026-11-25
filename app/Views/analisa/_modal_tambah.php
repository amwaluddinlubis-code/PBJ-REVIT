<?php /* Partial analisa/detail: modal Tambah Komponen — dirender helper form_modal(); isi otomatis + pratinjau nilai di _skrip (jembatan). */ ?>
<?php
$opsiBahan = ['' => ['label' => '— ketik nama manual —']];
foreach ($bahan ?? [] as $b) {
    $opsiBahan[(int) $b['id']] = [
        'label' => $b['kode'] . ' — ' . $b['nama'],
        'data' => ['nama' => (string) $b['nama'], 'satuan' => (string) ($b['satuan'] ?? ''), 'tarif' => (int) ($acuanBahan[(int) $b['id']] ?? 0)],
    ];
}
?>
<?= form_modal([
    'id' => 'modal-tambah-komp',
    'judul' => 'Tambah Komponen',
    'aksi' => base_url('analisa/' . $a['id'] . '/komponen-tambah'),
    'lebar' => 520,
    'kolom' => [
        ['nama' => 'jenis', 'label' => 'Jenis', 'jenis' => 'pilihan', 'opsi' => ['bahan' => 'Bahan', 'upah' => 'Upah', 'alat' => 'Alat']],
        ['nama' => 'bahan_id', 'label' => 'Bahan dari master (jenis Bahan)', 'jenis' => 'pilihan', 'opsi' => $opsiBahan],
        ['nama' => 'nama', 'label' => 'Nama komponen', 'placeholder' => 'nama komponen'],
        ['nama' => 'koefisien', 'label' => 'Koefisien', 'placeholder' => '0,0000'],
        ['nama' => 'satuan', 'label' => 'Satuan', 'placeholder' => 'm3 / OH / zak'],
        ['nama' => 'tarif', 'label' => 'Tarif (Rp)', 'jenis' => 'uang', 'placeholder' => 'otomatis bila dari master'],
    ],
    'catatan_html' => '<p class="kecil-teks" style="margin:12px 0 0">Nilai per satuan dari isian ini: <strong id="nilai-tambah">Rp 0</strong>. Untuk jenis Upah, isi Nama persis seperti jenis di Master Upah agar tarif master berlaku. Memilih Bahan dari master mengisi otomatis nama, satuan, dan tarif (harga acuan bahan) — ketiganya tetap bisa diubah sebelum disimpan.</p>',
    'segarkan' => ['#wadah-komponen', '#ringkas-analisa'],
    'tombol' => ['simpan_teks' => '+ Tambah Komponen', 'hapus' => false],
]) ?>
