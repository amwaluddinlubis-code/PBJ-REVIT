<?php /* Partial invoice/index: modal form Invoice (baru & ubah) memakai
   helper form_modal — simpan AJAX ke invoice/simpan (id tersembunyi
   membedakan baru/ubah; invoice/perbarui/{id} tetap ada untuk halaman
   form mandiri invoice/form.php sebagai cadangan). Hapus lewat
   aksi_hapus form_modal (konfirmasi standar). Data baris dioper
   sebagai JSON di halaman (#data-invoice). */ ?>
<?php
$opsiSpk = ['' => '— pilih SPK —'];
foreach ($spk['list'] ?? [] as $s) {
    $opsiSpk[(int) $s['id']] = [
        'label' => 'SPK ' . (int) $s['nomor'] . ' — ' . ($s['paket'] ?? '') . ' (total Rp ' . number_format((float) $s['total'], 0, ',', '.') . ')',
        'data' => ['total' => (int) $s['total']],
    ];
}
$opsiBap = ['' => '— belum ada —'];
foreach ($spk['bap'] ?? [] as $sid => $daftar) {
    foreach ($daftar as $b) {
        $opsiBap[(int) $b['id']] = [
            'label' => 'BAP urut ' . (int) $b['nomor_urut'] . ' (SPK id ' . (int) $sid . ')',
            'data' => ['spk' => (int) $sid],
        ];
    }
}
$opsiBast = ['' => '— belum ada —'];
foreach ($spk['bast'] ?? [] as $sid => $daftar) {
    foreach ($daftar as $b) {
        $opsiBast[(int) $b['id']] = [
            'label' => 'BAST urut ' . (int) $b['nomor_urut'] . ' (SPK id ' . (int) $sid . ')',
            'data' => ['spk' => (int) $sid],
        ];
    }
}
?>
<?= form_modal([
    'id' => 'modal-invoice',
    'judul' => 'Invoice Baru',
    'judul_ubah' => 'Ubah Invoice',
    'aksi' => base_url('invoice/simpan'),
    'aksi_hapus' => base_url('invoice/hapus/__ID__'),
    'konfirmasi_hapus' => 'Hapus invoice ini?',
    'lebar' => 720,
    'kolom' => [
        ['nama' => 'nomor_urut', 'label' => 'Nomor Urut', 'jenis' => 'angka', 'wajib' => true],
        ['nama' => 'no_toko', 'label' => 'No. Invoice Toko', 'jenis' => 'teks'],
        ['nama' => 'tanggal', 'label' => 'Tanggal', 'jenis' => 'tanggal', 'min' => $buku_min ?? '', 'max' => $buku_max ?? '', 'petunjuk' => 'Buku terbuka: ' . ($buku_label ?? '')],
        ['nama' => 'spk_id', 'label' => 'SPK', 'jenis' => 'pilihan', 'wajib' => true, 'opsi' => $opsiSpk],
        ['nama' => 'bap_id', 'label' => 'BAP', 'jenis' => 'pilihan', 'opsi' => $opsiBap],
        ['nama' => 'bast_id', 'label' => 'BAST', 'jenis' => 'pilihan', 'opsi' => $opsiBast],
        ['nama' => 'nilai', 'label' => 'Nilai Invoice', 'jenis' => 'uang', 'petunjuk' => 'Kosongkan = total SPK'],
        ['nama' => 'status', 'label' => 'Status', 'jenis' => 'pilihan', 'opsi' => ['Belum Bayar' => 'Belum Bayar', 'Sudah Bayar' => 'Sudah Bayar']],
    ],
    'tombol' => ['simpan_teks' => 'Simpan', 'hapus_teks' => 'Hapus Invoice'],
]) ?>
<script type="application/json" id="data-invoice"><?= json_encode(array_column($inv ?? [], null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
// Jembatan modal Invoice: hanya mengoper data baris ke bukaFormModal dan
// menyinkronkan widget select-cari sesudahnya.
(function () {
  var dataInv = {};
  try { dataInv = JSON.parse(document.getElementById('data-invoice').textContent || '{}'); } catch (e) {}
  function sinkronSelect() {
    var m = document.getElementById('modal-invoice');
    if (m && window.perbaruiSelectCari) {
      m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
    }
  }
  window.bukaInvoiceBaru = function () {
    window.bukaFormModal('modal-invoice', { nomor_urut: <?= (int) ($nomor_baru ?? 1) ?>, status: 'Belum Bayar', tanggal: <?= json_encode($tgl_bayar_default ?? date('Y-m-d')) ?> });
    sinkronSelect();
  };
  window.bukaInvoiceUbah = function (id) {
    var i = dataInv[id];
    if (!i) return;
    window.bukaFormModal('modal-invoice', {
      id: i.id,
      nomor_urut: i.nomor_urut,
      no_toko: i.no_toko || '',
      tanggal: i.tanggal || '',
      spk_id: i.spk_id || '',
      bap_id: i.bap_id || '',
      bast_id: i.bast_id || '',
      nilai: i.nilai == null ? '' : i.nilai,
      status: i.status || 'Belum Bayar'
    });
    sinkronSelect();
  };
})();
</script>
