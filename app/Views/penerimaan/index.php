<div class="panel">
  <h2>Penerimaan Dana Bantuan Revitalisasi — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Menurut Juknis Revitalisasi SMP 2026, dana bantuan dicairkan bertahap: <b>Tahap 1 sebesar 70%</b> dan <b>Tahap 2 sebesar 30%</b> setelah kemajuan penyelesaian pekerjaan mencapai minimal 50% (dibuktikan Laporan Kemajuan + kuitansi penerimaan uang). Setiap penerimaan tercatat di sini dan otomatis masuk Buku Kas.</p>
  <?php
  $statusTahap2 = $terima2 >= $target2 && $pagu > 0 ? 'Lengkap' : ($terima2 > 0 ? 'Sebagian' : ($fisik >= 50 ? 'Dapat diajukan — kemajuan fisik ' . number_format($fisik, 1, ',', '.') . '% sudah ≥ 50%' : 'Belum dapat diajukan — kemajuan fisik ' . number_format($fisik, 1, ',', '.') . '% (syarat ≥ 50%)'));
  $barisTahap = [
      ['sel' => [
          'Tahap 1 (70%)',
          ['teks' => rp($target1), 'angka' => true],
          ['teks' => rp($terima1), 'angka' => true],
          ['teks' => rp(max(0, $target1 - $terima1)), 'angka' => true],
          $terima1 >= $target1 && $pagu > 0 ? 'Lengkap' : ($terima1 > 0 ? 'Sebagian' : 'Belum diterima'),
      ]],
      ['sel' => [
          'Tahap 2 (30%)',
          ['teks' => rp($target2), 'angka' => true],
          ['teks' => rp($terima2), 'angka' => true],
          ['teks' => rp(max(0, $target2 - $terima2)), 'angka' => true],
          $statusTahap2,
      ]],
  ];
  if ($terimaLain > 0) {
      $barisTahap[] = ['sel' => [
          'Penerimaan lain (tanpa tahap)',
          ['teks' => '—', 'angka' => true],
          ['teks' => rp($terimaLain), 'angka' => true],
          '',
          '',
      ]];
  }
  $barisTahap[] = ['sel' => [
      ['html' => '<b>Total bantuan (pagu)</b>'],
      ['html' => '<b>' . rp($pagu) . '</b>', 'angka' => true],
      ['html' => '<b>' . rp($terima1 + $terima2 + $terimaLain) . '</b>', 'angka' => true],
      ['html' => '<b>' . rp(max(0, $pagu - $terima1 - $terima2 - $terimaLain)) . '</b>', 'angka' => true],
      '',
  ]];
  echo tabel_bantu([
      'kolom' => [
          ['judul' => 'Tahap'],
          ['judul' => 'Target', 'angka' => true],
          ['judul' => 'Sudah Diterima', 'angka' => true],
          ['judul' => 'Kekurangan', 'angka' => true],
          ['judul' => 'Status'],
      ],
      'baris' => $barisTahap,
  ]);
  ?>
  <?php if ($pagu <= 0): ?><p class="kecil-teks">Pagu (total anggaran) belum diisi — atur di Identitas &amp; Pengaturan agar target per tahap terhitung.</p><?php endif; ?>
</div>

<div class="panel">
  <h2>Catat Penerimaan Dana</h2>
  <button type="button" class="tombol" onclick="bukaTerimaBaru()">+ Catat Penerimaan</button>
</div>

<?php if ($daftar !== []): ?>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-terima','ids-terima')">
  <h2>Riwayat Penerimaan</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-terima" method="post" action="<?= base_url('penerimaan-dana/hapus-massal') ?>" data-konfirmasi-judul="Hapus Catatan Penerimaan Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-terima"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} catatan penerimaan terpilih? Catatan itu ikut hilang dari Buku Kas.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('penerimaan-dana/kosongkan') ?>" data-konfirmasi="Kosongkan catatan penerimaan sekolah ini? Semua <?= count($daftar) ?> catatan penerimaan akan dihapus (hapus lunak) dan ikut hilang dari Buku Kas." data-konfirmasi-judul="Kosongkan Catatan Penerimaan" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Catatan Penerimaan</button>
    </form>
  </div>
  <?php
  $barisTerima = [];
  foreach ($daftar as $d) {
      $barisTerima[] = ['sel' => [
          ['html' => '<input type="checkbox" data-pilih value="' . (int) $d['id'] . '" x-model="terpilih">'],
          (string) tgl($d['tanggal']),
          $d['tahap'] ? 'Tahap ' . (int) $d['tahap'] : '—',
          ['teks' => rp($d['jumlah']), 'angka' => true],
          (string) ($d['keterangan'] ?? ''),
          ['html' => '<a class="tombol kecil abu" href="' . base_url('penerimaan-dana/kuitansi/' . $d['id']) . '" target="_blank">Cetak Kuitansi</a>'],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari penerimaan…',
      'paginasi' => 25,
      'kolom' => [
          ['judul_html' => '<input type="checkbox" title="Pilih semua catatan" @change="pilihSemua($event)">', 'lebar' => '34px'],
          ['judul' => 'Tanggal'],
          ['judul' => 'Tahap'],
          ['judul' => 'Jumlah', 'angka' => true],
          ['judul' => 'Keterangan'],
          ['judul' => ''],
      ],
      'baris' => $barisTerima,
  ]);
  ?>
</div>
<?php endif; ?>

<?= form_modal([
    'id' => 'modal-terima',
    'judul' => 'Catat Penerimaan Dana',
    'aksi' => base_url('penerimaan-dana/simpan'),
    'lebar' => 720,
    'kolom' => [
        ['nama' => 'tanggal', 'label' => 'Tanggal Terima', 'jenis' => 'tanggal', 'wajib' => true],
        ['nama' => 'tahap', 'label' => 'Tahap', 'jenis' => 'pilihan', 'opsi' => ['1' => 'Tahap 1 (70%)', '2' => 'Tahap 2 (30%)']],
        ['nama' => 'jumlah', 'label' => 'Jumlah Diterima (Rp)', 'jenis' => 'uang', 'wajib' => true, 'placeholder' => '437500000'],
        ['nama' => 'kanal', 'label' => 'Kanal', 'jenis' => 'pilihan', 'opsi' => ['bank' => 'Bank (rekening sekolah)', 'tunai' => 'Tunai']],
        ['nama' => 'keterangan', 'label' => 'Keterangan', 'jenis' => 'teks', 'penuh' => true, 'placeholder' => 'cth. Transfer dari KPPN, SP2D No. ...'],
    ],
    'tombol' => ['hapus' => false, 'simpan_teks' => 'Catat Penerimaan'],
]) ?>
<script>
// Jembatan modal penerimaan: tanggal bawaan hari ini (seperti form lama),
// lalu sinkronkan widget select-cari sesudah modal dibuka.
function bukaTerimaBaru() {
  window.bukaFormModal('modal-terima', { tanggal: <?= json_encode(date('Y-m-d')) ?> });
  var m = document.getElementById('modal-terima');
  if (m && window.perbaruiSelectCari) {
    m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
  }
}
</script>
