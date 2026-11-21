<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); $tot = 0; ?>
<div class="panel">
  <p>Baris-baris ini sudah punya barang, qty, dan harga, tetapi <strong>belum terhubung ke SPK mana pun</strong> sehingga tidak terbaca register dan dokumen. Pasangkan ke nomor SPK yang benar sesuai dokumen fisiknya.</p>
  <table>
    <tr><th>Kode</th><th>Nama</th><th class="angka">Qty</th><th>Satuan</th><th class="angka">Harga</th><th class="angka">Total</th><th>Pasangkan ke SPK</th></tr>
    <?php foreach ($detail as $d): $tot += (float) $d['total']; ?>
    <tr>
      <td><?= esc($d['kode'] ?: '—') ?></td>
      <td><?= esc($d['nama']) ?></td>
      <td class="angka"><?= number_format((float) $d['qty'], 2, ',', '.') ?></td>
      <td><?= esc($d['satuan'] ?? '') ?></td>
      <td class="angka"><?= $rp($d['harga']) ?></td>
      <td class="angka"><?= $rp($d['total']) ?></td>
      <td>
        <form class="inline" method="post" action="<?= base_url('belum-bernomor/pasang/' . $d['id']) ?>">
          <?= csrf_field() ?>
          <select name="spk_id" style="width:110px">
            <option value="">— pilih —</option>
            <?php foreach ($spk as $s): ?><option value="<?= (int) $s['id'] ?>">SPK <?= (int) $s['nomor'] ?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="kecil">Pasang</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr><th colspan="5" style="text-align:right">TOTAL BELUM BERNOMOR</th><th class="angka"><?= $rp($tot) ?></th><th></th></tr>
  </table>
</div>
