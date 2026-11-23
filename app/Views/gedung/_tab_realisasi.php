<?php /* Partial gedung/detail tab Realisasi: panel Kemajuan per Pekerjaan + Riwayat Pencatatan Terakhir. */ ?><div class="panel">
  <h2>Kemajuan per Pekerjaan</h2>
  <div class="bungkus-tabel"><table class="satu-baris" data-paginasi="25" data-cari="Cari…">
    <tr><th>Pekerjaan</th><th class="angka">Volume BOQ</th><th class="angka">Terealisasi</th><th>% Fisik</th><th style="width:300px">Catat Kemajuan</th></tr>
    <?php foreach ($pekerjaan as $p): $persen = (float) $p['volume'] > 0 ? min(100, (float) $p['volume_terealisasi'] / (float) $p['volume'] * 100) : 0; ?>
    <tr>
      <td class="potong" title="<?= esc($p['nama']) ?>"><?= esc($p['nama']) ?><?php if (! empty($p['bab'])): ?> <span class="kecil-teks">(<?= esc($p['bab']) ?>)</span><?php endif; ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume'], 3, ',', '.'), '0'), ',') ?> <?= esc($p['satuan'] ?? '') ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $p['volume_terealisasi'], 3, ',', '.'), '0'), ',') ?></td>
      <td><div class="sel-fisik"><div class="bar<?= $persen >= 99.5 ? ' hijau' : '' ?>"><div style="width:<?= $persen ?>%"></div></div><span class="kecil-teks"><?= number_format($persen, 1, ',', '.') ?>%</span></div></td>
      <td>
        <form method="post" action="<?= base_url('gedung/pekerjaan/' . $p['id'] . '/realisasi') ?>" style="display:flex;gap:5px">
          <?= csrf_field() ?>
          <input type="text" name="volume" placeholder="Volume" required style="margin:0;width:84px">
          <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" style="margin:0;width:132px">
          <button class="kecil" type="submit">Catat</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<div class="panel">
  <h2>Riwayat Pencatatan Terakhir</h2>
  <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari riwayat…">
    <tr><th>Tanggal</th><th>Pekerjaan</th><th class="angka">Volume</th><th>Keterangan</th><th>Aksi</th></tr>
    <?php foreach ($riwayat as $r): ?>
    <tr>
      <td><?= esc(tgl($r['tanggal'])) ?></td>
      <td><?= esc($r['nama_pekerjaan']) ?></td>
      <td class="angka"><?= rtrim(rtrim(number_format((float) $r['volume'], 3, ',', '.'), '0'), ',') ?></td>
      <td><?= esc($r['keterangan'] ?? '') ?></td>
      <td><form class="inline" method="post" action="<?= base_url('gedung/realisasi/' . $r['id'] . '/hapus') ?>" data-konfirmasi="Hapus catatan ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($riwayat === []): ?><tr><td colspan="5">Belum ada pencatatan kemajuan.</td></tr><?php endif; ?>
  </table></div>
</div>
