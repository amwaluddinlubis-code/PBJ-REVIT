<table data-paginasi="10">
  <tr>
    <th style="width:34px"><input type="checkbox" title="Pilih semua bahan pada tabel ini" @change="pilihSemua($event)"></th>
    <th>Kode</th><th>Nama Bahan</th><th>Satuan</th><th class="angka">Harga RAB</th><th>Tarif Analisa per Gedung</th><th class="angka">Qty RAB</th><th class="angka">Anggaran</th><th class="angka">Terpakai (SPK)</th><th class="angka">Sisa</th><th></th>
  </tr>
  <?php foreach ($bahan as $b): $sisa = (float) $b['qty_total'] - (float) $b['terpakai']; ?>
  <tr<?= $sisa < 0 ? ' class="negatif"' : '' ?>>
    <td><input type="checkbox" data-pilih-bahan value="<?= (int) $b['id'] ?>" x-model="terpilih"></td>
    <td><?= esc($b['kode']) ?></td>
    <td><?= esc($b['nama']) ?></td>
    <td><?= esc($b['satuan'] ?? '') ?></td>
    <td class="angka"><?= rp($b['harga_rab']) ?></td>
    <td>
      <?php if (($b['tarif_gedung'] ?? []) === []): ?>
        <span style="color:var(--ta-teks-3)">—</span>
      <?php else: ?>
        <?php if (($b['status_harga'] ?? null) === 'sama'): ?><span class="lencana hijau">Sama dengan Bahan</span>
        <?php elseif (($b['status_harga'] ?? null) === 'beda'): ?><span class="lencana kuning">Beda dengan Bahan</span>
        <?php else: ?><span class="lencana abu">Harga Bahan Masih 0</span><?php endif; ?>
        <?php if (! empty($b['tarif_seragam'])): $n = $b['tarif_seragam']['nilai']; ?>
          <div style="font-size:.92em;white-space:nowrap">Semua Gedung (<?= (int) $b['tarif_seragam']['jumlah'] ?>): <span<?= $n['sama'] ? '' : ' style="font-weight:700"' ?>><?= rp($n['tarif']) ?><?= $n['satuan'] !== '' && $n['satuan'] !== ($b['satuan'] ?? '') ? '/' . esc($n['satuan']) : '' ?></span></div>
        <?php else: ?>
        <?php foreach ($b['tarif_gedung'] as $tg): ?>
          <div style="font-size:.92em;white-space:nowrap"><?= esc($tg['gedung']) ?>: <?php foreach ($tg['nilai'] as $i => $n): ?><?= $i > 0 ? ' · ' : '' ?><span<?= $n['sama'] ? '' : ' style="font-weight:700"' ?>><?= rp($n['tarif']) ?><?= $n['satuan'] !== '' && $n['satuan'] !== ($b['satuan'] ?? '') ? '/' . esc($n['satuan']) : '' ?></span><?php endforeach; ?></div>
        <?php endforeach; ?>
        <?php endif; ?>
      <?php endif; ?>
    </td>
    <td class="angka"><?= number_format((float) $b['qty_total'], 2, ',', '.') ?></td>
    <td class="angka"><?= rp($b['anggaran']) ?></td>
    <td class="angka"><?= number_format((float) $b['terpakai'], 2, ',', '.') ?></td>
    <td class="angka"><?= number_format($sisa, 2, ',', '.') ?></td>
    <td><button type="button" class="kecil" @click="bukaUbah($event.currentTarget.dataset)"
      data-id="<?= (int) $b['id'] ?>" data-kode="<?= esc($b['kode'], 'attr') ?>" data-nama="<?= esc($b['nama'], 'attr') ?>"
      data-satuan="<?= esc($b['satuan'] ?? '', 'attr') ?>" data-harga="<?= (float) $b['harga_rab'] ?>"
      data-qrkb="<?= (float) ($b['qty_rkb'] ?? 0) ?>" data-qadm="<?= (float) ($b['qty_adm'] ?? 0) ?>" data-qtoilet="<?= (float) ($b['qty_toilet'] ?? 0) ?>">Ubah</button></td>
  </tr>
  <?php endforeach; ?>
  <?php if ($bahan === []): ?>
  <tr><td colspan="11">Tidak ada bahan yang cocok. Coba kata kunci lain, atau master memang masih kosong.</td></tr>
  <?php endif; ?>
</table>
