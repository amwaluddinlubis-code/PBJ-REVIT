<div class="panel">
  <h2>Informasi Database</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Database aktif: <strong><?= esc($namaDb) ?></strong> · MariaDB <?= esc($versi) ?> · <?= count($tabel) ?> tabel · total ukuran <?= esc($totalUkuran) ?>. Halaman ini hanya untuk administrator.</p>
  <div class="bungkus-tabel">
  <table class="satu-baris" data-cari="Cari tabel…">
    <tr><th>Tabel</th><th class="angka">Perkiraan Baris</th><th class="angka">Ukuran</th></tr>
    <?php foreach ($tabel as $t): ?>
    <tr>
      <td><?= esc($t['nama']) ?></td>
      <td class="angka"><?= number_format((float) $t['baris'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format((float) $t['ukuran'] / 1024, 1, ',', '.') ?> KB</td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>

<div class="panel">
  <h2>Backup Database</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Backup berisi seluruh struktur dan data database; bila ada berkas Template Laporan, backup menjadi satu paket .zip (database + template). Simpan berkasnya di tempat aman di luar komputer ini.</p>
  <form method="post" action="<?= base_url('pemeliharaan-db/backup') ?>">
    <?= csrf_field() ?>
    <button type="submit">Buat Backup Sekarang</button>
  </form>
  <?php if ($backups): ?>
  <div class="bungkus-tabel" style="margin-top:12px">
  <table class="satu-baris" data-cari="Cari backup…">
    <tr><th>Berkas</th><th>Tanggal</th><th class="angka">Ukuran</th><th style="width:190px">Aksi</th></tr>
    <?php foreach ($backups as $b): ?>
    <tr>
      <td><?= esc($b['nama']) ?><div class="kecil-teks"><?= esc($b['jenis'] ?? '') ?></div></td>
      <td><?= esc($b['tanggal']) ?></td>
      <td class="angka"><?= esc($b['ukuran']) ?></td>
      <td>
        <a class="tombol kecil" href="<?= base_url('pemeliharaan-db/unduh/' . $b['nama']) ?>">Unduh</a>
        <form class="inline" method="post" action="<?= base_url('pemeliharaan-db/hapus-backup') ?>" data-konfirmasi="Hapus berkas backup <?= esc($b['nama']) ?>?">
          <?= csrf_field() ?>
          <input type="hidden" name="nama" value="<?= esc($b['nama']) ?>">
          <button class="kecil bahaya" type="submit">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php else: ?>
  <p class="kecil-teks">Belum ada backup tersimpan.</p>
  <?php endif; ?>
</div>

<div class="panel">
  <h2>Pulihkan Database</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)"><strong>Perhatian:</strong> pemulihan MENIMPA seluruh data saat ini dengan isi berkas backup. Sebelum memulihkan, aplikasi otomatis membuat backup keadaan saat ini (berawalan <em>pra-pulih-</em>) sehingga pemulihan masih bisa dibatalkan.</p>
  <form method="post" action="<?= base_url('pemeliharaan-db/pulih') ?>" enctype="multipart/form-data" data-konfirmasi="Pulihkan database dari berkas ini? SELURUH data saat ini akan ditimpa. Backup keadaan saat ini dibuat otomatis sebelum pemulihan.">
    <?= csrf_field() ?>
    <label>Unggah berkas .sql / paket .zip
      <input type="file" name="berkas" accept=".sql,.zip">
    </label>
    <label>atau pilih dari backup tersimpan
      <?= select_cari('dari_backup', array_merge([['value' => '', 'label' => '— tidak dipilih —']], array_map(static fn ($b) => ['value' => $b['nama'], 'label' => $b['nama'] . ' (' . $b['tanggal'] . ')'], $backups))) ?>
    </label>
    <button type="submit" class="bahaya">Pulihkan Database</button>
  </form>
</div>

<div class="panel">
  <h2>Sampah — Baris Terhapus</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Penghapusan di aplikasi ini bersifat lunak: baris disembunyikan, bukan dimusnahkan. Di sini baris terhapus bisa <strong>dipulihkan</strong> (tampil lagi) atau <strong>dihapus permanen</strong> (tidak bisa dikembalikan).</p>
  <div class="bungkus-tabel">
  <table class="satu-baris" data-cari="Cari tabel…">
    <tr><th>Tabel</th><th class="angka">Baris Aktif</th><th class="angka">Baris Terhapus</th><th style="width:300px">Aksi</th></tr>
    <?php foreach ($sampah as $t): ?>
    <tr>
      <td><?= esc($t['nama']) ?></td>
      <td class="angka"><?= number_format($t['aktif'], 0, ',', '.') ?></td>
      <td class="angka"><?= number_format($t['terhapus'], 0, ',', '.') ?></td>
      <td>
        <?php if ($t['terhapus'] > 0): ?>
        <form class="inline" method="post" action="<?= base_url('pemeliharaan-db/sampah-pulihkan') ?>" data-konfirmasi="Pulihkan <?= $t['terhapus'] ?> baris terhapus pada tabel <?= esc($t['nama']) ?>? Baris akan tampil lagi di aplikasi.">
          <?= csrf_field() ?>
          <input type="hidden" name="tabel" value="<?= esc($t['nama']) ?>">
          <button class="kecil" type="submit">Pulihkan Semua</button>
        </form>
        <form class="inline" method="post" action="<?= base_url('pemeliharaan-db/sampah-hapus') ?>" data-konfirmasi="Hapus PERMANEN <?= $t['terhapus'] ?> baris terhapus pada tabel <?= esc($t['nama']) ?>? Tindakan ini tidak bisa dibatalkan.">
          <?= csrf_field() ?>
          <input type="hidden" name="tabel" value="<?= esc($t['nama']) ?>">
          <button class="kecil bahaya" type="submit">Hapus Permanen</button>
        </form>
        <?php else: ?>
        <span class="kecil-teks">—</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>

<div class="panel">
  <h2>Optimasi Tabel</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Optimasi menyusun ulang penyimpanan tabel dan mengembalikan ruang kosong sisa penghapusan. Aman dijalankan kapan saja dan tidak mengubah data.</p>
  <form method="post" action="<?= base_url('pemeliharaan-db/optimasi') ?>">
    <?= csrf_field() ?>
    <button type="submit">Optimasi Semua Tabel</button>
  </form>
</div>
