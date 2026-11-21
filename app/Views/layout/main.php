<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($judul ?? 'Analisa Perencana') ?></title>
<link rel="stylesheet" href="<?= base_url('assets/idds.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/tailwind.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
</head>
<body>
<?php
$seg = explode('/', uri_string())[0] ?? '';
$aktif = static fn (string $s): string => $seg === $s ? ' class="aktif"' : '';
$ikon = [
  'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
  'impor'  => '<path d="M12 16V4m0 0l-4 4m4-4l4 4"/><path d="M4 16v3a1 1 0 001 1h14a1 1 0 001-1v-3"/>',
  'gedung' => '<path d="M4 21V8l8-5 8 5v13"/><path d="M2 21h20M9 21v-6h6v6"/>',
  'kotak'  => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
  'spk'    => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M10 12h5M10 16h5"/>',
  'survei' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
  'nego'   => '<path d="M8 7h13M8 12h13M8 17h13"/><circle cx="4" cy="7" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="17" r="1"/>',
  'dok'    => '<path d="M9 12h6M9 16h4"/><path d="M14 3H7v18h10V7l-3-4z"/><path d="M14 3v4h3"/>',
  'inv'    => '<path d="M6 3h12v18l-2-1.5L14 21l-2-1.5L10 21l-2-1.5L6 21V3z"/><path d="M9 8h6M9 12h6"/>',
  'jurnal' => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20V3H6.5A2.5 2.5 0 004 5.5v14z"/><path d="M4 19.5A2.5 2.5 0 006.5 22H20v-5"/>',
  'upah'   => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20c.8-3.2 3.6-5 7-5s6.2 1.8 7 5"/>',
  'mat'    => '<path d="M4 7c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3z"/><path d="M4 7v10c0 1.7 3.6 3 8 3s8-1.3 8-3V7"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
  'sisa'   => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v3M12 18.2v3M2.8 12h3M18.2 12h3M5.5 5.5l2.1 2.1M16.4 16.4l2.1 2.1M18.5 5.5l-2.1 2.1M7.6 16.4l-2.1 2.1"/>',
  'rencana'=> '<circle cx="9" cy="20" r="1.6"/><circle cx="17" cy="20" r="1.6"/><path d="M3 3h2l2.4 12h10.4l2.2-9H6"/>',
  'lpj'    => '<path d="M4 19V5m0 14h16"/><path d="M7 15c3-1 4-6 7-7 2-.7 3 1 5 0"/>',
  'gear'   => '<circle cx="12" cy="12" r="3.2"/><path d="M19.4 15a1.7 1.7 0 00.34 1.87l.06.06a2 2 0 11-2.83 2.83l-.06-.06A1.7 1.7 0 0015 19.4a1.7 1.7 0 00-1 1.55V21a2 2 0 11-4 0v-.09A1.7 1.7 0 009 19.4a1.7 1.7 0 00-1.87.34l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.7 1.7 0 004.6 15a1.7 1.7 0 00-1.55-1H3a2 2 0 110-4h.09A1.7 1.7 0 004.6 9a1.7 1.7 0 001-1.87l-.06-.06a2 2 0 112.83-2.83l.06.06A1.7 1.7 0 009 4.6a1.7 1.7 0 001-1.55V3a2 2 0 114 0v.09c0 .68.4 1.3 1 1.51.61.22 1.3.1 1.9-.34l.06-.06a2 2 0 112.83 2.83l-.06.06a1.7 1.7 0 00-.34 1.9V9c.24.6.86 1 1.51 1H21a2 2 0 110 4h-.09c-.65 0-1.27.4-1.51 1z" stroke-linejoin="round"/>',
  'sekolah'=> '<path d="M3 21h18M5 21V9l7-5 7 5v12"/><path d="M9 21v-5h6v5M12 4V2.5"/>',
];
$svg = static fn (string $k): string => '<svg viewBox="0 0 24 24">' . ($ikon[$k] ?? '') . '</svg>';
?>
<div class="kerangka">
  <aside class="samping">
    <div class="logo"><div class="kotak-logo">AP</div><div class="nama-logo">Analisa Perencana<small>Perencanaan · Belanja · LPJ</small></div></div>
    <form method="post" action="<?= base_url('sekolah/pilih') ?>">
      <?= csrf_field() ?>
      <label class="label-samping" for="pilihan-sekolah">Sekolah aktif</label>
      <select id="pilihan-sekolah" name="sekolah_id" onchange="this.form.submit()">
        <?php foreach ($semuaSekolah ?? [] as $sk): ?>
          <option value="<?= (int) $sk['id'] ?>"<?= ((int) ($sekolahAktif['id'] ?? 0) === (int) $sk['id']) ? ' selected' : '' ?>><?= esc($sk['nama_sekolah'] ?? ('Sekolah #' . $sk['id'])) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
    <nav>
      <div class="grup">Menu</div>
      <a href="<?= base_url('/') ?>"<?= $seg === '' ? ' class="aktif"' : '' ?>><?= $svg('grid') ?>Dashboard</a>
      <a href="<?= base_url('impor') ?>"<?= $aktif('impor') ?>><?= $svg('impor') ?>Impor Dokumen Perencana</a>
      <div class="grup">Pembangunan Gedung</div>
      <?php foreach ($semuaGedung ?? [] as $g): ?>
        <a href="<?= base_url('gedung/' . $g['id']) ?>"<?= ($seg === 'gedung' && (explode('/', uri_string())[1] ?? '') === (string) $g['id']) ? ' class="aktif"' : '' ?>><?= $svg('gedung') ?><?= esc($g['nama']) ?><?php if (isset($g['persen_fisik'])): ?><span class="hitung"><?= (int) round((float) $g['persen_fisik']) ?>%</span><?php endif; ?></a>
      <?php endforeach; ?>
      <a href="<?= base_url('gedung') ?>"<?= $aktif('gedung') ?>><?= $svg('gedung') ?>Kelola Gedung</a>
      <div class="grup">Belanja</div>
      <a href="<?= base_url('rencana-belanja') ?>"<?= $aktif('rencana-belanja') ?>><?= $svg('rencana') ?>Rencana Belanja</a>
      <a href="<?= base_url('bahan') ?>"<?= $aktif('bahan') ?>><?= $svg('kotak') ?>Master Bahan</a>
      <a href="<?= base_url('spk') ?>"<?= $aktif('spk') ?>><?= $svg('spk') ?>Register SPK</a>
      <a href="<?= base_url('belum-bernomor') ?>"<?= $aktif('belum-bernomor') ?>><?= $svg('spk') ?>Belum Bernomor SPK</a>
      <a href="<?= base_url('survei') ?>"<?= $aktif('survei') ?>><?= $svg('survei') ?>Survei Harga</a>
      <a href="<?= base_url('negosiasi') ?>"<?= $aktif('negosiasi') ?>><?= $svg('nego') ?>Negosiasi</a>
      <a href="<?= base_url('dokumen') ?>"<?= $aktif('dokumen') ?>><?= $svg('dok') ?>BAP &amp; BAST / Cetak</a>
      <a href="<?= base_url('invoice') ?>"<?= $aktif('invoice') ?>><?= $svg('inv') ?>Invoice</a>
      <a href="<?= base_url('invoice/jurnal') ?>"<?= $aktif('invoice') ?>><?= $svg('jurnal') ?>Jurnal Pembayaran</a>
      <a href="<?= base_url('upah') ?>"<?= $aktif('upah') ?>><?= $svg('upah') ?>Pembayaran Upah</a>
      <div class="grup">Bahan &amp; Laporan</div>
      <a href="<?= base_url('material') ?>"<?= $aktif('material') ?>><?= $svg('mat') ?>Buku Material</a>
      <a href="<?= base_url('sisa-bahan') ?>"<?= $aktif('sisa-bahan') ?>><?= $svg('sisa') ?>Sisa Bahan</a>
      <a href="<?= base_url('lpj') ?>"<?= $aktif('lpj') ?>><?= $svg('lpj') ?>LPJ &amp; Realisasi</a>
      <a href="<?= base_url('laporan') ?>"<?= $aktif('laporan') ?>><?= $svg('dok') ?>Laporan &amp; Ekspor</a>
      <div class="grup">Pengaturan</div>
      <a href="<?= base_url('pengaturan') ?>"<?= $aktif('pengaturan') ?>><?= $svg('gear') ?>Identitas &amp; Pengaturan</a>
      <a href="<?= base_url('sekolah') ?>"<?= $aktif('sekolah') ?>><?= $svg('sekolah') ?>Kelola Sekolah</a>
    </nav>
    <div class="profil">
      <div class="ava"><?= esc(strtoupper(substr($pengguna['nama'] ?? 'A', 0, 1))) ?></div>
      <div class="siapa"><?= esc($pengguna['nama'] ?? '') ?><small><?= esc($sekolahAktif['nama_sekolah'] ?? '') ?> · <a href="<?= base_url('logout') ?>">Keluar</a></small></div>
    </div>
  </aside>
  <div class="utama">
    <div class="topbar">
      <form class="cari" method="get" action="<?= base_url('bahan') ?>">
        <span aria-hidden="true">⌕</span>
        <input type="text" name="q" placeholder="Cari bahan…" value="<?= esc($_GET['q'] ?? '') ?>">
      </form>
      <div class="topbar-kanan">
        <span class="cip-top"><?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></span>
        <div class="ava"><?= esc(strtoupper(substr($pengguna['nama'] ?? 'A', 0, 1))) ?></div>
      </div>
    </div>
    <main class="isi">
      <div class="atas"><?= esc($judul ?? '') ?></div>
      <?php if (session()->getFlashdata('sukses')): ?>
        <div class="pesan sukses"><?= esc(session()->getFlashdata('sukses')) ?></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('galat')): ?>
        <div class="pesan galat"><?= esc(session()->getFlashdata('galat')) ?></div>
      <?php endif; ?>
      <?= $konten ?? '' ?>
    </main>
  </div>
</div>
<script src="<?= base_url('assets/paginasi.js') ?>"></script>
</body>
</html>
