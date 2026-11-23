<?php /* Partial sekolah/index: modal form Tambah Sekolah — bidang sama persis dengan sekolah/form.php; halaman form mandiri tetap ada sebagai cadangan. POST biasa ke sekolah/simpan. */ ?>
<div class="mk-lapik" id="modal-sekolah" hidden>
  <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="msek-judul" style="max-width:720px;width:94vw">
    <h3 id="msek-judul" style="margin-top:0">Tambah Sekolah</h3>
    <p class="kecil-teks" style="margin-top:0">Isi data pokok sekolah. Kop surat, pejabat lengkap, dan nama toko survei dapat dilengkapi sesudahnya di <strong>Identitas &amp; Pengaturan</strong>. Sekolah baru langsung menjadi sekolah aktif dengan data kosong.</p>
    <form class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" method="post" action="<?= base_url('sekolah/simpan') ?>">
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
        <label><?= $label ?> <input name="<?= $k ?>"></label>
      <?php endforeach; ?>
      <div class="aksi md:col-span-2"><button type="submit">Simpan &amp; Aktifkan</button><button type="button" class="abu" onclick="tutupModalSekolah()">Batal</button></div>
    </form>
  </div>
</div>
<script>
(function () {
  var lapik = document.getElementById('modal-sekolah');
  window.bukaSekolahBaru = function () { lapik.hidden = false; };
  window.tutupModalSekolah = function () { lapik.hidden = true; };
  lapik.addEventListener('click', function (e) { if (e.target === lapik) tutupModalSekolah(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !lapik.hidden) tutupModalSekolah(); });
})();
</script>
