<?php
$rp = static fn ($n) => number_format((float) $n, 0, ',', '.');
$f = static function (string $k, string $label, string $petunjuk = '') use ($idn): string {
    return '<label>' . $label . ' <input name="' . $k . '" value="' . esc($idn[$k] ?? '') . '">'
        . ($petunjuk !== '' ? '<span class="kecil-teks">' . $petunjuk . '</span>' : '') . '</label>';
};
$anggaran = (float) ($idn['total_anggaran'] ?? 0);
?>
<form method="post" action="<?= base_url('pengaturan/simpan') ?>">
  <?= csrf_field() ?>

  <div class="panel">
    <h2>Identitas Sekolah</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <?= $f('nama_sekolah', 'Nama Sekolah') ?>
      <?= $f('npsn', 'NPSN') ?>
      <?= $f('tahun_anggaran', 'Tahun Anggaran') ?>
      <?= $f('paket_pekerjaan', 'Paket Pekerjaan', 'mis. Revitalisasi 3 Gedung') ?>
      <?= $f('alamat', 'Alamat') ?>
      <?= $f('desa', 'Desa/Kelurahan') ?>
      <?= $f('kecamatan', 'Kecamatan') ?>
      <?= $f('kabupaten', 'Kabupaten') ?>
      <?= $f('provinsi', 'Provinsi') ?>
      <?= $f('kode_pos', 'Kode Pos') ?>
      <?= $f('email', 'Email Sekolah') ?>
    </div>
  </div>

  <div class="panel">
    <h2>Pagu Anggaran (Kontrol Belanja)</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Total Anggaran / Bantuan (Rp)
        <input data-uang name="total_anggaran" value="<?= $anggaran > 0 ? $rp($anggaran) : '' ?>" placeholder="mis. 2.540.292.000">
        <span class="kecil-teks">Terisi otomatis dari Rekap RAB saat impor dokumen perencana. Isi manual bila sekolah tidak memakai impor.</span>
      </label>
      <label>Belanja terbayar saat ini (terkunci)
        <input value="Rp <?= $rp($terbayar) ?> — sisa pagu <?= $anggaran > 0 ? 'Rp ' . $rp($anggaran - $terbayar) : 'pagu belum diatur' ?>" disabled>
        <span class="kecil-teks">Pembayaran invoice &amp; upah yang menembus pagu akan ditahan aplikasi.</span>
      </label>
    </div>
  </div>

  <div class="panel">
    <h2>Kop Surat Dokumen</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <?= $f('kop_pemerintah', 'Kop Baris 1 (Pemerintah)', 'mis. PEMERINTAH KABUPATEN MANDAILING NATAL') ?>
      <?= $f('kop_dinas', 'Kop Baris 2 (Dinas)', 'mis. DINAS PENDIDIKAN') ?>
      <?= $f('alamat_kop', 'Alamat pada Kop', 'Kosongkan untuk memakai alamat sekolah di atas secara otomatis.') ?>
    </div>
  </div>

  <div class="panel">
    <h2>Panitia &amp; Penandatangan</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <?= $f('kepsek_nama', 'Kepala Sekolah') ?>
      <?= $f('kepsek_nip', 'NIP Kepala Sekolah') ?>
      <?= $f('ketua_nama', 'Ketua Panitia P2SP') ?>
      <?= $f('sekretaris_nama', 'Sekretaris / Logistik') ?>
      <?= $f('pelaksana_nama', 'Kepala Pelaksana') ?>
    </div>
  </div>

  <div class="panel">
    <details>
      <summary style="cursor:pointer;font-weight:600">Pengaturan Lanjutan — jarang diubah (tim pemeriksa, toko survei, penyedia bawaan)</summary>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" style="margin-top:14px">
        <?= $f('pemeriksa1', 'Anggota Pemeriksa 1 (BAP)') ?>
        <?= $f('pemeriksa2', 'Anggota Pemeriksa 2 (BAP)') ?>
        <?= $f('pemeriksa3', 'Anggota Pemeriksa 3 (BAP)') ?>
        <label>Tanggal Survei Terakhir <input type="date" name="tanggal_survei" value="<?= esc($idn['tanggal_survei'] ?? '') ?>"></label>
        <?= $f('toko1', 'Nama Toko Survei 1') ?>
        <?= $f('toko2', 'Nama Toko Survei 2') ?>
        <?= $f('toko3', 'Nama Toko Survei 3') ?>
        <?= $f('toko4', 'Nama Toko Survei 4') ?>
        <?= $f('penyedia_default_nama', 'Penyedia Default (Negosiasi)') ?>
        <?= $f('penyedia_default_alamat', 'Alamat Penyedia Default') ?>
      </div>
    </details>
    <div class="aksi"><button type="submit">Simpan Semua Pengaturan</button></div>
  </div>
</form>

<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-penyedia','ids-penyedia')">
  <h2>Master Penyedia</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-penyedia" method="post" action="<?= base_url('pengaturan/penyedia-hapus-massal') ?>" data-konfirmasi-judul="Hapus Penyedia Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-penyedia"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} penyedia terpilih? Penyedia yang dipakai SPK akan dilewati.')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('pengaturan/penyedia-kosongkan') ?>" data-konfirmasi="Kosongkan master penyedia sekolah ini? Semua penyedia aktif akan dihapus (hapus lunak), kecuali yang dipakai SPK." data-konfirmasi-judul="Kosongkan Master Penyedia" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Master Penyedia</button>
    </form>
  </div>
  <table data-cari="Cari penyedia…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua penyedia" @change="pilihSemua($event)"></th><th>Nama</th><th>Alamat</th><th>Penandatangan</th><th></th></tr>
    <?php foreach ($penyedia as $p): ?>
    <tr><td><input type="checkbox" data-pilih value="<?= (int) $p['id'] ?>" x-model="terpilih"></td><td><?= esc($p['nama']) ?></td><td><?= esc($p['alamat'] ?? '') ?></td><td><?= esc($p['ttd_nama'] ?? '') ?></td>
      <td style="white-space:nowrap">
        <button class="kecil" type="button" data-ubah-penyedia data-id="<?= (int) $p['id'] ?>" data-nama="<?= esc($p['nama'], 'attr') ?>" data-alamat="<?= esc($p['alamat'] ?? '', 'attr') ?>" data-ttd="<?= esc($p['ttd_nama'] ?? '', 'attr') ?>">Ubah</button>
        <form class="inline" method="post" action="<?= base_url('pengaturan/penyedia-hapus/' . $p['id']) ?>" data-konfirmasi="Hapus penyedia ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form>
      </td></tr>
    <?php endforeach; ?>
  </table>
  <form id="form-penyedia" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url('pengaturan/penyedia-tambah') ?>" style="margin-top:12px">
    <?= csrf_field() ?>
    <label>Nama Penyedia <input name="nama" required></label>
    <label>Alamat <input name="alamat"></label>
    <label>Penandatangan <input name="ttd_nama"></label>
    <div class="aksi md:col-span-2">
      <button type="submit" id="tombol-penyedia">Tambah Penyedia</button>
      <button type="button" id="batal-penyedia" class="abu" style="display:none">Batal Ubah</button>
    </div>
  </form>
</div>
<script>
(function () {
  var form = document.getElementById('form-penyedia');
  if (!form) return;
  var tombol = document.getElementById('tombol-penyedia');
  var batal = document.getElementById('batal-penyedia');
  var aksiTambah = form.getAttribute('action');
  function modeTambah() {
    form.setAttribute('action', aksiTambah);
    form.reset();
    tombol.textContent = 'Tambah Penyedia';
    batal.style.display = 'none';
  }
  batal.addEventListener('click', modeTambah);
  document.querySelectorAll('[data-ubah-penyedia]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      form.setAttribute('action', aksiTambah.replace('penyedia-tambah', 'penyedia-ubah/' + btn.dataset.id));
      form.querySelector('[name=nama]').value = btn.dataset.nama || '';
      form.querySelector('[name=alamat]').value = btn.dataset.alamat || '';
      form.querySelector('[name=ttd_nama]').value = btn.dataset.ttd || '';
      tombol.textContent = 'Simpan Perubahan';
      batal.style.display = '';
      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      form.querySelector('[name=nama]').focus();
    });
  });
})();
</script>
