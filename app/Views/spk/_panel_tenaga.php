<?php /* Partial spk/detail: panel Kebutuhan Tenaga Kerja dari baris upah SPK. */ ?><div class="panel">
  <h2>Kebutuhan Tenaga Kerja</h2>
  <p class="kecil-teks" style="margin-top:0">Catatan perkiraan tenaga kerja untuk paket ini — jenis tenaga, keahliannya, kebutuhan OH (orang-hari), dan perkiraan jumlah orang yang harus disediakan panitia. Terisi otomatis bila SPK dibuat dari Rencana Belanja; dapat ditambah atau diubah manual di sini, dan ikut tercetak pada SPK.</p>
  <?php if ($tenaga): ?>
  <table data-cari="Cari tenaga…">
    <tr><th>Jenis Tenaga</th><th>Keahlian</th><th class="angka">Kebutuhan (OH)</th><th class="angka">Perkiraan Orang</th><th>Keterangan</th><th></th></tr>
    <?php $totOh = 0; $totOrang = 0; foreach ($tenaga as $t): $totOh += (float) $t['oh']; $totOrang += (int) ($t['jumlah_orang'] ?? 0); ?>
    <tr>
      <td><?= esc($t['jenis']) ?></td>
      <td><?= esc($t['keahlian'] ?? '—') ?></td>
      <td class="angka"><?= number_format((float) $t['oh'], 2, ',', '.') ?></td>
      <td class="angka"><?= $t['jumlah_orang'] === null ? '—' : (int) $t['jumlah_orang'] ?></td>
      <td><?= esc($t['keterangan'] ?? '—') ?></td>
      <td>
        <form class="inline" method="post" action="<?= base_url('spk/tenaga-hapus/' . $t['id']) ?>" data-konfirmasi="Hapus baris kebutuhan tenaga ini?">
          <?= csrf_field() ?>
          <button type="submit" class="kecil bahaya">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr class="baris-total"><th colspan="2" style="text-align:right">TOTAL</th><th class="angka"><?= number_format($totOh, 2, ',', '.') ?> OH</th><th class="angka"><?= $totOrang ?> orang</th><th colspan="2"></th></tr>
  </table>
  <?php endif; ?>
  <form method="post" action="<?= base_url('spk/tenaga-tambah/' . $s['id']) ?>" style="margin-top:10px">
    <?= csrf_field() ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
      <label>Jenis<br>
        <select name="jenis" style="width:130px"><option value="Pekerja">Pekerja</option><option value="Tukang">Tukang</option></select>
      </label>
      <label>Keahlian (untuk Tukang)<br><input type="text" name="keahlian" placeholder="mis. Batu, Kayu, Besi, Cat" style="width:190px"></label>
      <label>Kebutuhan (OH)<br><input type="text" name="oh" inputmode="decimal" placeholder="0,00" style="width:110px"></label>
      <label>Perkiraan orang<br><input type="number" name="jumlah_orang" min="0" placeholder="—" style="width:110px"></label>
      <label>Keterangan<br><input type="text" name="keterangan" style="width:200px"></label>
      <button class="tombol kecil" type="submit">+ Tambah Baris</button>
    </div>
  </form>
</div>
