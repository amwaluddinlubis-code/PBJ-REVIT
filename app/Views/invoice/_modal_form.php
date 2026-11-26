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
        ['nama' => 'spk_id', 'label' => 'SPK', 'jenis' => 'pilihan', 'wajib' => true, 'opsi' => $opsiSpk, 'petunjuk' => 'Tanggal SPK, BAP & BAST diwarisi otomatis dari SPK'],
        ['nama' => 'nilai', 'label' => 'Nilai Invoice', 'jenis' => 'uang', 'petunjuk' => 'Kosongkan = total SPK'],
        ['nama' => 'status', 'label' => 'Status', 'jenis' => 'pilihan', 'opsi' => ['Belum Bayar' => 'Belum Bayar', 'Sudah Bayar' => 'Sudah Bayar']],
    ],
    'catatan_html' => '<div id="info-warisan-invoice" class="kecil-teks" style="margin-top:10px">Pilih SPK untuk melihat tanggal SPK, BAP, dan BAST yang diwarisi.</div>',
    'tombol' => ['simpan_teks' => 'Simpan', 'hapus_teks' => 'Hapus Invoice'],
]) ?>
<script type="application/json" id="data-invoice"><?= json_encode(array_column($inv ?? [], null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script type="application/json" id="data-warisan-spk"><?= json_encode($spk['warisan'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
// Jembatan modal Invoice: mengoper data baris ke bukaFormModal,
// menyinkronkan widget select-cari, dan menampilkan warisan SPK
// (tanggal SPK + BAP/BAST terakhir beserta tanggalnya) — BAP/BAST
// tidak lagi dipilih pengguna; server mewarisinya dari SPK.
(function () {
  var dataInv = {};
  try { dataInv = JSON.parse(document.getElementById('data-invoice').textContent || '{}'); } catch (e) {}
  var warisan = {};
  try { warisan = JSON.parse(document.getElementById('data-warisan-spk').textContent || '{}'); } catch (e) {}
  function tglIndo(iso) {
    if (!iso) return '—';
    var p = String(iso).split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
  }
  function tampilWarisan() {
    var m = document.getElementById('modal-invoice');
    var kotak = document.getElementById('info-warisan-invoice');
    if (!m || !kotak) return;
    var sel = m.querySelector('select[name="spk_id"]');
    var w = sel ? warisan[sel.value] : null;
    if (!w) {
      kotak.textContent = 'Pilih SPK untuk melihat tanggal SPK, BAP, dan BAST yang diwarisi.';
      return;
    }
    var bap = w.bap ? 'BAP urut ' + w.bap.nomor_urut + ' tgl ' + tglIndo(w.bap.tanggal) : 'BAP belum ada';
    var bast = w.bast ? 'BAST urut ' + w.bast.nomor_urut + ' tgl ' + tglIndo(w.bast.tanggal) : 'BAST belum ada';
    kotak.textContent = 'Diwarisi dari SPK: tgl SPK ' + tglIndo(w.tanggal) + ' · ' + bap + ' · ' + bast + '.';
  }
  function sinkronSelect() {
    var m = document.getElementById('modal-invoice');
    if (m && window.perbaruiSelectCari) {
      m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
    }
    tampilWarisan();
  }
  document.addEventListener('change', function (e) {
    if (e.target && e.target.name === 'spk_id' && e.target.closest('#modal-invoice')) tampilWarisan();
  });
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
      nilai: i.nilai == null ? '' : i.nilai,
      status: i.status || 'Belum Bayar'
    });
    sinkronSelect();
  };
})();
</script>
