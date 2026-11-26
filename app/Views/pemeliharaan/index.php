<details class="panel panel-lipat" data-lipat-id="pemeliharaan-informasi-database" open>
  <summary>Informasi Database</summary>

  <p style="margin-top:0;color:var(--ta-teks-2)">Database aktif: <strong><?= esc($namaDb) ?></strong> · MariaDB <?= esc($versi) ?> · <?= count($tabel) ?> tabel · total ukuran <?= esc($totalUkuran) ?>. Halaman ini hanya untuk administrator.</p>
  <div class="bungkus-tabel">
  <?php
  $barisInfo = [];
  foreach ($tabel as $t) {
      $barisInfo[] = ['sel' => [
          (string) $t['nama'],
          ['teks' => number_format((float) $t['baris'], 0, ',', '.'), 'angka' => true],
          ['teks' => number_format((float) $t['ukuran'] / 1024, 1, ',', '.') . ' KB', 'angka' => true],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari tabel…',
      'class' => 'satu-baris',
      'kolom' => [
          ['judul' => 'Tabel'],
          ['judul' => 'Perkiraan Baris', 'angka' => true],
          ['judul' => 'Ukuran', 'angka' => true],
      ],
      'baris' => $barisInfo,
  ]);
  ?>
  </div>
</details>

<details class="panel panel-lipat" data-lipat-id="pemeliharaan-backup-database">
  <summary>Backup Database</summary>

  <p style="margin-top:0;color:var(--ta-teks-2)">Backup berisi seluruh struktur dan data database; bila ada berkas Template Laporan, backup menjadi satu paket .zip (database + template). Simpan berkasnya di tempat aman di luar komputer ini.</p>
  <form method="post" action="<?= base_url('pemeliharaan-db/backup') ?>">
    <?= csrf_field() ?>
    <button type="submit">Buat Backup Sekarang</button>
  </form>
  <?php if ($backups): ?>
  <div class="bungkus-tabel" style="margin-top:12px">
  <?php
  $barisBackup = [];
  foreach ($backups as $b) {
      $barisBackup[] = ['sel' => [
          ['html' => esc($b['nama']) . '<div class="kecil-teks">' . esc($b['jenis'] ?? '') . '</div>'],
          (string) $b['tanggal'],
          ['teks' => (string) $b['ukuran'], 'angka' => true],
          ['html' => '<a class="tombol kecil" href="' . base_url('pemeliharaan-db/unduh/' . $b['nama']) . '">Unduh</a>'
              . '<form class="inline" method="post" action="' . base_url('pemeliharaan-db/hapus-backup') . '" data-konfirmasi="Hapus berkas backup ' . esc($b['nama'], 'attr') . '?">'
              . csrf_field()
              . '<input type="hidden" name="nama" value="' . esc($b['nama'], 'attr') . '">'
              . '<button class="kecil bahaya" type="submit">Hapus</button></form>'],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari backup…',
      'class' => 'satu-baris',
      'kolom' => [
          ['judul' => 'Berkas'],
          ['judul' => 'Tanggal'],
          ['judul' => 'Ukuran', 'angka' => true],
          ['judul' => 'Aksi', 'lebar' => '190px'],
      ],
      'baris' => $barisBackup,
  ]);
  ?>
  </div>
  <?php else: ?>
  <p class="kecil-teks">Belum ada backup tersimpan.</p>
  <?php endif; ?>
</details>

<details class="panel panel-lipat" data-lipat-id="pemeliharaan-pulihkan-database">
  <summary>Pulihkan Database</summary>

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
</details>

<details class="panel panel-lipat" data-lipat-id="pemeliharaan-sampah-baris-terhapus">
  <summary>Sampah — Baris Terhapus</summary>

  <p style="margin-top:0;color:var(--ta-teks-2)">Penghapusan di aplikasi ini bersifat lunak: baris disembunyikan, bukan dimusnahkan. Di sini baris terhapus bisa <strong>dipulihkan</strong> (tampil lagi) atau <strong>dihapus permanen</strong> (tidak bisa dikembalikan).</p>
  <div class="bungkus-tabel">
  <?php
  $barisSampah = [];
  foreach ($sampah as $t) {
      $aksiSampah = '<span class="kecil-teks">—</span>';
      if ($t['terhapus'] > 0) {
          $aksiSampah = '<form class="inline" method="post" action="' . base_url('pemeliharaan-db/sampah-pulihkan') . '" data-konfirmasi="Pulihkan ' . $t['terhapus'] . ' baris terhapus pada tabel ' . esc($t['nama'], 'attr') . '? Baris akan tampil lagi di aplikasi.">'
              . csrf_field()
              . '<input type="hidden" name="tabel" value="' . esc($t['nama'], 'attr') . '">'
              . '<button class="kecil" type="submit">Pulihkan Semua</button></form>'
              . '<form class="inline" method="post" action="' . base_url('pemeliharaan-db/sampah-hapus') . '" data-konfirmasi="Hapus PERMANEN ' . $t['terhapus'] . ' baris terhapus pada tabel ' . esc($t['nama'], 'attr') . '? Tindakan ini tidak bisa dibatalkan.">'
              . csrf_field()
              . '<input type="hidden" name="tabel" value="' . esc($t['nama'], 'attr') . '">'
              . '<button class="kecil bahaya" type="submit">Hapus Permanen</button></form>';
      }
      $barisSampah[] = ['sel' => [
          (string) $t['nama'],
          ['teks' => number_format($t['aktif'], 0, ',', '.'), 'angka' => true],
          ['teks' => number_format($t['terhapus'], 0, ',', '.'), 'angka' => true],
          ['html' => $aksiSampah],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari tabel…',
      'class' => 'satu-baris',
      'kolom' => [
          ['judul' => 'Tabel'],
          ['judul' => 'Baris Aktif', 'angka' => true],
          ['judul' => 'Baris Terhapus', 'angka' => true],
          ['judul' => 'Aksi', 'lebar' => '300px'],
      ],
      'baris' => $barisSampah,
  ]);
  ?>
  </div>
</details>

<details class="panel panel-lipat" data-lipat-id="pemeliharaan-optimasi-tabel">
  <summary>Optimasi Tabel</summary>

  <p style="margin-top:0;color:var(--ta-teks-2)">Optimasi menyusun ulang penyimpanan tabel dan mengembalikan ruang kosong sisa penghapusan. Aman dijalankan kapan saja dan tidak mengubah data.</p>
  <form method="post" action="<?= base_url('pemeliharaan-db/optimasi') ?>">
    <?= csrf_field() ?>
    <button type="submit">Optimasi Semua Tabel</button>
  </form>
</details>
