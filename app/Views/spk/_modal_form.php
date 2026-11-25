<?php /* Partial spk/index: modal form SPK (baru & ubah) memakai helper form_modal() (26 Nov 2026, menggantikan modal tulisan tangan lama). Bidang sama persis dengan spk/form.php; halaman form mandiri tetap ada sebagai cadangan. Aksi form ditukar jembatan: spk/simpan untuk baru, spk/perbarui/{id} untuk ubah; simpan AJAX (Spk::simpan/perbarui membalas JSON). Hapus lewat tombol Hapus bawaan helper (aksi_hapus __ID__). */ ?>
<?php
$opsiPenyediaReg = ['' => '— pilih / isi baru di bawah —'];
foreach ($penyedia ?? [] as $p) {
    $opsiPenyediaReg[(int) $p['id']] = (string) $p['nama'];
}
$opsiGedungReg = ['' => '— umum / belum ditentukan —'];
foreach ($semuaGedung ?? [] as $gd) {
    $opsiGedungReg[(int) $gd['id']] = (string) $gd['nama'];
}
?>
<?= form_modal([
    'id' => 'modal-spk',
    'judul' => 'SPK Baru',
    'judul_ubah' => 'Ubah SPK',
    'aksi' => base_url('spk/simpan'),
    'aksi_hapus' => base_url('spk/hapus/__ID__'),
    'konfirmasi_hapus' => 'Hapus SPK ini? Rincian barangnya menjadi belum bernomor.',
    'lebar' => 720,
    'kolom' => [
        ['nama' => 'nomor', 'label' => 'Nomor SPK', 'jenis' => 'angka', 'wajib' => true],
        ['nama' => 'tanggal', 'label' => 'Tanggal', 'jenis' => 'tanggal'],
        ['nama' => 'penyedia_id', 'label' => 'Penyedia', 'jenis' => 'pilihan', 'opsi' => $opsiPenyediaReg],
        ['nama' => 'penyedia_baru', 'label' => 'Penyedia Baru (bila belum ada)'],
        ['nama' => 'penerima_manual', 'label' => 'Nama Penerima (khusus penyedia "Masyarakat")', 'penuh' => true, 'petunjuk' => 'Bila penyedia dipilih "Masyarakat", isi nama orangnya di sini — nama ini yang tampil menggantikan nama penyedia di SPK, BAP, BAST, invoice, dan cetakan turunannya.'],
        ['nama' => 'ttd_penyedia', 'label' => 'Nama Penandatangan Penyedia', 'petunjuk' => 'Kosongkan = memakai Nama Penerima.'],
        ['nama' => 'gedung_id', 'label' => 'Gedung Tujuan', 'jenis' => 'pilihan', 'opsi' => $opsiGedungReg],
        ['nama' => 'paket', 'label' => 'Paket Pekerjaan'],
        ['nama' => 'tgl_mulai', 'label' => 'Tanggal Mulai', 'jenis' => 'tanggal'],
        ['nama' => 'tgl_selesai', 'label' => 'Tanggal Selesai', 'jenis' => 'tanggal'],
        ['nama' => 'status', 'label' => 'Status', 'jenis' => 'pilihan', 'opsi' => ['Aktif' => 'Aktif', 'Selesai' => 'Selesai', 'Batal' => 'Batal']],
    ],
    'tombol' => ['simpan_teks' => 'Simpan', 'simpan_teks_ubah' => 'Simpan Perubahan'],
]) ?>
<script type="application/json" id="data-spk"><?= json_encode(array_column($spk ?? [], null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
(function () {
  var dataSpk = {};
  try { dataSpk = JSON.parse(document.getElementById('data-spk').textContent || '{}'); } catch (e) {}
  var formSpk = function () { return document.querySelector('#modal-spk [data-fm-form]'); };
  var sinkronPilihan = function () {
    if (!window.perbaruiSelectCari) return;
    formSpk().querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
  };
  window.bukaSpkBaru = function () {
    formSpk().action = '<?= base_url('spk/simpan') ?>';
    window.bukaFormModal('modal-spk', { nomor: <?= (int) ($nomor_baru ?? 1) ?>, status: 'Aktif' });
    sinkronPilihan();
  };
  window.bukaSpkUbah = function (id) {
    var s = dataSpk[id];
    if (!s) return;
    formSpk().action = '<?= base_url('spk/perbarui') ?>/' + id;
    window.bukaFormModal('modal-spk', {
      id: id, nomor: s.nomor, tanggal: s.tanggal || '', penyedia_id: s.penyedia_id || '',
      penerima_manual: s.penerima_manual || '', ttd_penyedia: s.ttd_penyedia || '',
      gedung_id: s.gedung_id || '', paket: s.paket || '',
      tgl_mulai: s.tgl_mulai || '', tgl_selesai: s.tgl_selesai || '', status: s.status || 'Aktif'
    });
    sinkronPilihan();
  };
})();
</script>
