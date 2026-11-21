<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <h2>Laporan Kemajuan &amp; Laporan Akhir — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Mengikuti format dokumen Juknis Revitalisasi SMP 2026. <b>Laporan Kemajuan</b> dipakai untuk pengajuan pencairan dana tahap 2 (syarat: kemajuan penyelesaian pekerjaan minimal 50%, dilampiri foto). <b>Laporan Akhir</b> dibuat setelah pekerjaan selesai 100%, memuat dana awal, dana yang digunakan, dan sisa dana. Nomor SK PPK dan nomor PKS pada format resmi diisi manual pada hasil cetak karena tidak tersimpan di aplikasi.</p>
  <div class="kartu-kpi">
    <div class="kpi"><div class="label">Kemajuan Fisik Keseluruhan</div><div class="nilai"><?= number_format($fisik, 1, ',', '.') ?>%</div></div>
    <div class="kpi"><div class="label">Dana Diterima</div><div class="nilai"><?= $rp($diterima) ?></div></div>
    <div class="kpi"><div class="label">Dana Digunakan (terbayar)</div><div class="nilai"><?= $rp($digunakan) ?></div></div>
    <div class="kpi"><div class="label">Sisa Dana</div><div class="nilai"><?= $rp($sisa) ?></div></div>
  </div>
  <div class="aksi" style="justify-content:flex-start;gap:8px">
    <a class="tombol" href="<?= base_url('laporan-kemajuan/cetak-kemajuan') ?>" target="_blank">Cetak Laporan Kemajuan</a>
    <a class="tombol abu" href="<?= base_url('laporan-kemajuan/cetak-akhir') ?>" target="_blank">Cetak Laporan Akhir 100%</a>
  </div>
  <?php if ($fisik < 50): ?><p class="kecil-teks">Kemajuan fisik saat ini <?= number_format($fisik, 1, ',', '.') ?>% — belum mencapai syarat 50% untuk pengajuan tahap 2.</p><?php endif; ?>
  <?php if ($setor_sisa > 0): ?><p class="kecil-teks">Sisa dana yang sudah disetor kembali ke kas negara: <?= $rp($setor_sisa) ?> (lihat menu Bukti Setor &amp; Pajak).</p><?php endif; ?>
  <p class="kecil-teks">Dokumentasi foto terlampir: sebelum <?= (int) ($foto['sebelum'] ?? 0) ?> · proses <?= (int) ($foto['proses'] ?? 0) ?> · akhir <?= (int) ($foto['akhir'] ?? 0) ?> — kelola di menu <a href="<?= base_url('foto-progres') ?>">Foto Progres</a>.</p>
</div>

<div class="panel">
  <h2>Kemajuan per Gedung</h2>
  <table>
    <tr><th>Gedung / Paket</th><th class="angka">Nilai BOQ</th><th class="angka">Nilai Realisasi</th><th class="angka">Kemajuan Fisik</th></tr>
    <?php foreach ($gedung as $g): ?>
    <tr>
      <td><?= esc($g['nama']) ?></td>
      <td class="angka"><?= $rp($g['nilai_boq']) ?></td>
      <td class="angka"><?= $rp($g['nilai_realisasi']) ?></td>
      <td class="angka"><?= number_format((float) $g['persen_fisik'], 1, ',', '.') ?>%</td>
    </tr>
    <?php endforeach; ?>
    <?php if ($gedung === []): ?><tr><td colspan="4">Belum ada gedung dari dokumen perencana.</td></tr><?php endif; ?>
    <tr><td><b>Total</b></td><td class="angka"><b><?= $rp($total_boq) ?></b></td><td class="angka"><b><?= $rp($total_realisasi) ?></b></td><td class="angka"><b><?= number_format($fisik, 1, ',', '.') ?>%</b></td></tr>
  </table>
</div>
