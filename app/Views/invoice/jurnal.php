<?php /* Jurnal Pembayaran: form catat pembayaran memakai helper form_modal
   (simpan AJAX ke Invoice::jurnalTambah; penjaga bendahara, Bulan
   Bekerja, dan kontrol pagu tetap di controller). Form hapus baris
   jurnal tetap POST biasa. Tabel dirender tabel_bantu. */ ?>
<div class="panel">
  <h2>Catat Pembayaran</h2>
  <button type="button" class="tombol" onclick="bukaJurnalBaru()">+ Catat Pembayaran</button>
  <span class="kecil-teks">Tanggal bayar bawaan merekomendasikan tanggal jurnal terakhir yang tercatat (dijepit ke Bulan Terbuka); hanya bendahara yang dapat mencatat pembayaran.</span>
</div>
<?php
$opsiBelum = ['' => '— pilih invoice —'];
foreach ($belum as $b) {
    $opsiBelum[(int) $b['id']] = [
        'label' => 'Invoice ' . (int) $b['nomor_urut'] . ' (SPK ' . (int) $b['spk_nomor'] . ') — ' . rp($b['nilai']),
        'data' => ['tanggal' => (string) ($b['tanggal'] ?? '')],
    ];
}
?>
<?= form_modal([
    'id' => 'modal-jurnal',
    'judul' => 'Catat Pembayaran',
    'aksi' => base_url('invoice/jurnal-tambah'),
    'kolom' => [
        ['nama' => 'invoice_id', 'label' => 'Invoice Belum Bayar', 'jenis' => 'pilihan', 'wajib' => true, 'opsi' => $opsiBelum],
        ['nama' => 'tanggal', 'label' => 'Tanggal Bayar', 'jenis' => 'tanggal', 'min' => $buku_min ?? '', 'max' => $buku_max ?? '', 'petunjuk' => 'Buku terbuka: ' . ($buku_label ?? '')],
        ['nama' => 'kanal', 'label' => 'Kanal Pembayaran', 'jenis' => 'pilihan', 'opsi' => ['tunai' => 'Tunai (Buku Kas Tunai)', 'bank' => 'Bank / Non-Tunai (Buku Bank)'], 'petunjuk' => 'Bawaan tunai — hanya pembayaran non-tunai yang masuk Buku Bank'],
        ['nama' => 'keterangan', 'label' => 'Keterangan', 'jenis' => 'teks'],
    ],
    'tombol' => ['simpan_teks' => 'Catat Bayar', 'hapus' => false],
]) ?>
<script>
// Jembatan modal Jurnal: tanggal bayar direkomendasikan dari jurnal
// terakhir tercatat — MAX(tanggal pembayaran terakhir, tanggal invoice
// terpilih), dijepit ke Bulan Terbuka; tanpa pembayaran sama sekali
// memakai bawaan Bulan Terbuka. Server tetap menegakkan penjaganya.
(function () {
  var tglTerakhir = '<?= esc($tgl_jurnal_terakhir ?? '', 'js') ?>';
  var tglBawaan = '<?= esc($tgl_bayar_default ?? date('Y-m-d'), 'js') ?>';
  var bukuMin = '<?= esc($buku_min ?? '', 'js') ?>';
  var bukuMax = '<?= esc($buku_max ?? '', 'js') ?>';
  function rekomendasi(tglInvoice) {
    var calon = tglTerakhir || '';
    if (tglInvoice && tglInvoice > calon) calon = tglInvoice;
    if (!calon) return tglBawaan;
    if (bukuMin && calon < bukuMin) return bukuMin;
    if (bukuMax && calon > bukuMax) return bukuMax;
    return calon;
  }
  function isiTanggal(iso) {
    var m = document.getElementById('modal-jurnal');
    if (!m || !iso) return;
    var inp = m.querySelector('input[name="tanggal"]');
    if (!inp) return;
    if (inp.classList.contains('isian-tanggal')) {
      var p = iso.split('-');
      inp.value = p[2] + '-' + p[1] + '-' + p[0];
    } else {
      inp.value = iso;
    }
    inp.dispatchEvent(new Event('input', { bubbles: true }));
    inp.dispatchEvent(new Event('change', { bubbles: true }));
  }
  window.bukaJurnalBaru = function () {
    window.bukaFormModal('modal-jurnal', { tanggal: rekomendasi('') });
    var m = document.getElementById('modal-jurnal');
    if (m && window.perbaruiSelectCari) {
      m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
    }
  };
  document.addEventListener('change', function (e) {
    if (!e.target || e.target.name !== 'invoice_id' || !e.target.closest || !e.target.closest('#modal-jurnal')) return;
    var opt = e.target.options[e.target.selectedIndex];
    isiTanggal(rekomendasi(opt ? (opt.getAttribute('data-tanggal') || '') : ''));
  });
})();
</script>
<div class="panel">
  <h2>Jurnal Pembayaran</h2>
  <?php
  $tot = 0;
  $barisJurnal = [];
  foreach ($baris as $r) {
      $tot += (float) $r['dibayar'];
      $selNilai = rp($r['nilai']) . ((float) $r['nilai'] >= 5000000 ? '<br><span class="kecil-teks">kuitansi ≥ Rp5 jt: materai Rp10.000</span>' : '');
      $selPph = rp($r['pph22']) . ((float) $r['pph22'] <= 0 && (float) $r['dpp'] > 0 ? '<br><span class="kecil-teks">bebas: DPP ≤ Rp2 jt</span>' : '');
      $barisJurnal[] = ['sel' => [
          (string) tgl($r['tanggal'] ?? null, '— (belum diisi)'),
          (int) $r['nomor_urut'] . ' (' . ($r['no_toko'] ?? '') . ')',
          (string) (int) $r['spk_nomor'],
          ['html' => $selNilai, 'angka' => true],
          ['teks' => rp($r['ppn']), 'angka' => true],
          ['teks' => rp($r['dpp']), 'angka' => true],
          ['html' => $selPph, 'angka' => true],
          ['teks' => rp($r['dibayar']), 'angka' => true],
          ['html' => '<span class="lencana ' . (($r['kanal'] ?? 'tunai') === 'bank' ? 'biru' : 'abu') . '">' . (($r['kanal'] ?? 'tunai') === 'bank' ? 'Bank' : 'Tunai') . '</span>'],
          (string) ($r['keterangan'] ?? ''),
          ['html' => '<form class="inline" method="post" action="' . base_url('invoice/jurnal-hapus/' . $r['id']) . '" data-konfirmasi="Hapus baris jurnal ini?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>'],
      ]];
  }
  $barisJurnal[] = ['total' => [
      ['teks' => 'TOTAL DIBAYAR', 'colspan' => 7, 'gaya' => 'text-align:right'],
      ['teks' => rp($tot), 'angka' => true],
      ['teks' => '', 'colspan' => 3],
  ], 'class' => 'total'];
  echo tabel_bantu([
      'cari' => 'Cari jurnal…',
      'paginasi' => 10,
      'kolom' => [
          ['judul' => 'Tanggal'],
          ['judul' => 'Invoice'],
          ['judul' => 'SPK'],
          ['judul' => 'Nilai Tagihan', 'angka' => true],
          ['judul' => 'PPN Dipungut', 'angka' => true],
          ['judul' => 'DPP', 'angka' => true],
          ['judul' => 'PPh 22 (1,5% dari DPP)', 'angka' => true],
          ['judul' => 'Nilai Dibayar', 'angka' => true],
          ['judul' => 'Kanal'],
          ['judul' => 'Keterangan'],
          ['judul' => ''],
      ],
      'baris' => $barisJurnal,
  ]);
  ?>
  <p class="kecil-teks">Menurut Juknis Revitalisasi SMP 2026: PPh 22 sebesar 1,5% dihitung dari DPP (nilai di luar PPN) dan tidak dipungut untuk pembelian sampai dengan Rp2.000.000; PPN 11% dipungut bendahara dari bagian harga yang kena PPN; nilai dibayar = nilai tagihan − PPN dipungut − PPh 22.</p>
</div>
