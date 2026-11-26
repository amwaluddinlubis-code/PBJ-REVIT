<?php /* Partial gedung/detail tab BOQ: panel Tambah Pekerjaan Manual — form kini modal helper form_modal (simpan AJAX ke Gedung::simpanPekerjaan; gedung_id lewat input tersembunyi). Kode analisa berupa pemilih dari master Analisa; satuan & harga diwariskan dari analisa terpilih. */ ?><div class="panel">
  <h2>Tambah Pekerjaan Manual</h2>
  <button type="button" class="tombol" onclick="bukaFormModal('modal-pekerjaan', { gedung_id: <?= (int) $g['id'] ?>, volume: '0', harga_satuan: '0' })">+ Tambah Pekerjaan</button>
</div>
<?php
$opsiAnalisa = ['' => '— tanpa analisa —'];
foreach (($analisaPilih ?? []) as $a) {
    $opsiAnalisa[$a['kode']] = [
        'label' => $a['kode'] . ' — ' . $a['nama'],
        'data' => [
            'satuan' => $a['satuan'],
            'harga' => $a['harga'] === null ? '' : (string) (int) round((float) $a['harga']),
        ],
    ];
}
echo form_modal([
    'id' => 'modal-pekerjaan',
    'judul' => 'Tambah Pekerjaan Manual',
    'aksi' => base_url('gedung/pekerjaan/simpan'),
    'tersembunyi' => ['gedung_id'],
    'kolom' => [
        ['nama' => 'nama', 'label' => 'Nama pekerjaan', 'wajib' => true],
        ['nama' => 'bab', 'label' => 'Bab', 'placeholder' => 'mis. II. PEKERJAAN TANAH', 'saran' => ($daftarBab ?? [])],
        ['nama' => 'kode_analisa', 'label' => 'Kode analisa', 'jenis' => 'pilihan', 'opsi' => $opsiAnalisa,
            'petunjuk' => 'Memilih analisa mengisi satuan & harga satuan otomatis (tetap bisa diubah).'],
        ['nama' => 'satuan', 'label' => 'Satuan', 'placeholder' => 'm3 / m2 / ls'],
        ['nama' => 'volume', 'label' => 'Volume', 'jenis' => 'angka'],
        ['nama' => 'harga_satuan', 'label' => 'Harga satuan (Rp)', 'jenis' => 'uang'],
    ],
    'tombol' => ['hapus' => false, 'simpan_teks' => 'Tambah Pekerjaan'],
]);
?>
<script>
// Jembatan modal Tambah Pekerjaan: memilih kode analisa mewariskan
// satuan & harga satuan analisa ke isian (tetap bisa diubah).
(function () {
  var modal = document.getElementById('modal-pekerjaan');
  if (!modal) return;
  var sel = modal.querySelector('[name="kode_analisa"]');
  if (!sel) return;
  sel.addEventListener('change', function () {
    var opt = sel.options[sel.selectedIndex];
    if (!opt) return;
    var set = function (nama, nilai) {
      var inp = modal.querySelector('[name="' + nama + '"]');
      if (inp && nilai) {
        inp.value = nilai;
        inp.dispatchEvent(new Event('input', { bubbles: true }));
      }
    };
    set('satuan', opt.getAttribute('data-satuan'));
    set('harga_satuan', opt.getAttribute('data-harga'));
  });
})();
</script>
