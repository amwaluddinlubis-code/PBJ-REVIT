<?php /* Partial gedung/detail tab Bahan: panel Komponen Belum Tertaut ke Master Bahan + form tautkan. */ ?><?php if (! empty($kebutuhan['tak_tertaut'])): ?>
<div class="panel">
  <h2>Komponen Belum Tertaut ke Master Bahan (<?= count($kebutuhan['tak_tertaut']) ?>)</h2>
  <p style="margin-top:0" class="kecil-teks">Komponen AHSP berikut belum punya padanan di master bahan sehingga belum terhitung. Tautkan ke bahan yang sesuai — penautan berlaku untuk semua gedung.</p>
  <div class="bungkus-tabel"><table class="satu-baris" data-paginasi="25" data-cari="Cari komponen…">
    <tr><th>Komponen AHSP</th><th class="angka">Kebutuhan</th><th style="width:46%">Tautkan ke Bahan Master</th></tr>
    <?php foreach ($kebutuhan['tak_tertaut'] as $t): ?>
    <tr>
      <td class="potong" title="<?= esc($t['nama']) ?>"><?= esc($t['nama']) ?></td>
      <td class="angka"><?= number_format((float) $t['qty_butuh'], 2, ',', '.') ?></td>
      <td>
        <form method="post" action="<?= base_url('gedung/tautkan') ?>" style="display:flex;gap:6px">
          <?= csrf_field() ?>
          <input type="hidden" name="nama_komponen" value="<?= esc($t['nama']) ?>">
          <input type="hidden" name="gedung_id" value="<?= (int) $g['id'] ?>">
          <select name="bahan_id" required style="margin:0" data-select-cari data-placeholder="— pilih bahan —">
            <option value="">— pilih bahan —</option>
            <?php foreach ($semuaBahan as $sb): ?><option value="<?= (int) $sb['id'] ?>"><?= esc($sb['kode']) ?> — <?= esc($sb['nama']) ?></option><?php endforeach; ?>
          </select>
          <button class="kecil" type="submit">Tautkan</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php endif; ?>

