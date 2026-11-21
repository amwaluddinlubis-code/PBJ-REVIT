<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="aksi" style="margin:0 0 12px"><a class="tombol" href="<?= base_url('spk/baru') ?>">+ SPK Baru</a></div>
<div class="panel">
<table>
  <tr><th>No.</th><th>Tanggal</th><th>Penyedia</th><th>Paket</th><th>Periode</th><th class="angka">Jml Bahan</th><th class="angka">Nilai SPK</th><th>Status</th><th></th></tr>
  <?php foreach ($spk as $s): ?>
  <tr>
    <td><a href="<?= base_url('spk/detail/' . $s['id']) ?>"><?= (int) $s['nomor'] ?></a></td>
    <td><?= esc($s['tanggal'] ?? '—') ?></td>
    <td><?= esc($s['penyedia'] ?? '—') ?></td>
    <td><?= esc($s['paket'] ?? '—') ?></td>
    <td><?= esc($s['tgl_mulai'] ?? '—') ?> s.d. <?= esc($s['tgl_selesai'] ?? '—') ?></td>
    <td class="angka"><?= (int) $s['jumlah'] ?></td>
    <td class="angka"><?= $rp($s['nilai']) ?></td>
    <td><?= esc($s['status']) ?></td>
    <td><a href="<?= base_url('spk/ubah/' . $s['id']) ?>">Ubah</a> · <a href="<?= base_url('spk/detail/' . $s['id']) ?>">Detail</a></td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
