<div class="panel">
  <h2>Identitas Satuan Pendidikan & Panitia</h2>
  <form class="grid" method="post" action="<?= base_url('pengaturan/simpan') ?>">
    <?= csrf_field() ?>
    <?php
    $kolom = [
      'nama_sekolah' => 'Nama Sekolah', 'npsn' => 'NPSN', 'alamat' => 'Alamat', 'desa' => 'Desa/Kelurahan',
      'kecamatan' => 'Kecamatan', 'kabupaten' => 'Kabupaten', 'provinsi' => 'Provinsi', 'email' => 'Email Sekolah',
      'kode_pos' => 'Kode Pos', 'tahun_anggaran' => 'Tahun Anggaran', 'paket_pekerjaan' => 'Paket Pekerjaan',
      'kop_pemerintah' => 'Kop Baris 1 (Pemerintah)', 'kop_dinas' => 'Kop Baris 2 (Dinas)', 'alamat_kop' => 'Alamat pada Kop',
      'kepsek_nama' => 'Kepala Sekolah', 'kepsek_nip' => 'NIP Kepala Sekolah',
      'ketua_nama' => 'Ketua Panitia P2SP', 'sekretaris_nama' => 'Sekretaris / Logistik', 'pelaksana_nama' => 'Kepala Pelaksana',
      'pemeriksa1' => 'Anggota Pemeriksa 1', 'pemeriksa2' => 'Anggota Pemeriksa 2', 'pemeriksa3' => 'Anggota Pemeriksa 3',
      'toko1' => 'Nama Toko Survei 1', 'toko2' => 'Nama Toko Survei 2', 'toko3' => 'Nama Toko Survei 3', 'toko4' => 'Nama Toko Survei 4',
      'penyedia_default_nama' => 'Penyedia Default (Negosiasi)', 'penyedia_default_alamat' => 'Alamat Penyedia Default',
    ];
    foreach ($kolom as $k => $label): ?>
      <label><?= $label ?> <input name="<?= $k ?>" value="<?= esc($idn[$k] ?? '') ?>"></label>
    <?php endforeach; ?>
    <label>Tanggal Survei Terakhir <input type="date" name="tanggal_survei" value="<?= esc($idn['tanggal_survei'] ?? '') ?>"></label>
    <div class="aksi"><button type="submit">Simpan Identitas</button></div>
  </form>
</div>
<div class="panel">
  <h2>Master Penyedia</h2>
  <table>
    <tr><th>Nama</th><th>Alamat</th><th>Penandatangan</th><th></th></tr>
    <?php foreach ($penyedia as $p): ?>
    <tr><td><?= esc($p['nama']) ?></td><td><?= esc($p['alamat'] ?? '') ?></td><td><?= esc($p['ttd_nama'] ?? '') ?></td>
      <td><form class="inline" method="post" action="<?= base_url('pengaturan/penyedia-hapus/' . $p['id']) ?>" onsubmit="return confirm('Hapus penyedia ini?')"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td></tr>
    <?php endforeach; ?>
  </table>
  <form class="grid" method="post" action="<?= base_url('pengaturan/penyedia-tambah') ?>" style="margin-top:12px">
    <?= csrf_field() ?>
    <label>Nama Penyedia <input name="nama" required></label>
    <label>Alamat <input name="alamat"></label>
    <label>Penandatangan <input name="ttd_nama"></label>
    <div class="aksi"><button type="submit">Tambah Penyedia</button></div>
  </form>
</div>
