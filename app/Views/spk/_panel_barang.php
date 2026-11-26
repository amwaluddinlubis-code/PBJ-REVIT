<?php /* Partial spk/detail: panel baris barang SPK (register detail + ubah/hapus). */ ?>
<details class="panel panel-lipat" data-lipat-id="spk-detail-baris-barang-spk" open>
  <summary>Baris Barang SPK</summary>
<?php if ($detail === []): ?>
  <p style="margin-top:0"><strong>SPK ini belum mempunyai rincian.</strong> Tambahkan lewat formulir Tambah Rincian di bawah halaman ini, atau buat SPK-nya dari <a href="<?= base_url('rencana-belanja') ?>">Rencana Belanja</a> / <a href="<?= base_url('rencana-mingguan') ?>">Rencana Mingguan</a> agar rinciannya terisi otomatis. Tanpa rincian, BAP belum bisa dibuat dari halaman <a href="<?= base_url('dokumen') ?>">Dokumen</a>.</p>
<?php endif; ?>
<?php if (! empty($rincian_terkunci)): ?>
  <p style="margin-top:0"><strong>Rincian terkunci.</strong> <?= esc($rincian_terkunci) ?></p>
<?php endif; ?>
<table data-paginasi="10">
  <tr><th>Kode</th><th>Nama Bahan</th><?php if (! empty($gabungan)): ?><th>Gedung Tujuan</th><?php endif; ?><th class="angka">Qty</th><th>Satuan</th><th class="angka">Harga</th><th class="angka">Total</th><th class="angka">PPN</th><th>Ubah Qty/Harga</th><th></th></tr>
  <?php $grand = 0; $grandPpn = 0; foreach ($detail as $d): $grand += (float) $d['total']; $grandPpn += (float) $d['ppn']; ?>
  <tr>
    <td><?= esc($d['kode']) ?></td>
    <td><?= esc($d['nama']) ?></td>
    <?php if (! empty($gabungan)): ?><td><?= esc($d['gedung_rincian'] ?? '—') ?></td><?php endif; ?>
    <td class="angka"><?= number_format((float) $d['qty'], 2, ',', '.') ?></td>
    <td><?= esc($d['satuan'] ?? '') ?></td>
    <td class="angka"><?= rp($d['harga']) ?></td>
    <td class="angka"><?= rp($d['total']) ?></td>
    <td class="angka"><?= $d['ppn'] ? rp($d['ppn']) : '—' ?></td>
    <td>
      <form class="inline" method="post" action="<?= base_url('spk/detail-ubah/' . $d['id']) ?>">
        <?= csrf_field() ?>
        <input type="number" name="qty" step="0.001" value="<?= esc($d['qty']) ?>" style="width:80px"<?= ! empty($rincian_terkunci) ? ' disabled' : '' ?>>
        <input type="text" inputmode="numeric" data-uang name="harga" step="1" value="<?= esc($d['harga']) ?>" style="width:110px"<?= ! empty($rincian_terkunci) ? ' disabled' : '' ?>>
        <label class="kecil-teks"><input type="checkbox" name="pajak" value="1" style="width:auto"<?= $d['pajak'] ? ' checked' : '' ?><?= ! empty($rincian_terkunci) ? ' disabled' : '' ?>> kena PPN</label>
        <button type="submit" class="kecil abu"<?= ! empty($rincian_terkunci) ? ' disabled' : '' ?>>Simpan</button>
      </form>
    </td>
    <td>
      <form class="inline" method="post" action="<?= base_url('spk/detail-hapus/' . $d['id']) ?>" data-konfirmasi="Hapus baris ini?">
        <?= csrf_field() ?>
        <button type="submit" class="kecil bahaya"<?= ! empty($rincian_terkunci) ? ' disabled' : '' ?>>Hapus</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <tr class="baris-total"><th colspan="5" style="text-align:right">TOTAL</th><th class="angka"><?= rp($grand) ?></th><th class="angka"><?= rp($grandPpn) ?></th><th colspan="2"></th></tr>
</table>
</details>
