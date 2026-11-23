<?php

$tgl = static fn ($d) => $d ? date('d/m/Y', strtotime((string) $d)) : '';
$resmi = ($mode ?? 'perkiraan') === 'resmi';
?>
<div class="panel">
  <h2>Kurva S Pelaksanaan Pekerjaan</h2>
  <?php if ($adaRencana): ?>
  <p style="margin-top:0;color:var(--ta-teks-2)">Garis rencana memakai <strong>bobot resmi Time Schedule perencana</strong> yang sudah diimpor (per minggu kalender proyek), dibandingkan realisasi kumulatif dari data realisasi BOQ. Kelompok Persiapan &amp; Biaya Manajemen hanya masuk kurva Total Sekolah. Realisasi diisi dari tab Realisasi pada halaman <a href="<?= base_url('gedung') ?>">Gedung</a> dan diringkas di <a href="<?= base_url('laporan-kemajuan') ?>">Laporan Kemajuan</a>.</p>
  <?php else: ?>
  <p style="margin-top:0;color:var(--ta-teks-2)">Jadwal pelaksanaan dalam bentuk kurva S (format dokumen Juknis): garis rencana kumulatif dibandingkan realisasi kumulatif mingguan dari data realisasi BOQ. Jendela waktu mengikuti tanggal mulai–selesai SPK yang tertaut ke gedung. Impor rencana resmi perencana (berkas JSON) agar garis rencana mengikuti Time Schedule dokumen. Realisasi diisi dari tab Realisasi pada halaman <a href="<?= base_url('gedung') ?>">Gedung</a> dan diringkas di <a href="<?= base_url('laporan-kemajuan') ?>">Laporan Kemajuan</a>.</p>
  <?php endif; ?>
  <form method="get" action="<?= base_url('kurva-s') ?>" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
    <label>Tampilan
      <select data-select-cari name="gedung">
        <?php if ($adaRencana): ?><option value="total" <?= $total ? 'selected' : '' ?>>Total Sekolah (Semua Gedung)</option><?php endif; ?>
        <?php foreach ($semua as $row): ?><option value="<?= (int) $row['id'] ?>" <?= ! $total && $g && (int) $g['id'] === (int) $row['id'] ? 'selected' : '' ?>><?= esc($row['nama']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <button type="submit" class="kecil">Tampilkan</button>
  </form>
  <details style="margin-top:10px">
    <summary>Impor / Ganti Rencana Kurva S Resmi (JSON)</summary>
    <form method="post" enctype="multipart/form-data" action="<?= base_url('kurva-s/impor') ?>" style="margin-top:8px;display:flex;gap:8px;align-items:end;flex-wrap:wrap">
      <?= csrf_field() ?>
      <label>Berkas JSON rencana (daftar minggu + bobot per bab)
        <input type="file" name="berkas" accept=".json,application/json" required>
      </label>
      <button type="submit">Impor Rencana</button>
    </form>
    <p class="kecil-teks" style="margin-bottom:0">Kelompok pada dokumen dipetakan otomatis: RUANG KELAS BARU → gedung RKB, RUANG GURU → gedung ADM, TOILET → gedung Toilet; PERSIAPAN &amp; BIAYA MANAGEMEN masuk kurva total saja. Mengimpor menggantikan rencana resmi sebelumnya untuk sekolah aktif. Rencana resmi juga menjadi sumber tombol "Ambil Target dari Kurva S" di <a href="<?= base_url('rencana-mingguan') ?>">Rencana Mingguan</a> dan mesin <a href="<?= base_url('simulasi') ?>">Simulasi Mingguan</a>.</p>
  </details>
</div>

<?php if ($total || $g): ?>
<div class="panel">
  <?php if ($total): ?>
  <h2>Total Sekolah — Basis Nilai Realisasi <?= rp($basis) ?> (BOQ berjalan + biaya non-gedung)</h2>
  <?php else: ?>
  <h2><?= esc($g['nama']) ?> — Nilai BOQ <?= rp($g['nilai_boq']) ?> · Realisasi saat ini <?= number_format((float) $g['persen_fisik'], 1, ',', '.') ?>%</h2>
  <?php endif; ?>
  <?= $svg ?>
  <table data-paginasi="20" data-cari="Cari minggu…">
    <tr><th>Minggu ke-</th><th>Periode</th><th class="angka">Rencana Kumulatif</th><th class="angka">Realisasi Kumulatif</th><th class="angka">Nilai Realisasi</th><th class="angka">Deviasi</th></tr>
    <?php foreach ($minggu as $m): $dev = $m['realisasi'] - $m['rencana']; ?>
    <tr>
      <td><?= (int) $m['ke'] ?></td>
      <td><?= ! empty($m['mulai']) ? $tgl($m['mulai']) . ' s/d ' . $tgl($m['tanggal']) : 's/d ' . $tgl($m['tanggal']) ?></td>
      <td class="angka"><?= number_format($m['rencana'], 1, ',', '.') ?>%</td>
      <td class="angka"><?= number_format($m['realisasi'], 1, ',', '.') ?>%</td>
      <td class="angka"><?= rp($m['nilai']) ?></td>
      <td class="angka"><span style="color:<?= $dev < -0.05 ? 'var(--ina-red-700,#a61e1e)' : 'var(--ina-green-700,#1e6b2a)' ?>"><?= number_format($dev, 1, ',', '.') ?>%</span></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php if ($resmi): ?>
  <p class="kecil-teks">Deviasi negatif (merah) berarti realisasi tertinggal dari rencana resmi perencana pada minggu itu. Persen realisasi gedung dihitung terhadap nilai BOQ berjalannya (hasil AHSP); persen total terhadap BOQ berjalan semua gedung didanai ditambah biaya non-gedung.</p>
  <?php else: ?>
  <p class="kecil-teks">Deviasi negatif berarti realisasi tertinggal dari rencana. Garis rencana digambar halus berbentuk S (lambat di awal dan akhir, cepat di tengah) sebagai pembanding; sesuaikan dengan jadwal resmi pada dokumen perencanaan.</p>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="panel"><p>Belum ada gedung dari dokumen perencana.</p></div>
<?php endif; ?>
