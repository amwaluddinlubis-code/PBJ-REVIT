<?php

$num = static fn ($n) => number_format((float) $n, 2, ',', '.');
$pct = static function ($n) {
    $s = rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
    return $s === '' || $s === '-0' ? '0' : str_replace('.', ',', $s);
};
?>
<?php if (($belum ?? null) !== null): ?>
<details class="panel panel-lipat" data-lipat-id="simulasi-simulasi-mingguan" open>
  <summary>Simulasi Mingguan</summary>

  <?php if ($belum === 'migrasi'): ?>
  <p>Tabel Kurva S belum tersedia. Jalankan lebih dulu <code>php spark migrate</code> di komputer server (migrasi Kurva S &amp; Simulasi), lalu buka lagi halaman ini.</p>
  <?php else: ?>
  <p>Rencana Kurva S resmi belum diimpor untuk sekolah ini, jadi simulasi belum dapat dihitung. Impor dulu berkas JSON Time Schedule perencana di halaman <a href="<?= base_url('kurva-s') ?>">Kurva S</a>, lalu kembali ke sini.</p>
  <?php endif; ?>
</details>
<?php else: ?>
<details class="panel panel-lipat" data-lipat-id="simulasi-simulasi-mingguan-cara-kerja">
  <summary>Simulasi Mingguan — Cara Kerja</summary>

  <p class="kecil-teks" style="margin:0">Isi <strong>bobot realisasi tiap minggu per gedung</strong> (persen terhadap total proyek <?= rp($total_proyek) ?>, satuan yang sama dengan Kurva S), simpan, dan simulasi di bawah terhitung ulang: realisasi disebar ke bab mengikuti pola Kurva S minggu itu, sisa volume tiap bab dijadwalkan ulang ke minggu berikutnya mengikuti pola babnya, lalu kebutuhan bahan &amp; pekerja dihitung dengan mesin <a href="<?= base_url('rencana-belanja') ?>">Rencana Belanja</a>. Minggu terakhir yang terisi dianggap minggu berjalan (sekarang minggu ke-<strong><?= (int) $berjalan ?></strong>). Kosongkan sel untuk menganggap minggu itu belum ada realisasi.</p>
</details>

<details class="panel panel-lipat" data-lipat-id="simulasi-1-realisasi-mingguan-masukan">
  <summary>1. Realisasi Mingguan (Masukan)</summary>

  <form method="post" action="<?= base_url('simulasi/simpan') ?>">
    <?= csrf_field() ?>
    <div class="bungkus-tabel"><table>
      <tr><th>Minggu</th><th>Tanggal</th>
      <?php foreach ($gedung as $g): ?><th class="angka"><?= esc($g['nama']) ?> (%)</th><?php endforeach; ?>
      </tr>
      <?php foreach ($kalender as $km): $ke = (int) $km['minggu_ke']; ?>
      <tr>
        <td><strong><?= $ke ?></strong></td>
        <td><?= esc(tgl($km['tanggal_mulai'])) ?> – <?= esc(tgl($km['tanggal_selesai'])) ?></td>
        <?php foreach ($gedung as $gid => $g): ?>
        <td class="angka"><input type="number" step="0.01" min="0" max="100" name="realisasi[<?= $ke ?>][<?= (int) $gid ?>]" value="<?= isset($input[$ke][(int) $gid]) ? esc($pct($input[$ke][(int) $gid])) : '' ?>" style="width:90px;text-align:right" inputmode="decimal" aria-label="Realisasi minggu <?= $ke ?> <?= esc($g['nama']) ?>"></td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <p style="margin:10px 0 0"><button type="submit" class="tombol">Simpan &amp; Generate Ulang Simulasi</button></p>
  </form>
</details>

<details class="panel panel-lipat" data-lipat-id="simulasi-2-kedudukan-per-gedung-s-d-minggu-berjalan">
  <summary>2. Kedudukan per Gedung s.d. Minggu Berjalan (ke-<?= (int) $berjalan ?>)</summary>

  <div class="bungkus-tabel"><table>
    <tr><th>Gedung</th><th class="angka">Bobot Gedung</th><th class="angka">Rencana Kumulatif</th><th class="angka">Realisasi Kumulatif</th><th class="angka">Selisih</th><th>Keterangan</th></tr>
    <?php foreach ($ringkas as $r): $sel = $r['realisasi_kum'] - $r['rencana_kum']; ?>
    <tr>
      <td><strong><?= esc($r['nama']) ?></strong></td>
      <td class="angka"><?= $pct($r['bobot_total']) ?>%</td>
      <td class="angka"><?= $pct($r['rencana_kum']) ?>%</td>
      <td class="angka"><?= $pct($r['realisasi_kum']) ?>%</td>
      <td class="angka"><?= ($sel >= 0 ? '+' : '') . $pct($sel) ?>%</td>
      <td><?= $berjalan === 0 ? 'Belum ada realisasi dimasukkan' : ($sel >= 0.005 ? 'Lebih cepat dari rencana' : ($sel <= -0.005 ? 'Tertinggal dari rencana — sisa pekerjaan digeser & dikejar pada minggu berikutnya' : 'Sesuai rencana')) ?></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</details>

<details class="panel panel-lipat" data-lipat-id="simulasi-3-ringkasan-mingguan">
  <summary>3. Ringkasan Mingguan</summary>

  <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari minggu…">
    <tr><th>Minggu</th><th>Tanggal</th><th>Status</th><th class="angka">Bobot Rencana</th><th class="angka">Belanja Bahan</th><th class="angka">Nilai Upah</th><th class="angka">Perkiraan Orang</th><th data-tanpa-urut>Rincian</th></tr>
    <?php foreach ($hasil as $h):
      $nb = array_sum(array_column($h['bahan'], 'nilai'));
      $nu = array_sum(array_map(static fn ($u) => (float) $u['nilai'], $h['upah']));
      $org = array_sum(array_map(static fn ($u) => (int) $u['jumlah_pekerja'], $h['upah']));
    ?>
    <tr>
      <td><strong><?= (int) $h['ke'] ?></strong></td>
      <td><?= esc(tgl($h['mulai'])) ?> – <?= esc(tgl($h['selesai'])) ?></td>
      <td><?= $h['lewat'] ? 'Sudah lewat (dari realisasi)' : 'Rencana sisa' ?></td>
      <td class="angka"><?= $pct($h['bobot_rencana']) ?>%</td>
      <td class="angka"><?= rp($nb) ?></td>
      <td class="angka"><?= rp($nu) ?></td>
      <td class="angka"><?= $org ?></td>
      <td><a class="tombol kecil" href="#minggu-<?= (int) $h['ke'] ?>">Lihat</a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <p class="kecil-teks">Belanja bahan adalah kebutuhan kotor minggu itu pada harga acuan aplikasi — saat pelaksanaan kurangi stok tersedia &amp; SPK berjalan (gunakan <a href="<?= base_url('rencana-mingguan') ?>">Rencana Mingguan</a> untuk daftar belanja bersihnya). Perkiraan orang = kebutuhan OH ÷ 6 hari kerja.</p>
</details>

<?php foreach ($hasil as $h): ?>
<details class="panel" id="minggu-<?= (int) $h['ke'] ?>" <?= ((int) $h['ke'] === (int) $berjalan + 1) ? 'open' : '' ?>>
  <summary><strong>Minggu <?= (int) $h['ke'] ?></strong> — <?= esc(tgl($h['mulai'])) ?> s.d. <?= esc(tgl($h['selesai'])) ?> · <?= $h['lewat'] ? 'sudah lewat (porsi dari realisasi yang dimasukkan)' : 'rencana sisa pekerjaan' ?> · belanja <?= rp(array_sum(array_column($h['bahan'], 'nilai'))) ?></summary>

  <h3>Jenis Pekerjaan <?= $h['lewat'] ? 'yang Terealisasi' : 'yang Berjalan' ?></h3>
  <?php if ($h['aktif'] === []): ?>
  <p class="kecil-teks">Tidak ada pekerjaan BOQ pada minggu ini.</p>
  <?php else: ?>
  <?php
    $grupAktif = [];
    foreach ($h['aktif'] as $a) {
        $grupAktif[$a['gedung_id'] . '|' . $a['bab']][] = $a;
    }
  ?>
  <ul>
    <?php foreach ($grupAktif as $kunci => $items): [$gidAktif, $babAktif] = explode('|', $kunci, 2); ?>
    <li><strong><?= esc($gedung[(int) $gidAktif]['nama'] ?? '') ?> — <?= esc(ucwords(strtolower($babAktif))) ?></strong>: <?= implode('; ', array_map(static fn ($a) => esc($a['nama']) . ' (' . number_format((float) $a['volume'], 2, ',', '.') . ' ' . esc($a['satuan']) . ')', $items)) ?></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <h3>Rencana Belanja Bahan</h3>
  <?php if ($h['bahan'] === []): ?>
  <p class="kecil-teks">Tidak ada kebutuhan bahan dari pekerjaan BOQ minggu ini.</p>
  <?php else: ?>
  <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari bahan…" data-paginasi="25">
    <tr><th>Kode</th><th>Bahan</th><th class="angka">Qty</th><th>Satuan</th><th class="angka">Harga Acuan</th><th class="angka">Nilai</th></tr>
    <?php foreach ($h['bahan'] as $b): ?>
    <tr><td><?= esc($b['kode']) ?></td><td><?= esc($b['nama']) ?></td><td class="angka"><?= $num($b['qty']) ?></td><td><?= esc($b['satuan']) ?></td><td class="angka"><?= rp($b['harga']) ?></td><td class="angka"><?= rp($b['nilai']) ?></td></tr>
    <?php endforeach; ?>
    <tr class="total"><td colspan="5"><strong>Total belanja minggu ini</strong></td><td class="angka"><strong><?= rp(array_sum(array_column($h['bahan'], 'nilai'))) ?></strong></td></tr>
  </table></div>
  <?php endif; ?>

  <h3>Kebutuhan Pekerja</h3>
  <?php if ($h['upah'] === []): ?>
  <p class="kecil-teks">Tidak ada kebutuhan upah dari pekerjaan BOQ minggu ini.</p>
  <?php else: ?>
  <div class="bungkus-tabel"><table>
    <tr><th>Jenis Tenaga</th><th class="angka">Kebutuhan (OH)</th><th class="angka">Perkiraan Orang (6 hari)</th><th class="angka">Nilai Upah</th></tr>
    <?php foreach ($h['upah'] as $u): ?>
    <tr><td><?= esc($u['jenis']) ?></td><td class="angka"><?= $num($u['orang_hari']) ?></td><td class="angka"><?= (int) $u['jumlah_pekerja'] ?></td><td class="angka"><?= rp($u['nilai']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>

  <?php if ($h['non_boq'] !== []): ?>
  <h3>Biaya &amp; Belanja Non-BOQ Terjadwal (dari Kurva S)</h3>
  <ul>
    <?php foreach ($h['non_boq'] as $x): ?><li><?= esc($x['label']) ?>: <?= rp($x['nilai']) ?></li><?php endforeach; ?>
  </ul>
  <?php endif; ?>
</details>
<?php endforeach; ?>

<details class="panel panel-lipat" data-lipat-id="simulasi-catatan">
  <summary>Catatan</summary>

  <ul class="kecil-teks" style="margin:0">
    <li>Simulasi ini memakai realisasi mingguan yang diisi di halaman ini — terpisah dari catatan Realisasi per pekerjaan di modul Gedung. Mengubah masukan lalu menyimpan akan menghitung ulang seluruh minggu.</li>
    <li>Penyebaran realisasi ke bab mengikuti porsi bobot rencana minggu itu; bab yang jatahnya sudah habis berhenti menerima, kelebihannya pindah ke bab terjadwal berikutnya pada gedung yang sama.</li>
    <li>Bab BOQ tanpa baris sendiri di Kurva S mengikuti bab induknya: Tangga Beton → Struktur; Arsitektur &amp; Rampa → Lain-lain. Baris Perabot, Persiapan umum, dan Biaya Manajemen tampil sebagai biaya non-BOQ terjadwal (bukan pekerjaan BOQ).</li>
    <li>Tarif upah memakai tarif efektif analisa (terbesar antara tarif komponen dan Master Upah); jenis upah hanya Pekerja &amp; Tukang.</li>
  </ul>
</details>
<?php endif; ?>
