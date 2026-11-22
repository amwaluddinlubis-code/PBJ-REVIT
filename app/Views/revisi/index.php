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

<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-revisi','ids-revisi')">
  <h2>Riwayat Revisi</h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-revisi" method="post" action="<?= base_url('revisi-pekerjaan/hapus-massal') ?>" data-konfirmasi-judul="Hapus Catatan Revisi Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-revisi"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} catatan revisi terpilih?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('revisi-pekerjaan/kosongkan') ?>" data-konfirmasi="Kosongkan riwayat revisi sekolah ini? Semua <?= count($revisi) ?> catatan akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Riwayat Revisi" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Riwayat Revisi</button>
    </form>
  </div>
  <table data-cari="Cari revisi…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua catatan" @change="pilihSemua($event)"></th><th>Tanggal</th><th>Gedung / Pekerjaan</th><th>Perubahan</th><th>Uraian</th><th></th></tr>
    <?php foreach ($revisi as $r): ?>
    <tr>
      <td><input type="checkbox" data-pilih value="<?= (int) $r['id'] ?>" x-model="terpilih"></td>
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
    <?php if ($revisi === []): ?><tr><td colspan="6">Belum ada revisi pekerjaan yang dicatat.</td></tr><?php endif; ?>
  </table>
</div>
