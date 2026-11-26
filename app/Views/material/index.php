<?php /* Buku Material: tabel daftar memakai helper tabel_bantu(); form
   Catat Barang Keluar memakai helper form_modal (simpan AJAX ke
   material/simpan). Barang masuk tercatat otomatis dari BAST. */ ?>
<?php $fmt = static fn ($n) => number_format((float) $n, 2, ',', '.'); ?>
<div class="panel">
  <h2>Catat Barang Keluar</h2>
  <p class="kecil-teks">Barang <strong>masuk tercatat otomatis dari BAST</strong> — tanggalnya mengikuti tanggal BAST — jadi di sini Bapak/Ibu hanya mencatat barang <strong>keluar</strong>. Pilih gedung tujuan dan bahan; petunjuk di bawah form menunjukkan kebutuhan gedung itu dan stok tersedia bahan ini, agar tidak salah catat. Pilihan bahan hanya menampilkan bahan yang masih berstok (penerimaan BAST dikurangi yang sudah keluar).</p>
  <?php if (! empty($bahan_pilih)): ?>
  <button type="button" class="tombol" onclick="bukaCatatKeluar()">+ Catat Barang Keluar</button>
  <?php else: ?>
  <p class="kecil-teks" style="margin:0">Belum ada bahan yang berstok. Pilihan bahan tampil sesudah ada SPK yang di-BAST — periksa di <a href="<?= base_url('dokumen') ?>">Dokumen</a>.</p>
  <?php endif; ?>
</div>
<div class="panel">
  <h2>Kebutuhan Bahan per Gedung</h2>
  <p class="kecil-teks">Kebutuhan dihitung dari BOQ tiap gedung (volume × koefisien analisa). Bandingkan dengan yang sudah keluar ke gedung itu sebelum mencatat. Baris merah = keluar ke gedung itu sudah melebihi kebutuhannya (atau bahan itu bukan kebutuhan gedung tersebut). Gedung diurutkan dari persentase sisa kebutuhan terbesar — yang paling belum terpenuhi tampil lebih dulu.</p>
  <?php if (! empty($semua_kebutuhan_gedung)): ?>
  <form method="get" action="<?= base_url('material') ?>" style="margin:0 0 10px">
    <label>Saring gedung
      <select name="f_gedung" onchange="this.form.submit()">
        <option value="0"<?= ($f_gedung ?? 0) === 0 ? ' selected' : '' ?>>— semua gedung —</option>
        <?php foreach ($semua_kebutuhan_gedung as $kg): ?>
        <option value="<?= (int) $kg['gedung']['id'] ?>"<?= ($f_gedung ?? 0) === (int) $kg['gedung']['id'] ? ' selected' : '' ?>><?= esc($kg['gedung']['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>
  <?php endif; ?>
  <?php if (! empty($kebutuhan_gedung)): ?>
  <?php
  $barisKebutuhan = [];
  foreach ($kebutuhan_gedung as $kg) {
      $judulGrup = '<strong>' . esc($kg['gedung']['nama']) . '</strong>'
          . (($kg['persen_sisa'] ?? -1) >= 0 ? ' <span class="kecil-teks">· sisa kebutuhan ' . number_format((float) $kg['persen_sisa'], 1, ',', '.') . '%</span>' : '');
      $barisKebutuhan[] = ['grup_html' => $judulGrup];
      foreach ($kg['baris'] as $r) {
          $barisKebutuhan[] = [
              'class' => $r['sisa'] < -0.0005 ? 'negatif' : '',
              'sel' => [
                  $r['kode'] . ' — ' . $r['nama'] . ' (' . ($r['satuan'] ?? '') . ')',
                  ['teks' => $fmt($r['butuh']), 'angka' => true],
                  ['teks' => $fmt($r['keluar']), 'angka' => true],
                  ['teks' => $fmt($r['sisa']), 'angka' => true],
              ],
          ];
      }
  }
  echo tabel_bantu([
      'cari' => 'Cari gedung / bahan…',
      'kolom' => [
          ['judul' => 'Bahan'],
          ['judul' => 'Kebutuhan', 'angka' => true],
          ['judul' => 'Sudah Keluar', 'angka' => true],
          ['judul' => 'Sisa Kebutuhan', 'angka' => true],
      ],
      'baris' => $barisKebutuhan,
  ]);
  ?>
  <?php else: ?>
  <p class="kecil-teks" style="margin:0">Belum ada kebutuhan bahan terhitung — kebutuhan berasal dari BOQ pada halaman <a href="<?= base_url('gedung') ?>">Gedung</a>.</p>
  <?php endif; ?>
</div>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-material','ids-material')">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-material" method="post" action="<?= base_url('material/hapus-massal') ?>" data-konfirmasi-judul="Hapus Baris Buku Material Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-material"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} baris buku material terpilih? Sisa stok akan berubah.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('material/kosongkan') ?>" data-konfirmasi="Kosongkan buku material sekolah ini? Semua catatan KELUAR akan dihapus (hapus lunak); catatan masuk dari BAST tidak terpengaruh dan stok kembali sebesar penerimaan BAST." data-konfirmasi-judul="Kosongkan Buku Material" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Buku Material</button>
    </form>
  </div>
  <p class="kecil-teks" style="margin:0 0 10px">Baris bertanda <strong>Otomatis</strong> berasal dari BAST (tanggal = tanggal BAST) dan tidak dapat dihapus dari sini. Catatan masuk manual lama tidak lagi ditampilkan dan tidak dihitung — stok murni penerimaan BAST dikurangi catatan keluar.</p>
  <?php
  $barisBuku = [];
  foreach ($baris as $r) {
      $barisBuku[] = ['sel' => [
          ['html' => ! empty($r['otomatis'])
              ? '<span class="kecil-teks">Otomatis</span>'
              : '<input type="checkbox" data-pilih value="' . (int) $r['id'] . '" x-model="terpilih">'],
          tgl($r['tanggal'] ?? null),
          (string) ($r['no_bpb'] ?? ''),
          $r['kode'] . ' — ' . $r['nama'] . ' (' . ($r['satuan'] ?? '') . ')',
          (string) ($r['gedung_nama'] ?? '—'),
          ['teks' => $fmt($r['masuk']), 'angka' => true],
          ['teks' => $fmt($r['keluar']), 'angka' => true],
          ['teks' => $fmt($r['sisa']), 'angka' => true],
          (string) ($r['keterangan'] ?? ''),
          ['html' => empty($r['otomatis'])
              ? '<form class="inline" method="post" action="' . base_url('material/hapus/' . $r['id']) . '" data-konfirmasi="Hapus baris ini?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>'
              : ''],
      ]];
  }
  if (! $baris) {
      $barisBuku[] = ['sel' => [['teks' => 'Belum ada catatan — catatan masuk muncul otomatis sesudah ada BAST, catatan keluar dicatat dari form di atas.', 'colspan' => 10]]];
  }
  echo tabel_bantu([
      'cari' => 'Cari catatan material…',
      'paginasi' => 10,
      'kolom' => [
          ['judul_html' => '<input type="checkbox" title="Pilih semua baris" @change="pilihSemua($event)">', 'lebar' => '34px'],
          ['judul' => 'Tanggal'],
          ['judul' => 'No. BPB'],
          ['judul' => 'Bahan'],
          ['judul' => 'Gedung'],
          ['judul' => 'Masuk', 'angka' => true],
          ['judul' => 'Keluar', 'angka' => true],
          ['judul' => 'Sisa Stok', 'angka' => true],
          ['judul' => 'Keterangan'],
          ['judul' => ''],
      ],
      'baris' => $barisBuku,
  ]);
  ?>
</div>
<?php if (! empty($rekap_gedung)): ?>
<div class="panel">
  <h2>Rekap Keluar per Gedung</h2>
  <p class="kecil-teks">Hanya dari catatan keluar yang gedung tujuannya diisi — menunjukkan pemakaian tiap gedung, mis. bahan dari satu SPK gabungan yang dipakai masing-masing gedung. Stok utama tetap gabungan se-sekolah (penerimaan BAST dikurangi total keluar).</p>
  <?php
  $barisRekap = [];
  foreach ($rekap_gedung as $r) {
      $barisRekap[] = ['sel' => [
          ['html' => '<strong>' . esc($r['gedung_nama']) . '</strong>'],
          $r['kode'] . ' — ' . $r['nama'] . ' (' . ($r['satuan'] ?? '') . ')',
          ['teks' => $fmt($r['keluar']), 'angka' => true],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari gedung / bahan…',
      'kolom' => [
          ['judul' => 'Gedung'],
          ['judul' => 'Bahan'],
          ['judul' => 'Sudah Keluar', 'angka' => true],
      ],
      'baris' => $barisRekap,
  ]);
  ?>
</div>
<?php endif; ?>

<?php if (! empty($bahan_pilih)): ?>
<?php
$opsiGedung = ['' => '— gudang bersama / belum ditentukan —'];
foreach ($gedung ?? [] as $g) {
    $opsiGedung[(int) $g['id']] = (string) $g['nama'];
}
$opsiBahan = ['' => '— pilih bahan —'];
foreach ($bahan_pilih as $b) {
    $opsiBahan[(int) $b['id']] = [
        'label' => $b['kode'] . ' — ' . $b['nama'] . ' — sisa ' . number_format((float) $b['stok'], 2, ',', '.') . ' ' . ($b['satuan'] ?? ''),
        'data' => ['stok' => (float) $b['stok'], 'satuan' => (string) ($b['satuan'] ?? '')],
    ];
}
?>
<?= form_modal([
    'id' => 'modal-material',
    'judul' => 'Catat Barang Keluar',
    'aksi' => base_url('material/simpan'),
    'kolom' => [
        ['nama' => 'tanggal', 'label' => 'Tanggal', 'jenis' => 'tanggal'],
        ['nama' => 'no_bpb', 'label' => 'No. BPB', 'jenis' => 'teks'],
        ['nama' => 'gedung_id', 'label' => 'Gedung Tujuan', 'jenis' => 'pilihan', 'opsi' => $opsiGedung],
        ['nama' => 'bahan_id', 'label' => 'Bahan', 'jenis' => 'pilihan', 'wajib' => true, 'opsi' => $opsiBahan],
        ['nama' => 'keluar', 'label' => 'Jumlah Keluar', 'jenis' => 'angka'],
        ['nama' => 'keterangan', 'label' => 'Keterangan', 'jenis' => 'teks'],
    ],
    'catatan_html' => '<p class="kecil-teks" id="info-catatan" hidden style="margin:12px 0 0"></p>',
    'tombol' => ['simpan_teks' => 'Catat', 'hapus' => false],
]) ?>
<?php endif; ?>

<script>
// Jembatan modal Catat Barang Keluar: membuka form_modal dan mengisi
// petunjuk stok/kebutuhan dari peta JSON halaman.
(function () {
  var peta = <?= json_encode($peta_info ?? [], JSON_NUMERIC_CHECK) ?>;
  var modal = document.getElementById('modal-material');
  if (!modal) return;
  var selG = modal.querySelector('select[name="gedung_id"]');
  var selB = modal.querySelector('select[name="bahan_id"]');
  var info = document.getElementById('info-catatan');
  if (!selB || !info) return;
  var fmt = function (n) { return Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 3 }); };
  function segarkan() {
    if (!selB.value) { info.hidden = true; return; }
    var op = selB.options[selB.selectedIndex];
    var teks = 'Stok tersedia bahan ini: ' + fmt(parseFloat(op.dataset.stok || '0')) + ' ' + (op.dataset.satuan || '') + ' (penerimaan BAST dikurangi yang sudah keluar).';
    if (selG && selG.value) {
      var namaG = selG.options[selG.selectedIndex].textContent;
      var e = (peta[selG.value] || {})[selB.value];
      if (e) {
        teks += ' Kebutuhan ' + namaG + ': ' + fmt(e.butuh) + ' ' + e.satuan + ' · sudah keluar ke gedung ini: ' + fmt(e.keluar) + ' · sisa kebutuhan: ' + fmt(e.sisa) + '.';
      } else {
        teks += ' Bahan ini TIDAK termasuk kebutuhan ' + namaG + ' — periksa lagi gedung tujuannya.';
      }
    }
    info.textContent = teks;
    info.hidden = false;
  }
  selB.addEventListener('change', segarkan);
  if (selG) selG.addEventListener('change', segarkan);
  window.bukaCatatKeluar = function () {
    window.bukaFormModal('modal-material', { keluar: 0 });
    if (window.perbaruiSelectCari) {
      modal.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
    }
    info.hidden = true;
  };
})();
</script>
