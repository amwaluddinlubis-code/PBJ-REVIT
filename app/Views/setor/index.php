<?php $label = ['pph22' => 'PPh Pasal 22', 'ppn' => 'PPN dipungut', 'pph21' => 'PPh Pasal 21', 'pph23' => 'PPh Pasal 23', 'pph42' => 'PPh Pasal 4 ayat 2', 'sisa_dana' => 'Pengembalian sisa dana (SIMPONI)']; ?>
<details class="panel panel-lipat" data-lipat-id="setor-kewajiban-pajak-per-masa" open>
  <summary>Kewajiban Pajak per Masa — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></summary>

  <p style="margin-top:0;color:var(--ta-teks-2)">Pajak yang dipungut/dipotong dari pembayaran invoice (PPh 22 &amp; PPN), pembayaran upah (PPh 21), dan biaya non-gedung (PPh 23 / PPh 4 ayat 2 / PPh 21 honor) wajib disetor ke kas negara dengan kode e-billing; bukti sahnya adalah NTPN. Tabel ini membandingkan kewajiban dengan bukti setor yang sudah dicatat.</p>
  <?php
  $barisWajib = [];
  foreach ($baris as $b) {
      $barisWajib[] = ['sel' => [
          (string) ($label[$b['jenis']] ?? $b['jenis']),
          (string) $b['masa'],
          ['teks' => rp($b['wajib']), 'angka' => true],
          ['teks' => rp($b['disetor']), 'angka' => true],
          ['teks' => rp(max(0, $b['kurang'])), 'angka' => true],
          (string) $b['status'],
      ]];
  }
  if ($baris === []) {
      $barisWajib[] = ['sel' => [['teks' => 'Belum ada kewajiban pajak dari transaksi yang tercatat.', 'colspan' => 6]]];
  }
  echo tabel_bantu([
      'cari' => 'Cari pajak / masa…',
      'kolom' => [
          ['judul' => 'Jenis Pajak'],
          ['judul' => 'Masa'],
          ['judul' => 'Kewajiban', 'angka' => true],
          ['judul' => 'Sudah Disetor', 'angka' => true],
          ['judul' => 'Kekurangan', 'angka' => true],
          ['judul' => 'Status'],
      ],
      'baris' => $barisWajib,
  ]);
  ?>
</details>

<details class="panel panel-lipat" data-lipat-id="setor-sisa-dana-bantuan">
  <summary>Sisa Dana Bantuan</summary>

  <?php /* Tabel ringkasan 2 kolom tanpa kepala kolom — dipertahankan manual
     (bukan tabel daftar; tabel_bantu selalu merender baris kepala). */ ?>
  <table>
    <tr><td style="width:300px">Sisa dana (diterima − digunakan)</td><td class="angka"><?= rp($sisa) ?></td></tr>
    <tr><td>Sudah disetor kembali ke kas negara</td><td class="angka"><?= rp($setor_sisa) ?></td></tr>
    <tr><td><b>Belum disetor</b></td><td class="angka"><b><?= rp(max(0, $sisa - $setor_sisa)) ?></b></td></tr>
  </table>
  <p class="kecil-teks">Menurut Juknis, sisa dana bantuan yang tidak digunakan wajib disetorkan kembali ke kas negara melalui SIMPONI dan bukti setoran dilampirkan pada laporan akhir.</p>
</details>

<details class="panel panel-lipat" data-lipat-id="setor-catat-bukti-setor">
  <summary>Catat Bukti Setor</summary>

  <button type="button" class="tombol" onclick="bukaSetorBaru()">+ Catat Bukti Setor</button>
</details>

<?php if ($setor !== []): ?>
<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-setor','ids-setor')">
  <h2>Riwayat Bukti Setor</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-setor" method="post" action="<?= base_url('bukti-setor/hapus-massal') ?>" data-konfirmasi-judul="Hapus Bukti Setor Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-setor"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} bukti setor terpilih?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('bukti-setor/kosongkan') ?>" data-konfirmasi="Kosongkan riwayat bukti setor sekolah ini? Semua <?= count($setor) ?> catatan akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Bukti Setor" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Bukti Setor</button>
    </form>
  </div>
  <?php
  $barisSetor = [];
  foreach ($setor as $s) {
      $barisSetor[] = ['sel' => [
          ['html' => '<input type="checkbox" data-pilih value="' . (int) $s['id'] . '" x-model="terpilih">'],
          (string) tgl($s['tanggal_setor']),
          (string) ($label[$s['jenis']] ?? $s['jenis']),
          (string) ($s['masa'] ?? '—'),
          ['teks' => rp($s['jumlah']), 'angka' => true],
          (string) ($s['kode_billing'] ?? '—'),
          (string) ($s['ntpn'] ?? '—'),
          ['html' => '<form class="inline" method="post" action="' . base_url('bukti-setor/hapus/' . $s['id']) . '" data-konfirmasi="Hapus bukti setor ini?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>'],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari bukti setor…',
      'paginasi' => 10,
      'kolom' => [
          ['judul_html' => '<input type="checkbox" title="Pilih semua bukti setor" @change="pilihSemua($event)">', 'lebar' => '34px'],
          ['judul' => 'Tanggal'],
          ['judul' => 'Jenis'],
          ['judul' => 'Masa'],
          ['judul' => 'Jumlah', 'angka' => true],
          ['judul' => 'Kode Billing'],
          ['judul' => 'NTPN'],
          ['judul' => ''],
      ],
      'baris' => $barisSetor,
  ]);
  ?>
</div>
<?php endif; ?>

<?= form_modal([
    'id' => 'modal-setor',
    'judul' => 'Catat Bukti Setor',
    'aksi' => base_url('bukti-setor/simpan'),
    'lebar' => 720,
    'kolom' => [
        ['nama' => 'jenis', 'label' => 'Jenis Setoran', 'jenis' => 'pilihan', 'opsi' => $label],
        ['nama' => 'masa', 'label' => 'Masa Pajak', 'jenis' => 'teks', 'placeholder' => 'YYYY-MM', 'petunjuk' => 'Bulan masa pajak, format YYYY-MM (cth. 2026-11).'],
        ['nama' => 'tanggal_setor', 'label' => 'Tanggal Setor', 'jenis' => 'tanggal', 'wajib' => true],
        ['nama' => 'jumlah', 'label' => 'Jumlah Disetor (Rp)', 'jenis' => 'uang', 'wajib' => true],
        ['nama' => 'kode_billing', 'label' => 'Kode Billing', 'jenis' => 'teks', 'placeholder' => 'dari e-billing / SIMPONI'],
        ['nama' => 'ntpn', 'label' => 'NTPN', 'jenis' => 'teks', 'placeholder' => 'Nomor Transaksi Penerimaan Negara'],
        ['nama' => 'keterangan', 'label' => 'Keterangan', 'jenis' => 'teks', 'penuh' => true, 'placeholder' => 'opsional'],
    ],
    'tombol' => ['hapus' => false, 'simpan_teks' => 'Catat Bukti Setor'],
]) ?>
<script>
// Jembatan modal setor: tanggal bawaan mengikuti Bulan Bekerja, lalu
// sinkronkan widget select-cari sesudah modal dibuka.
function bukaSetorBaru() {
  window.bukaFormModal('modal-setor', { tanggal_setor: <?= json_encode($tgl_bayar_default ?? date('Y-m-d')) ?> });
  var m = document.getElementById('modal-setor');
  if (m && window.perbaruiSelectCari) {
    m.querySelectorAll('select[data-select-cari]').forEach(function (sel) { window.perbaruiSelectCari(sel); });
  }
}
</script>
