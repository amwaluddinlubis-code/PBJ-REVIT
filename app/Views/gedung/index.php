<div class="panel" data-bulk-akar x-data="komponenBulk('form-bulk-gedung','ids-gedung')">
  <h2>Daftar Gedung — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></h2>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 10px">
    <form id="form-bulk-gedung" method="post" action="<?= base_url('gedung/hapus-massal') ?>" data-konfirmasi-judul="Hapus Gedung Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-gedung"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus {n} gedung terpilih beserta seluruh BOQ dan realisasinya?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('gedung/kosongkan') ?>" data-konfirmasi="Kosongkan daftar gedung sekolah ini? Semua <?= count($gedung) ?> gedung aktif beserta BOQ dan realisasinya akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Daftar Gedung" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Daftar Gedung</button>
    </form>
  </div>
  <table data-paginasi="25" data-cari="Cari gedung…">
    <tr><th style="width:34px"><input type="checkbox" title="Pilih semua gedung" @change="pilihSemua($event)"></th><th>Gedung</th><th class="angka">Pekerjaan</th><th class="angka">Nilai BOQ Asli</th><th class="angka">Nilai Analisa</th><th class="angka">Perubahan</th><th class="angka">Realisasi</th><th>Fisik</th><th>Sumber Sheet</th><th>Aksi</th></tr>
    <?php foreach ($gedung as $g): ?>
    <tr>
      <td><input type="checkbox" data-pilih value="<?= (int) $g['id'] ?>" x-model="terpilih"></td>
      <td><b><?= esc($g['nama']) ?></b><?php if ((int) ($g['didanai'] ?? 1) !== 1): ?> <span style="background:var(--ina-red-100);color:var(--ina-red-700);padding:1px 7px;border-radius:99px;font-size:11px">Tidak didanai</span><?php endif; ?><?= $g['keterangan'] ? '<br><span class="kecil-teks">' . esc($g['keterangan']) . '</span>' : '' ?></td>
      <td class="angka"><?= (int) $g['jml_pekerjaan'] ?></td>
      <td class="angka">Rp <?= number_format((float) $g['boq_asli'], 0, ',', '.') ?></td>
      <td class="angka">Rp <?= number_format((float) $g['nilai_analisa'], 0, ',', '.') ?></td>
      <td class="angka"><?php if (abs($g['perubahan']) < 0.5): ?><span class="kecil-teks">—</span><?php else: ?><span style="color:<?= $g['perubahan'] > 0 ? 'var(--ina-red-700,#a61e1e)' : 'var(--ina-green-700,#1e6b2a)' ?>"><?= $g['perubahan'] > 0 ? '+' : '−' ?>Rp <?= number_format(abs((float) $g['perubahan']), 0, ',', '.') ?></span><?php if ($g['boq_asli'] > 0): ?> <span class="kecil-teks">(<?= number_format(abs($g['perubahan']) / $g['boq_asli'] * 100, 1, ',', '.') ?>%)</span><?php endif; ?><?php endif; ?></td>
      <td class="angka">Rp <?= number_format((float) $g['nilai_realisasi'], 0, ',', '.') ?></td>
      <td><div class="bar<?= $g['persen_fisik'] >= 99.5 ? ' hijau' : '' ?>"><div style="width:<?= min(100, $g['persen_fisik']) ?>%"></div></div><span class="kecil-teks"><?= number_format($g['persen_fisik'], 1, ',', '.') ?>%</span></td>
      <td><?= esc($g['sumber_sheet'] ?? '—') ?></td>
      <td>
        <a class="tombol kecil" href="<?= base_url('gedung/' . $g['id']) ?>">Buka</a>
        <form class="inline" method="post" action="<?= base_url('gedung/' . $g['id'] . '/didanai') ?>">
          <?= csrf_field() ?><input type="hidden" name="didanai" value="<?= (int) ($g['didanai'] ?? 1) === 1 ? 0 : 1 ?>"><button class="kecil abu" type="submit"><?= (int) ($g['didanai'] ?? 1) === 1 ? 'Tandai Tidak Didanai' : 'Tandai Didanai' ?></button>
        </form>
        <form class="inline" method="post" action="<?= base_url('gedung/' . $g['id'] . '/hapus') ?>" data-konfirmasi="Hapus gedung beserta seluruh BOQ dan realisasinya?">
          <?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if ($gedung === []): ?><tr><td colspan="8">Belum ada gedung. Impor dokumen perencana atau tambah manual di bawah.</td></tr><?php endif; ?>
  </table>
</div>
<div class="panel">
  <h2>Tambah Gedung Manual</h2>
  <form method="post" action="<?= base_url('gedung/simpan') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama gedung<input type="text" name="nama" required placeholder="mis. RKB 2 Kopel"></label>
      <label>Keterangan<input type="text" name="keterangan" placeholder="mis. Ruang Kelas 2 Kopel (18 m × 7 m)"></label>
      <label>Nilai BOQ dokumen (Rp)<input type="text" name="nilai_boq" data-uang inputmode="numeric" placeholder="0"></label>
      <div class="aksi md:col-span-2"><button type="submit">Tambah Gedung</button></div>
    </div>
  </form>
</div>
