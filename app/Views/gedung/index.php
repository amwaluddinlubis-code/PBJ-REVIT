<div class="panel">
  <h2>Daftar Gedung — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <table data-paginasi="25">
    <tr><th>Gedung</th><th class="angka">Pekerjaan</th><th class="angka">Nilai BOQ</th><th class="angka">Realisasi</th><th>Fisik</th><th>Sumber Sheet</th><th>Aksi</th></tr>
    <?php foreach ($gedung as $g): ?>
    <tr>
      <td><b><?= esc($g['nama']) ?></b><?= $g['keterangan'] ? '<br><span class="kecil-teks">' . esc($g['keterangan']) . '</span>' : '' ?></td>
      <td class="angka"><?= (int) $g['jml_pekerjaan'] ?></td>
      <td class="angka">Rp <?= number_format((float) $g['nilai_boq'], 0, ',', '.') ?></td>
      <td class="angka">Rp <?= number_format((float) $g['nilai_realisasi'], 0, ',', '.') ?></td>
      <td><div class="bar<?= $g['persen_fisik'] >= 99.5 ? ' hijau' : '' ?>"><div style="width:<?= min(100, $g['persen_fisik']) ?>%"></div></div><span class="kecil-teks"><?= number_format($g['persen_fisik'], 1, ',', '.') ?>%</span></td>
      <td><?= esc($g['sumber_sheet'] ?? '—') ?></td>
      <td>
        <a class="tombol kecil" href="<?= base_url('gedung/' . $g['id']) ?>">Buka</a>
        <form class="inline" method="post" action="<?= base_url('gedung/' . $g['id'] . '/hapus') ?>" onsubmit="return confirm('Hapus gedung beserta seluruh BOQ dan realisasinya?')">
          <?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if ($gedung === []): ?><tr><td colspan="7">Belum ada gedung. Impor dokumen perencana atau tambah manual di bawah.</td></tr><?php endif; ?>
  </table>
</div>
<div class="panel">
  <h2>Tambah Gedung Manual</h2>
  <form method="post" action="<?= base_url('gedung/simpan') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama gedung<input type="text" name="nama" required placeholder="mis. RKB 2 Kopel"></label>
      <label>Keterangan<input type="text" name="keterangan" placeholder="mis. Ruang Kelas 2 Kopel (18 m × 7 m)"></label>
      <div class="aksi md:col-span-2"><button type="submit">Tambah Gedung</button></div>
    </div>
  </form>
</div>
