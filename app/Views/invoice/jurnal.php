<?php /* Jurnal Pembayaran: form catat pembayaran memakai helper form_modal
   (simpan AJAX ke Invoice::jurnalTambah; penjaga bendahara, Bulan
   Bekerja, dan kontrol pagu tetap di controller). Form hapus baris
   jurnal tetap POST biasa. Tabel dirender tabel_bantu. */ ?>
<div class="panel">
  <h2>Catat Pembayaran</h2>
  <button type="button" class="tombol" onclick="bukaJurnalBaru()">+ Catat Pembayaran</button>
  <span class="kecil-teks">Tanggal bayar bawaan mengikuti Bulan Terbuka; hanya bendahara yang dapat mencatat pembayaran.</span>
</div>
<?php
$opsiBelum = ['' => '— pilih invoice —'];
foreach ($belum as $b) {
    $opsiBelum[(int) $b['id']] = 'Invoice ' . (int) $b['nomor_urut'] . ' (SPK ' . (int) $b['spk_nomor'] . ') — ' . rp($b['nilai']);
}
?>
<?= form_modal([
    'id' => 'modal-jurnal',
    'judul' => 'Catat Pembayaran',
    'aksi' => base_url('invoice/jurnal-tambah'),
    'kolom' => [
        ['nama' => 'invoice_id', 'label' => 'Invoice Belum Bayar', 'jenis' => 'pilihan', 'wajib' => true, 'opsi' => $opsiBelum],
        ['nama' => 'tanggal', 'label' => 'Tanggal Bayar', 'jenis' => 'tanggal', 'min' => $buku_min ?? '', 'max' => $buku_max ?? '', 'petunjuk' => 'Buku terbuka: ' . ($buku_label ?? '')],
        ['nama' => 'keterangan', 'label' => 'Keterangan', 'jenis' => 'teks'],
    ],
    'tombol' => ['simpan_teks' => 'Catat Bayar', 'hapus' => false],
]) ?>
<script>
// Jembatan modal Jurnal: tanggal bawaan (Bulan Terbuka) dioper lewat data
// bukaFormModal; sesudahnya sinkronkan widget select-cari.
function bukaJurnalBaru() {
  window.bukaFormModal('modal-jurnal', { tanggal: '<?= esc($tgl_bayar_default ?? date('Y-m-d'), 'js') ?>' });
  var m = document.getElementById('modal-jurnal');
  if (m && window.perbaruiSelectCari) {
    m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
  }
}
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
          (string) ($r['keterangan'] ?? ''),
          ['html' => '<form class="inline" method="post" action="' . base_url('invoice/jurnal-hapus/' . $r['id']) . '" data-konfirmasi="Hapus baris jurnal ini?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>'],
      ]];
  }
  $barisJurnal[] = ['total' => [
      ['teks' => 'TOTAL DIBAYAR', 'colspan' => 7, 'gaya' => 'text-align:right'],
      ['teks' => rp($tot), 'angka' => true],
      ['teks' => '', 'colspan' => 2],
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
          ['judul' => 'Keterangan'],
          ['judul' => ''],
      ],
      'baris' => $barisJurnal,
  ]);
  ?>
  <p class="kecil-teks">Menurut Juknis Revitalisasi SMP 2026: PPh 22 sebesar 1,5% dihitung dari DPP (nilai di luar PPN) dan tidak dipungut untuk pembelian sampai dengan Rp2.000.000; PPN 11% dipungut bendahara dari bagian harga yang kena PPN; nilai dibayar = nilai tagihan − PPN dipungut − PPh 22.</p>
</div>
