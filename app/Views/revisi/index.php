<div class="panel">
  <h2>Berita Acara Revisi Pekerjaan — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Menurut Juknis, setiap perubahan pekerjaan — penambahan maupun pengurangan volume atau biaya — dibuatkan Berita Acara Revisi Pekerjaan yang ditandatangani Kepala Sekolah, Ketua P2SP, dan Tim Teknis, dan dikonsultasikan kepada PPK melalui fasilitator. Catat revisinya di sini, lalu cetak berita acaranya.</p>
  <form method="post" action="<?= base_url('revisi-pekerjaan/simpan') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Gedung
        <select name="gedung_id" required>
          <option value="">— pilih gedung —</option>
          <?php foreach ($gedung as $g): ?><option value="<?= (int) $g['id'] ?>"><?= esc($g['nama']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Pekerjaan (opsional)
        <select name="pekerjaan_id">
          <option value="">— umum / beberapa pekerjaan —</option>
          <?php foreach ($pekerjaan as $p): ?><option value="<?= (int) $p['id'] ?>"><?= esc($p['nama']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Tanggal Revisi <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required></label>
      <label>Ringkasan Perubahan <input type="text" name="perubahan" placeholder="cth. Volume plesteran +12 m2, biaya +Rp1.850.000"></label>
      <label class="md:col-span-2">Uraian Revisi <textarea name="uraian" rows="3" required placeholder="Alasan perubahan, rincian pekerjaan sebelum dan sesudah revisi, serta dampaknya pada biaya dan jadwal"></textarea></label>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit">Catat Revisi</button></div>
  </form>
</div>

<div class="panel">
  <h2>Riwayat Revisi</h2>
  <table>
    <tr><th>Tanggal</th><th>Gedung / Pekerjaan</th><th>Perubahan</th><th>Uraian</th><th></th></tr>
    <?php foreach ($revisi as $r): ?>
    <tr>
      <td><?= esc($r['tanggal']) ?></td>
      <td><?= esc($r['gedung_nama']) ?><?= $r['pekerjaan_nama'] ? '<br><span class="kecil-teks">' . esc($r['pekerjaan_nama']) . '</span>' : '' ?></td>
      <td><?= esc($r['perubahan'] ?? '—') ?></td>
      <td><?= esc($r['uraian']) ?></td>
      <td style="white-space:nowrap">
        <a class="tombol kecil abu" href="<?= base_url('revisi-pekerjaan/cetak/' . $r['id']) ?>" target="_blank">Cetak BA</a>
        <form class="inline" method="post" action="<?= base_url('revisi-pekerjaan/hapus/' . $r['id']) ?>" data-konfirmasi="Hapus catatan revisi ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if ($revisi === []): ?><tr><td colspan="5">Belum ada revisi pekerjaan yang dicatat.</td></tr><?php endif; ?>
  </table>
</div>
