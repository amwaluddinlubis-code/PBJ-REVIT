<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <h2 style="margin-top:0">Analisa Pekerjaan (AHSP)</h2>
  <p class="kecil-teks" style="margin-top:0">Analisa menentukan kebutuhan bahan &amp; upah dari volume BOQ. Klik <strong>Buka</strong> untuk mengubah koefisien atau tarif komponen tanpa impor ulang — harga satuan analisa dihitung ulang dari komponennya dan kebutuhan di semua gedung langsung mengikuti.</p>
  <?php if (($belum ?? 0) > 0): ?>
  <p style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:8px 12px"><strong><?= (int) $belum ?> analisa</strong> harga satuannya <strong>belum terverifikasi</strong> — tidak terbaca dari sheet Harsat dokumen sumber (di dokumennya sendiri tertulis #REF!). Harga BOQ tetap yang dipakai untuk nilai pekerjaan; penanda ini hanya agar Bapak tahu analisa mana yang harga satuannya belum bisa dicek silang.</p>
  <?php endif; ?>
  <div class="aksi" style="justify-content:flex-start;margin-bottom:12px">
    <a class="tombol<?= ($tab ?? 'didanai') === 'didanai' ? '' : ' abu' ?>" href="<?= base_url('analisa?tab=didanai') ?>">Dipakai Gedung Didanai (<?= (int) ($jml_didanai ?? 0) ?>)</a>
    <a class="tombol<?= ($tab ?? 'didanai') === 'lainnya' ? '' : ' abu' ?>" href="<?= base_url('analisa?tab=lainnya') ?>">Lainnya — Tidak Didanai / Belum Dipakai (<?= (int) ($jml_lainnya ?? 0) ?>)</a>
  </div>
  <?php if ($daftar === []): ?>
  <p class="kecil-teks"><?= ($tab ?? 'didanai') === 'didanai' ? 'Tidak ada analisa yang dipakai oleh gedung yang didanai.' : 'Semua analisa dipakai oleh gedung yang didanai.' ?></p>
  <?php endif; ?>
  <div class="bungkus-tabel"><table class="satu-baris" data-cari>
    <tr><th>Kode</th><th>Nama Analisa</th><th>Satuan</th><th class="angka">Harga Satuan</th><th class="angka">Komponen</th><th class="angka">Dipakai di Pekerjaan</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $a): ?>
    <tr>
      <td><strong><?= esc($a['kode']) ?></strong></td>
      <td class="potong" title="<?= esc($a['nama'] ?? '') ?>"><?= esc($a['nama'] ?? '') ?></td>
      <td><?= esc($a['satuan'] ?? '') ?></td>
      <td class="angka"><?= $rp($a['harga_satuan'] ?? 0) ?><?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?> <span style="background:#fef3c7;color:#92400e;padding:1px 7px;border-radius:99px;font-size:11px">belum terverifikasi</span><?php endif; ?></td>
      <td class="angka"><?= (int) $a['jml_komponen'] ?></td>
      <td class="angka"><?= (int) $a['dipakai'] ?> pekerjaan<br><span class="kecil-teks"><?= (int) ($a['dipakai_didanai'] ?? 0) ?> di gedung didanai<?= (int) ($a['dipakai_lain'] ?? 0) > 0 ? ' · ' . (int) $a['dipakai_lain'] . ' di gedung tidak didanai' : '' ?></span></td>
      <td><a class="tombol kecil" href="<?= base_url('analisa/' . $a['id']) ?>">Buka</a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
