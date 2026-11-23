<?php /* Partial bahan/index: panel Kelengkapan Harga & Tarif (isi massal harga bahan & tarif upah yang masih kosong). Variabel diwarisi via $this->include(). */ ?><?php if (! empty($bahanTanpaHarga) || ! empty($upahTanpaTarif)): ?>
<details class="panel panel-lipat panel-waspada" data-lipat-id="kelengkapan-harga" <?= (count($bahanTanpaHarga) + count($upahTanpaTarif)) <= 15 ? 'open' : '' ?>>
  <summary>Kelengkapan Harga &amp; Tarif <span class="kecil-teks">(<?= count($bahanTanpaHarga) + count($upahTanpaTarif) ?> isian masih kosong)</span></summary>
  <p class="kecil-teks" style="margin:0 0 10px">Bahan tanpa harga acuan terhitung <strong>Rp 0</strong> di kebutuhan &amp; rencana belanja; jenis upah tanpa tarif terhitung Rp 0 di kebutuhan upah.
  Isi yang kosong di bawah ini lalu klik <strong>Simpan Semua Isian</strong> — yang sudah terisi tidak muncul lagi di panel ini.</p>
  <form method="post" action="<?= base_url('bahan/kelengkapan') ?>">
    <?= csrf_field() ?>
    <?php if (! empty($bahanTanpaHarga)): ?>
    <p style="margin:8px 0 4px"><strong><?= count($bahanTanpaHarga) ?> bahan belum punya harga acuan</strong></p>
    <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari bahan…">
      <tr><th>Kode</th><th>Bahan</th><th>Satuan</th><th style="width:190px">Harga Acuan (Rp)</th></tr>
      <?php foreach ($bahanTanpaHarga as $b): ?>
      <tr>
        <td><?= esc($b['kode']) ?></td>
        <td class="potong" title="<?= esc($b['nama']) ?>"><?= esc($b['nama']) ?></td>
        <td><?= esc($b['satuan']) ?></td>
        <td><input type="text" inputmode="numeric" data-uang name="harga[<?= (int) $b['id'] ?>]" min="1" placeholder="isi harga…" style="width:170px"></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
    <?php if (! empty($upahTanpaTarif)): ?>
    <p style="margin:12px 0 4px"><strong><?= count($upahTanpaTarif) ?> jenis upah belum punya tarif</strong></p>
    <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari jenis tenaga…">
      <tr><th>Jenis Tenaga</th><th style="width:190px">Tarif per Hari (Rp)</th></tr>
      <?php foreach ($upahTanpaTarif as $u): ?>
      <tr>
        <td><?= esc($u['jenis']) ?></td>
        <td><input type="text" inputmode="numeric" data-uang name="tarif[<?= (int) $u['id'] ?>]" min="1" placeholder="isi tarif…" style="width:170px"></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
    <p style="margin:12px 0 0"><button type="submit" class="tombol">Simpan Semua Isian</button></p>
  </form>
</details>
<?php endif; ?>
