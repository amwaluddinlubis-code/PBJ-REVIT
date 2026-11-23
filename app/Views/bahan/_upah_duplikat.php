<?php /* Partial bahan/_panel_upah: blok Duplikat Jenis Tenaga + form gabung per grup. */ ?>
<?php if (! empty($duplikatUpah)): ?>
  <h3 style="margin-bottom:6px">Duplikat Jenis Tenaga</h3>
  <?php foreach ($duplikatUpah as $grup): ?>
  <form method="post" action="<?= base_url('bahan/upah-gabung') ?>" data-konfirmasi="Gabungkan duplikat jenis tenaga ini mengikuti tarif baris utama?">
    <?= csrf_field() ?>
    <table>
      <tr><th style="width:36px">Utama</th><th>Jenis</th><th>Satuan</th><th class="angka">Tarif</th></tr>
      <?php foreach ($grup['anggota'] as $i => $u): ?>
      <tr>
        <td><input type="radio" name="utama" value="<?= (int) $u['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>></td>
        <td><?= esc($u['jenis']) ?><input type="hidden" name="anggota[]" value="<?= (int) $u['id'] ?>"></td>
        <td><?= esc($u['satuan'] ?? 'OH') ?></td>
        <td class="angka"><?= rp($u['tarif']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:8px 0">
      <label>Tarif final (Rp) <input type="text" data-uang name="tarif_final" value="<?= (int) $grup['anggota'][0]['tarif'] ?>"></label>
      <span class="kecil-teks">Tarif final ini yang menjadi acuan Master Upah setelah digabung.</span>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit" class="kecil">Perbaiki &amp; Gabungkan "<?= esc($grup['nama']) ?>"</button></div>
  </form>
  <?php endforeach; ?>
  <?php endif; ?>
