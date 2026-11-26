<div class="panel" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
  <form method="get" action="<?= base_url('upah/cetak-daftar') ?>" target="_blank" style="display:flex;gap:8px;align-items:end">
    <label>Bulan daftar upah <input type="month" name="bulan" value="<?= date('Y-m') ?>"></label>
    <button type="submit" class="kecil">Cetak Daftar Pembayaran Upah</button>
  </form>
  <span class="kecil-teks">Format dokumen pembayaran upah Juknis — lengkap dengan kolom tanda tangan penerima.</span>
</div>
<details class="panel panel-lipat" data-lipat-id="upah-catat-pembayaran-upah" open>
  <summary>Catat Pembayaran Upah</summary>

  <button type="button" class="tombol" onclick="bukaUpahBaru()">+ Catat Pembayaran</button>
  <p class="kecil-teks">Jumlah bayar = orang-hari × tarif, dihitung otomatis. Pembayaran upah ikut terhitung dalam kontrol pagu total anggaran. PPh 21 mengikuti Juknis Revitalisasi: upah harian sampai dengan Rp450.000 per hari tidak dipotong; di atasnya dipotong 5% dari kelebihan per hari (batas kumulatif Rp4.500.000 dalam 22 hari kerja per bulan untuk penerima yang sama perlu dicek manual).</p>
</details>

<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-upah','ids-upahbayar')">
  <h2>Riwayat Pembayaran Upah — Total <?= rp($total) ?> · PPh 21 <?= rp($total_pph21) ?> · Bersih <?= rp($total - $total_pph21) ?></h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-upah" method="post" action="<?= base_url('upah/hapus-massal') ?>" data-konfirmasi-judul="Hapus Catatan Upah Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-upahbayar"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} catatan pembayaran upah terpilih? Total bayar dan kontrol pagu akan berubah.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('upah/kosongkan') ?>" data-konfirmasi="Kosongkan riwayat pembayaran upah sekolah ini? Semua <?= count($daftar) ?> catatan akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Riwayat Upah" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Riwayat Upah</button>
    </form>
  </div>
  <?php
  // Angka riwayat upah tampil polos tanpa awalan Rp (persis seperti sebelumnya).
  $barisUpah = [];
  foreach ($daftar as $u) {
      $barisUpah[] = ['sel' => [
          ['html' => '<input type="checkbox" data-pilih value="' . (int) $u['id'] . '" x-model="terpilih">'],
          (string) tgl($u['tanggal']),
          ['html' => esc($u['nama_gedung'] ?? '—') . ($u['nama_pekerjaan'] ? '<br><span class="kecil-teks">' . esc($u['nama_pekerjaan']) . '</span>' : '')],
          (string) $u['jenis_tenaga'],
          (string) ($u['penerima'] ?? '—'),
          ['teks' => number_format((float) $u['orang_hari'], 2, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $u['tarif'], 0, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $u['jumlah'], 0, ',', '.'), 'angka' => true],
          ['teks' => (float) $u['pph21'] > 0 ? number_format((float) $u['pph21'], 0, ',', '.') : '—', 'angka' => true],
          ['teks' => number_format((float) $u['bersih'], 0, ',', '.'), 'angka' => true],
          ['html' => '<form class="inline" method="post" action="' . base_url('upah/' . $u['id'] . '/hapus') . '" data-konfirmasi="Hapus catatan ini?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>'],
      ]];
  }
  if ($daftar === []) {
      $barisUpah[] = ['sel' => [['teks' => 'Belum ada pembayaran upah tercatat.', 'colspan' => 11]]];
  }
  echo tabel_bantu([
      'cari' => 'Cari pembayaran upah…',
      'paginasi' => 25,
      'kolom' => [
          ['judul_html' => '<input type="checkbox" title="Pilih semua catatan" @change="pilihSemua($event)">', 'lebar' => '34px'],
          ['judul' => 'Tanggal'],
          ['judul' => 'Gedung'],
          ['judul' => 'Jenis Tenaga'],
          ['judul' => 'Penerima'],
          ['judul' => 'OH', 'angka' => true],
          ['judul' => 'Tarif', 'angka' => true],
          ['judul' => 'Jumlah', 'angka' => true],
          ['judul' => 'PPh 21', 'angka' => true],
          ['judul' => 'Bersih', 'angka' => true],
          ['judul' => 'Aksi'],
      ],
      'baris' => $barisUpah,
  ]);
  ?>
</div>

<details class="panel panel-lipat" data-lipat-id="upah-master-tarif-upah-per-oh">
  <summary>Master Tarif Upah (per OH)</summary>

  <?php
  // Form ubah tarif tetap sebaris per baris (aksi per id upah/tarif/{id});
  // tabelnya saja yang memakai helper.
  $barisMaster = [];
  foreach ($master as $m) {
      $barisMaster[] = ['sel' => [
          (string) $m['jenis'],
          ['html' => '<form method="post" action="' . base_url('upah/tarif/' . $m['id']) . '" style="display:flex;gap:6px">'
              . csrf_field()
              . '<input type="text" inputmode="numeric" data-uang name="tarif" value="' . (int) round((float) $m['tarif']) . '" style="margin:0">'
              . '<button class="kecil" type="submit">Simpan</button></form>'],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari jenis tenaga…',
      'kolom' => [
          ['judul' => 'Jenis Tenaga'],
          ['judul' => 'Tarif (Rp)', 'lebar' => '300px'],
      ],
      'baris' => $barisMaster,
  ]);
  ?>
  <?php
  $jenisMaster = array_map(static fn ($m) => (string) $m['jenis'], $master);
  $kanonKurang = array_values(array_filter(['Pekerja', 'Tukang'], static fn ($k) => ! in_array($k, $jenisMaster, true)));
  $bukanKanon  = array_values(array_filter($master, static fn ($m) => ! in_array((string) $m['jenis'], ['Pekerja', 'Tukang'], true)));
  ?>
  <?php if ($kanonKurang !== []): ?>
  <div style="margin-top:12px"><button type="button" class="tombol" onclick="bukaMasterBaru()">+ Tambah ke Master</button></div>
  <?php endif; ?>
  <?php if ($bukanKanon !== []): ?>
  <p class="kecil-teks">Master upah hanya mengenal <strong>Pekerja</strong> dan <strong>Tukang</strong>. Masih ada <?= count($bukanKanon) ?> jenis lain (<?= esc(implode(', ', array_column($bukanKanon, 'jenis'))) ?>) — rapikan lewat tombol <strong>Rapikan ke 2 Jenis</strong> di <a href="<?= base_url('bahan') ?>">Master Bahan &amp; Upah</a>.</p>
  <?php else: ?>
  <p class="kecil-teks">Master upah hanya mengenal dua jenis kanonik: <strong>Pekerja</strong> dan <strong>Tukang</strong>, dengan tarif per OH. Tarif bawaan berasal dari daftar upah dokumen perencana (berbasis UMK); ubah pada kolom Tarif di tabel atau lewat tombol <strong>Rapikan ke 2 Jenis</strong> di <a href="<?= base_url('bahan') ?>">Master Bahan &amp; Upah</a> bila masih ada varian lain.</p>
  <?php endif; ?>
</details>

<?php
$opsiGedung = ['' => '— umum —'];
foreach ($semuaGedung ?? [] as $gd) {
    $opsiGedung[(int) $gd['id']] = (string) $gd['nama'];
}
?>
<?= form_modal([
    'id' => 'modal-upah',
    'judul' => 'Catat Pembayaran Upah',
    'aksi' => base_url('upah/simpan'),
    'lebar' => 720,
    'kolom' => [
        ['nama' => 'tanggal', 'label' => 'Tanggal', 'jenis' => 'tanggal', 'wajib' => true],
        ['nama' => 'gedung_id', 'label' => 'Gedung', 'jenis' => 'pilihan', 'opsi' => $opsiGedung],
        ['nama' => 'pekerjaan_id', 'label' => 'Pekerjaan (opsional)', 'jenis' => 'pilihan', 'opsi' => ['' => '— tidak tertaut pekerjaan —']],
        ['nama' => 'jenis_tenaga', 'label' => 'Jenis tenaga', 'jenis' => 'teks', 'wajib' => true, 'placeholder' => 'mis. Pekerja / Tukang', 'saran' => array_map(static fn ($m) => (string) $m['jenis'], $master)],
        ['nama' => 'penerima', 'label' => 'Nama penerima', 'jenis' => 'teks', 'placeholder' => 'mis. Sarmin'],
        ['nama' => 'orang_hari', 'label' => 'Orang-hari (OH)', 'jenis' => 'angka', 'wajib' => true, 'placeholder' => 'mis. 12,5'],
        ['nama' => 'tarif', 'label' => 'Tarif per OH (Rp)', 'jenis' => 'uang', 'placeholder' => 'mis. 93578', 'petunjuk' => 'Kosongkan untuk tarif master'],
        ['nama' => 'keterangan', 'label' => 'Keterangan', 'jenis' => 'teks', 'placeholder' => 'mis. Upah pasang pondasi minggu ke-3'],
    ],
    'tombol' => ['hapus' => false, 'simpan_teks' => 'Catat Pembayaran'],
]) ?>
<?php if ($kanonKurang !== []): ?>
<?= form_modal([
    'id' => 'modal-upah-master',
    'judul' => 'Tambah ke Master Upah',
    'aksi' => base_url('upah/master'),
    'kolom' => [
        ['nama' => 'jenis', 'label' => 'Jenis tenaga', 'jenis' => 'pilihan', 'wajib' => true, 'opsi' => array_combine($kanonKurang, $kanonKurang)],
        ['nama' => 'tarif', 'label' => 'Tarif per OH (Rp)', 'jenis' => 'uang', 'placeholder' => 'mis. 93578'],
    ],
    'tombol' => ['hapus' => false, 'simpan_teks' => 'Tambah ke Master'],
]) ?>
<?php endif; ?>
<script>
// Jembatan modal upah: pilihan pekerjaan mengikuti gedung terpilih
// (peta pekerjaanPerGedung dari server), tanggal bawaan mengikuti
// Bulan Bekerja, dan widget select-cari disinkronkan sesudah buka.
const pekerjaanPerGedung = <?= json_encode($pekerjaan_per_gedung) ?>;
function segarkanPekerjaan() {
  var selGedung = document.getElementById('modal-upah-gedung_id');
  var selPekerjaan = document.getElementById('modal-upah-pekerjaan_id');
  if (!selGedung || !selPekerjaan) return;
  const daftar = pekerjaanPerGedung[selGedung.value] || [];
  selPekerjaan.innerHTML = '<option value="">— tidak tertaut pekerjaan —</option>' +
    daftar.map(p => `<option value="${p.id}">${p.nama}</option>`).join('');
  if (window.perbaruiSelectCari) { window.perbaruiSelectCari(selPekerjaan); }
}
function sinkronSelectUpah(idModal) {
  var m = document.getElementById(idModal);
  if (m && window.perbaruiSelectCari) {
    m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
  }
}
function bukaUpahBaru() {
  window.bukaFormModal('modal-upah', { tanggal: <?= json_encode($tgl_bayar_default ?? date('Y-m-d')) ?> });
  segarkanPekerjaan();
  sinkronSelectUpah('modal-upah');
}
function bukaMasterBaru() {
  window.bukaFormModal('modal-upah-master');
  sinkronSelectUpah('modal-upah-master');
}
document.getElementById('modal-upah-gedung_id').addEventListener('change', segarkanPekerjaan);
</script>
