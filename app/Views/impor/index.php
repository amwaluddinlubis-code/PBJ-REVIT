<div class="panel">
  <h2>Unggah Dokumen Perencana (.xlsx)</h2>
  <p style="margin-top:0;color:var(--ta-teks-2)">Dokumen RAB dari tenaga teknis perencana akan dibaca otomatis: daftar harga bahan &amp; upah, analisa AHSP, BOQ tiap gedung, dan total bantuan dari Rekap RAB. Sheet yang terdeteksi menjadi <b>gedung</b>; mengimpor ulang berkas yang sama memperbarui gedung dari sheet yang sama (pekerjaan lama diganti, nama gedung yang Bapak ubah tetap).</p>
  <form method="post" action="<?= base_url('impor/proses') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Berkas dokumen perencana
      <input type="file" name="berkas" accept=".xlsx" required>
    </label>
    <div class="aksi"><button type="submit">Impor Sekarang</button></div>
    <p class="kecil-teks">Impor workbook besar (±100 sheet) dapat memakan waktu 1–3 menit. Jangan tutup halaman selama proses.</p>
  </form>
</div>

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
