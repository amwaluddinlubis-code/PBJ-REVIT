<div class="panel">
  <p>Isi data pokok sekolah. Kop surat, pejabat lengkap, dan nama toko survei dapat dilengkapi sesudahnya di <strong>Identitas &amp; Pengaturan</strong>. Sekolah baru langsung menjadi sekolah aktif dengan data kosong.</p>
  <form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url($aksi) ?>">
    <?= csrf_field() ?>
    <?php
    $kolom = [
      'nama_sekolah' => 'Nama Sekolah *', 'npsn' => 'NPSN', 'alamat' => 'Alamat', 'desa' => 'Desa/Kelurahan',
      'kecamatan' => 'Kecamatan', 'kabupaten' => 'Kabupaten', 'provinsi' => 'Provinsi', 'email' => 'Email Sekolah',
      'kode_pos' => 'Kode Pos', 'tahun_anggaran' => 'Tahun Anggaran', 'paket_pekerjaan' => 'Paket Pekerjaan',
      'kepsek_nama' => 'Kepala Sekolah', 'kepsek_nip' => 'NIP Kepala Sekolah',
      'ketua_nama' => 'Ketua Panitia P2SP', 'sekretaris_nama' => 'Sekretaris / Logistik', 'pelaksana_nama' => 'Kepala Pelaksana',
    ];
    foreach ($kolom as $k => $label): ?>
      <label><?= $label ?> <input name="<?= $k ?>" value="<?= esc(old($k, $s[$k] ?? '')) ?>"></label>
    <?php endforeach; ?>
    <div class="aksi md:col-span-2"><button type="submit">Simpan &amp; Aktifkan</button><a class="tombol abu" href="<?= base_url('sekolah') ?>">Batal</a></div>
  </form>
</div>
