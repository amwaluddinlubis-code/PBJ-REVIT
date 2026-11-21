<?php
$rp = static fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
$pilihSekolah = static function () use ($semuaSekolah, $sekolahAktif) {
    $html = '<label>Sekolah tujuan impor<select name="sekolah_id">';
    foreach ($semuaSekolah ?? [] as $sk) {
        $sel = (int) ($sekolahAktif['id'] ?? 0) === (int) $sk['id'] ? ' selected' : '';
        $html .= '<option value="' . (int) $sk['id'] . '"' . $sel . '>' . esc($sk['nama_sekolah']) . '</option>';
    }
    return $html . '</select></label>';
};
?>
<div class="panel">
  <h2>Impor Dokumen Perencana — Satu Klik</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Dokumen RAB perencana dibaca otomatis: daftar harga bahan &amp; upah, analisa AHSP, BOQ tiap gedung, dan total bantuan dari Rekap RAB. Pilih sekolah tujuan, lalu pakai salah satu dari tiga cara di bawah. Mengimpor ulang berkas yang sama aman — gedung dari sheet yang sama diperbarui, bukan digandakan.</p>

  <h3 style="margin:18px 0 8px">Cara 1 — Dari Folder (disarankan, tanpa batas ukuran)</h3>
  <p class="kecil-teks" style="margin-top:0">Salin berkas .xlsx ke folder ini lewat Explorer: <code style="word-break:break-all"><?= esc($folder_impor) ?></code></p>
  <?php if ($berkas_folder !== []): ?>
  <form method="post" action="<?= base_url('impor/folder') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <?= $pilihSekolah() ?>
      <label>Berkas di folder impor
        <select name="berkas">
          <?php foreach ($berkas_folder as $bf): ?><option value="<?= esc($bf['nama']) ?>"><?= esc($bf['nama']) ?> — <?= number_format($bf['ukuran'] / 1048576, 1, ',', '.') ?> MB · <?= esc($bf['tanggal']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <div class="aksi md:col-span-2"><button type="submit">Impor Berkas Ini</button></div>
    </div>
  </form>
  <?php else: ?>
  <p class="kecil-teks">Belum ada berkas .xlsx di folder itu. Salin dulu berkasnya, lalu muat ulang halaman ini.</p>
  <?php endif; ?>

  <h3 style="margin:20px 0 8px">Cara 2 — Impor Ulang Berkas Terakhir</h3>
  <?php if ($berkas_terakhir !== null): ?>
  <form method="post" action="<?= base_url('impor/ulang') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <?= $pilihSekolah() ?>
      <label>Berkas tersimpan di server<input type="text" value="<?= esc($berkas_terakhir['nama']) ?> · <?= esc($berkas_terakhir['tanggal']) ?>" disabled></label>
      <div class="aksi md:col-span-2"><button type="submit">Impor Ulang</button></div>
    </div>
  </form>
  <?php else: ?>
  <p class="kecil-teks">Belum ada berkas unggahan yang tersimpan di server.</p>
  <?php endif; ?>

  <h3 style="margin:20px 0 8px">Cara 3 — Unggah Berkas</h3>
  <form method="post" action="<?= base_url('impor/proses') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <?= $pilihSekolah() ?>
      <label>Berkas dokumen perencana (.xlsx)<input type="file" name="berkas" accept=".xlsx" required></label>
      <div class="aksi md:col-span-2"><button type="submit">Unggah &amp; Impor</button></div>
    </div>
    <p class="kecil-teks">Cara unggah kena batas ukuran PHP server — bila berkas besar gagal, pakai Cara 1 (folder). Impor workbook besar berjalan beberapa detik sampai ±1 menit; jangan tutup halaman selama proses.</p>
  </form>
</div>

<?php if ($tak_tertaut !== []): ?>
<div class="panel">
  <h2>Komponen Belum Tertaut ke Master Bahan — <?= count($tak_tertaut) ?> nama</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Komponen ini belum punya padanan di master bahan sehingga belum terhitung di kebutuhan. Aplikasi menyarankan padanan terdekat — periksa, lalu tautkan satu-satu atau massal untuk saran yang meyakinkan (skor ≥ 80).</p>
  <?php if ($jumlah_massal > 0): ?>
  <form method="post" action="<?= base_url('impor/tautkan-massal') ?>" onsubmit="return confirm('Tautkan <?= (int) $jumlah_massal ?> nama komponen sesuai saran skor ≥ 80?')">
    <?= csrf_field() ?>
    <div class="aksi" style="margin:0 0 12px"><button type="submit">Tautkan Massal Saran ≥ 80 (<?= (int) $jumlah_massal ?> nama)</button></div>
  </form>
  <?php endif; ?>
  <datalist id="daftar-bahan">
    <?php foreach ($semua_bahan as $b): ?><option value="<?= esc($b['kode']) ?> — <?= esc($b['nama']) ?>" data-id="<?= (int) $b['id'] ?>"></option><?php endforeach; ?>
  </datalist>
  <table data-paginasi="25">
    <tr><th>Komponen (dari analisa)</th><th class="angka">Dipakai</th><th>Saran Padanan Master</th><th style="width:130px">Aksi</th></tr>
    <?php foreach ($tak_tertaut as $t): ?>
    <tr>
      <td><?= esc($t['nama']) ?> <span class="kecil-teks">(<?= esc($t['satuan'] ?? '') ?>)</span></td>
      <td class="angka"><?= (int) $t['dipakai'] ?> analisa</td>
      <td>
        <form method="post" action="<?= base_url('impor/tautkan') ?>" class="form-tautkan" style="display:flex;gap:6px;align-items:center">
          <?= csrf_field() ?>
          <input type="hidden" name="nama_komponen" value="<?= esc($t['nama']) ?>">
          <input type="hidden" name="bahan_id" value="<?= $t['saran'] !== null ? (int) $t['saran']['id'] : 0 ?>">
          <input type="text" name="bahan_teks" list="daftar-bahan" style="margin:0" value="<?= $t['saran'] !== null ? esc($t['saran']['kode'] . ' — ' . $t['saran']['nama']) : '' ?>" placeholder="Ketik kode / nama bahan…">
          <?php if ($t['saran'] !== null): ?><span class="lencana <?= $t['skor'] >= 80 ? 'hijau' : 'abu' ?>"><?= (int) $t['skor'] ?>%</span><?php endif; ?>
          <button class="kecil" type="submit">Tautkan</button>
        </form>
      </td>
      <td class="kecil-teks"><?= $t['saran'] !== null ? 'Saran: ' . esc($t['saran']['kode']) : 'Tidak ada saran ≥ 50%' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<script>
const petaBahan = {};
document.querySelectorAll('#daftar-bahan option').forEach(o => { petaBahan[o.value] = o.dataset.id; });
document.querySelectorAll('.form-tautkan').forEach(f => {
  f.addEventListener('submit', () => {
    const teks = f.querySelector('input[name=bahan_teks]').value;
    if (petaBahan[teks]) f.querySelector('input[name=bahan_id]').value = petaBahan[teks];
  });
});
</script>
<?php endif; ?>

<?php if (! empty($log)): ?>
<div class="panel">
  <h2>Riwayat &amp; Hasil Verifikasi Impor</h2>
  <?php foreach ($log as $l): ?>
    <p style="margin:6px 0"><b><?= esc($l['berkas']) ?></b> · <?= esc($l['created_at'] ?? '') ?></p>
    <?php if (! empty($l['ringkasan'])): $r = $l['ringkasan']; ?>
    <table>
      <tr><th>Gedung (sheet)</th><th class="angka">Pekerjaan</th><th class="angka">Total Terhitung</th><th class="angka">GRAND TOTAL Sheet</th><th class="angka">Selisih</th></tr>
      <?php foreach ($r['gedung'] ?? [] as $g): $selisih = (float) $g['total_hitung'] - (float) $g['total_sheet']; ?>
      <tr>
        <td><a href="<?= base_url('gedung/' . $g['gedung_id']) ?>"><?= esc($g['nama']) ?></a></td>
        <td class="angka"><?= (int) $g['pekerjaan'] ?></td>
        <td class="angka">Rp <?= number_format((float) $g['total_hitung'], 0, ',', '.') ?></td>
        <td class="angka">Rp <?= number_format((float) $g['total_sheet'], 0, ',', '.') ?></td>
        <td class="angka"><?= abs($selisih) < 1000 ? '<span class="lencana hijau">Sesuai</span>' : 'Rp ' . number_format($selisih, 0, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <p class="kecil-teks">Analisa terbaca: <?= (int) ($r['analisa'] ?? 0) ?> · Komponen: <?= (int) ($r['komponen'] ?? 0) ?> · Komponen bahan belum tertaut ke master: <?= (int) ($r['komponen_tak_tertaut'] ?? 0) ?> · Bahan baru: <?= (int) ($r['bahan_baru'] ?? 0) ?> · Jenis upah: <?= (int) ($r['upah'] ?? 0) ?> · Total anggaran (Rekap RAB): Rp <?= number_format((float) ($r['total_anggaran'] ?? 0), 0, ',', '.') ?><?= ! empty($r['npsn']) ? ' · NPSN dokumen: ' . esc($r['npsn']) : '' ?></p>
    <?php endif; ?>
  <?php endforeach; ?>
</div>
<?php endif; ?>
