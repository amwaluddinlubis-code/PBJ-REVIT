<div class="panel">
  <h2 style="margin-top:0">Buat Rencana Mingguan</h2>
  <?php /* Form buat rencana dipertahankan manual (bukan form_modal): sesudah dibuat pengguna langsung diarahkan ke halaman detail untuk mengisi target, sedangkan form_modal hanya memuat ulang halaman daftar. */ ?>
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
  <p style="margin:0 0 10px"><a class="tombol kecil" href="<?= base_url('rencana-mingguan/gabung') ?>">Gabungkan Beberapa Rencana Jadi 1 SPK</a> <span class="kecil-teks">— belanja beberapa gedung disatukan dalam satu SPK, rincian tetap terpisah per gedung.</span></p>
  <?php if ($daftar === []): ?>
  <p class="kecil-teks">Belum ada rencana mingguan. Buat yang pertama lewat formulir di atas.</p>
  <?php else: ?>
  <div class="bungkus-tabel"><?php
  $barisRm = [];
  foreach ($daftar as $r) {
      $barisRm[] = ['sel' => [
          ['html' => '<strong>' . esc($r['gedung_nama']) . '</strong>'],
          date('d/m/Y', strtotime($r['minggu_mulai'])) . ' – ' . date('d/m/Y', strtotime($r['minggu_mulai'] . ' +6 days')),
          ['teks' => (string) ($r['keterangan'] ?? '-'), 'class' => 'potong', 'title' => (string) ($r['keterangan'] ?? '')],
          ['teks' => (string) (int) $r['jml_target'], 'angka' => true],
          ['html' => '<a class="tombol kecil" href="' . base_url('rencana-mingguan/' . $r['id']) . '">Buka</a>'
              . ' <button type="submit" class="tombol kecil abu" form="hapus-rm-' . (int) $r['id'] . '">Hapus</button>', 'gaya' => 'white-space:nowrap'],
      ]];
  }
  echo tabel_bantu([
      'class' => 'satu-baris',
      'cari' => 'Cari rencana…',
      'paginasi' => 25,
      'kolom' => [
          ['judul' => 'Gedung'],
          ['judul' => 'Minggu'],
          ['judul' => 'Keterangan'],
          ['judul' => 'Pekerjaan Ditargetkan', 'angka' => true],
          ['judul' => 'Aksi', 'atribut' => 'data-tanpa-urut'],
      ],
      'baris' => $barisRm,
  ]);
  ?></div>
  <?php foreach ($daftar as $r): ?>
  <form id="hapus-rm-<?= (int) $r['id'] ?>" method="post" action="<?= base_url('rencana-mingguan/' . $r['id'] . '/hapus') ?>" data-konfirmasi="Hapus rencana mingguan <?= esc($r['gedung_nama']) ?> (<?= esc(date('d/m/Y', strtotime($r['minggu_mulai']))) ?>)? Target di dalamnya ikut terhapus." style="display:none"><?= csrf_field() ?></form>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
