<?php /* Partial analisa/detail: panel komponen AHSP (tabel bahan/upah/alat + aksi ubah/hapus per baris). */ ?>
<?php $lencana = ['bahan' => '#dbeafe', 'upah' => '#dcfce7', 'alat' => '#e5e7eb']; $total = 0.0; foreach ($komponen as $k) { $total += (float) $k['koefisien'] * (float) ($k['tarif'] ?? 0); } ?><div class="panel">
  <h2 style="margin-top:0"><?= esc($a['kode']) ?> — <?= esc($a['nama'] ?? '') ?><?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?> <span style="background:#fef3c7;color:#92400e;padding:2px 9px;border-radius:99px;font-size:12px;vertical-align:middle">harga satuan belum terverifikasi</span><?php endif; ?></h2>
  <?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?>
  <p style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:8px 12px">Harga satuan analisa ini tidak terbaca dari sheet Harsat dokumen sumber (tertulis #REF! di dokumennya). Menyimpan perubahan komponen di bawah akan mengisinya dari jumlah komponen.</p>
  <?php endif; ?>
  <p class="kecil-teks" id="ringkas-analisa" style="margin-top:0">Satuan analisa: <strong><?= esc($a['satuan'] ?? '-') ?></strong> · Harga satuan tersimpan: <strong><?= rp($a['harga_satuan'] ?? 0) ?></strong> · Jumlah komponen: <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong> per satuan. Klik Ubah pada komponen untuk mengubah koefisien/tarif lewat modal — kebutuhan bahan &amp; upah di semua gedung yang memakai analisa ini langsung berubah.</p>
  <p style="margin:0 0 10px"><button type="button" class="tombol" id="btn-tambah-komp">+ Tambah Komponen</button></p>
  <?php if (! empty($acuanInfo)): ?>
  <p class="kecil-teks" style="margin:0 0 10px">Padanan AHSP Nasional: <strong><?= esc($acuanInfo['kode_nasional']) ?> — <?= esc($acuanInfo['nama']) ?></strong>. Kolom <strong>Koef. Acuan</strong> &amp; <strong>Nilai Acuan</strong> (koefisien acuan × tarif komponen ini) tampil sebagai pembanding di samping setiap komponen — dokumen nasional hanya menetapkan koefisien, tarif tetap tarif sekolah. Ubah koefisien lewat tombol Ubah bila ingin menyamakannya. <a href="<?= base_url('acuan') ?>">Buka modul Acuan</a></p>
  <?php endif; ?>

  <div id="wadah-komponen">
    <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari komponen…">
      <tr><th>Jenis</th><th>Komponen</th><th class="angka">Koefisien</th><th>Satuan</th><th class="angka">Tarif (Rp)</th><th class="angka">Nilai / Satuan</th><?php if (! empty($acuanInfo)): ?><th class="angka">Koef. Acuan</th><th class="angka">Nilai Acuan</th><?php endif; ?><th data-tanpa-urut>Aksi</th></tr>
      <?php foreach ($komponen as $k): $nol = (float) ($k['tarif'] ?? 0) <= 0; ?>
      <tr<?= $nol ? ' style="background:#fffbeb"' : '' ?>>
        <td><span style="background:<?= $lencana[$k['jenis']] ?? '#e5e7eb' ?>;padding:1px 7px;border-radius:99px;font-size:11px"><?= esc(ucfirst($k['jenis'])) ?></span></td>
        <td class="potong" title="<?= esc($k['nama']) ?>"><?= esc($k['nama_master'] ?? $k['nama']) ?><?php if (! empty($k['kode_master'])): ?> <span class="kecil-teks">(<?= esc($k['kode_master']) ?>)</span><?php endif; ?></td>
        <td class="angka"><?= esc(rtrim(rtrim(number_format((float) $k['koefisien'], 6, ',', '.'), '0'), ',')) ?></td>
        <td><?= esc($k['satuan'] ?? '') ?></td>
        <td class="angka"><?= number_format((float) ($k['tarif'] ?? 0), 0, ',', '.') ?><?php if ($nol): ?> <span style="background:#fef3c7;color:#92400e;padding:1px 7px;border-radius:99px;font-size:11px">tarif 0</span><?php endif; ?></td>
        <td class="angka"><?= rp((float) $k['koefisien'] * (float) ($k['tarif'] ?? 0)) ?></td>
        <?php if (! empty($acuanInfo)): $ak = $acuanPerKomp[(int) $k['id']] ?? ['koef' => null, 'nilai' => null, 'banyak' => false]; ?>
        <td class="angka"><?= $ak['koef'] !== null ? esc(rtrim(rtrim(number_format($ak['koef'], 6, ',', '.'), '0'), ',')) . ($ak['banyak'] ? ' *' : '') : '—' ?></td>
        <td class="angka"><?= $ak['nilai'] !== null ? rp($ak['nilai']) : '—' ?></td>
        <?php endif; ?>
        <td style="white-space:nowrap">
          <button type="button" class="tombol kecil abu" data-ubah-komp data-id="<?= (int) $k['id'] ?>" data-nama="<?= esc($k['nama_master'] ?? $k['nama'], 'attr') ?>" data-jenis="<?= esc(ucfirst($k['jenis']), 'attr') ?>" data-koefisien="<?= esc((string) $k['koefisien'], 'attr') ?>" data-satuan="<?= esc($k['satuan'] ?? '', 'attr') ?>" data-tarif="<?= (int) ($k['tarif'] ?? 0) ?>"<?php if (! empty($acuanInfo) && ($acuanPerKomp[(int) $k['id']]['koef'] ?? null) !== null): ?> data-koef-acuan="<?= esc(rtrim(rtrim(number_format($acuanPerKomp[(int) $k['id']]['koef'], 6, ',', '.'), '0'), ','), 'attr') ?>"<?php endif; ?>>Ubah</button>
          <button type="submit" class="tombol kecil bahaya" form="hapus-komp-<?= (int) $k['id'] ?>">Hapus</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php $totalAcuan = 0.0; foreach ($acuanPerKomp ?? [] as $ak) { $totalAcuan += (float) ($ak['nilai'] ?? 0); } ?>
      <tr class="baris-total"><td colspan="5"><strong>Total komponen per satuan</strong></td><td class="angka"><strong><?= rp($total) ?></strong></td><?php if (! empty($acuanInfo)): ?><td></td><td class="angka"><strong><?= rp($totalAcuan) ?></strong></td><?php endif; ?><td></td></tr>
    </table></div>
    <?php if (! empty($acuanInfo) && array_filter($acuanPerKomp ?? [], static fn ($ak) => $ak['banyak'] && $ak['koef'] !== null)): ?>
    <p class="kecil-teks" style="margin:6px 0 0">* Koefisien acuan bertanda bintang adalah total untuk seluruh grup komponen sejenis pada analisa ini (grup berisi lebih dari satu komponen).</p>
    <?php endif; ?>
    <?php foreach ($komponen as $k): ?>
    <form id="hapus-komp-<?= (int) $k['id'] ?>" method="post" action="<?= base_url('analisa/' . $a['id'] . '/komponen-hapus/' . $k['id']) ?>" data-konfirmasi="Hapus komponen <?= esc($k['nama']) ?> dari analisa ini?" style="display:none"><?= csrf_field() ?></form>
    <?php endforeach; ?>
  </div>
</div>
