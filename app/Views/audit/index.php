<h1>Jejak Audit</h1>
<p class="kecil-teks">Catatan aksi penting yang menyentuh uang dan persetujuan: siapa melakukan apa dan kapan. 500 catatan terakhir untuk sekolah aktif (termasuk catatan masuk akun). Catatan tidak dapat diubah dari aplikasi.</p>

<div class="panel">
  <?php if ($daftar === []): ?>
    <p class="kecil-teks">Belum ada catatan audit.</p>
  <?php else: ?>
  <div class="bungkus-tabel">
    <table class="satu-baris" data-cari data-paginasi="25">
      <thead><tr><th>Waktu</th><th>Pengguna</th><th>Entitas</th><th>Aksi</th><th>Keterangan</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($daftar as $a): ?>
        <tr>
          <td><?= esc($a['created_at']) ?></td>
          <td><?= esc($a['username'] ?? '-') ?></td>
          <td><?= esc($a['entitas']) ?><?= $a['entitas_id'] ? ' #' . (int) $a['entitas_id'] : '' ?></td>
          <td><?= esc($a['aksi']) ?></td>
          <td class="potong" title="<?= esc($a['keterangan'] ?? '') ?>"><?= esc($a['keterangan'] ?? '-') ?></td>
          <td><?= esc($a['ip'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
