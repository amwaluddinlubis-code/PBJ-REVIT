<?php $rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div class="panel">
  <h2 style="margin-top:0">Buat Rencana Mingguan</h2>
  <form method="post" action="<?= base_url('rencana-mingguan/buat') ?>" style="display:grid;grid-template-columns:2fr 1fr 2fr auto;gap:8px;align-items:end">
    <?= csrf_field() ?>
    <label>Gedung
      <?= select_cari('gedung_id', array_map(static fn ($g) => ['value' => (int) $g['id'], 'label' => $g['nama']], $gedung), null, ['required' => 'required']) ?>
    </label>
    <label>Minggu mulai (Senin)
      <input type="date" name="minggu_mulai" value="<?= esc(date('Y-m-d', strtotime('monday next week'))) ?>" required>
    </label>
    <label>Keterangan
      <input type="text" name="keterangan" placeholder="mis. fokus dinding & plester">
    </label>
    <button type="submit" class="tombol">+ Buat Rencana</button>
  </form>
  <p class="kecil-teks">Satu rencana = satu gedung untuk satu minggu. Isi target volume pekerjaannya, daftar belanja bersih minggu itu terhitung otomatis (kebutuhan − stok − pesanan berjalan).</p>
</div>

<div class="panel">
  <h2 style="margin-top:0">Daftar Rencana Mingguan</h2>
  <?php if ($daftar === []): ?>
  <p class="kecil-teks">Belum ada rencana mingguan. Buat yang pertama lewat formulir di atas.</p>
  <?php else: ?>
  <div class="bungkus-tabel"><table class="satu-baris" data-cari="Cari rencana…" data-paginasi="25">
    <tr><th>Gedung</th><th>Minggu</th><th>Keterangan</th><th class="angka">Pekerjaan Ditargetkan</th><th data-tanpa-urut>Aksi</th></tr>
    <?php foreach ($daftar as $r): ?>
    <tr>
      <td><strong><?= esc($r['gedung_nama']) ?></strong></td>
      <td><?= esc(date('d/m/Y', strtotime($r['minggu_mulai']))) ?> – <?= esc(date('d/m/Y', strtotime($r['minggu_mulai'] . ' +6 days'))) ?></td>
      <td class="potong" title="<?= esc($r['keterangan'] ?? '') ?>"><?= esc($r['keterangan'] ?? '-') ?></td>
      <td class="angka"><?= (int) $r['jml_target'] ?></td>
      <td style="white-space:nowrap">
        <a class="tombol kecil" href="<?= base_url('rencana-mingguan/' . $r['id']) ?>">Buka</a>
        <button type="submit" class="tombol kecil abu" form="hapus-rm-<?= (int) $r['id'] ?>">Hapus</button>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <?php foreach ($daftar as $r): ?>
  <form id="hapus-rm-<?= (int) $r['id'] ?>" method="post" action="<?= base_url('rencana-mingguan/' . $r['id'] . '/hapus') ?>" data-konfirmasi="Hapus rencana mingguan <?= esc($r['gedung_nama']) ?> (<?= esc(date('d/m/Y', strtotime($r['minggu_mulai']))) ?>)? Target di dalamnya ikut terhapus." style="display:none"><?= csrf_field() ?></form>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
