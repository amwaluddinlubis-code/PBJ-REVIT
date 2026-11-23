<?php
/**
 * Partial layout: kolom utama (topbar, judul halaman, wadah toast,
 * dan konten halaman). Menutup </main>, </div> utama, </div> kerangka
 * yang dibuka partial _sidebar.
 */
$svg = static fn (string $k): string => ikonSvg($k);
?>
  <div class="utama">
    <div class="topbar">
      <button type="button" class="tombol-samping" id="tombol-samping" aria-controls="samping" aria-label="Tampilkan / sembunyikan menu samping" aria-expanded="false" title="Menu samping">
        <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <form class="cari" method="get" action="<?= base_url('bahan') ?>">
        <span aria-hidden="true">⌕</span>
        <input type="text" name="q" aria-label="Cari bahan" placeholder="Cari bahan di master…" value="<?= esc($q ?? '') ?>">
      </form>
      <div class="topbar-kanan">
        <span class="cip-top"><?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></span>
        <div class="grup-font" role="group" aria-label="Ukuran huruf">
          <button type="button" id="font-out" title="Perkecil huruf">A−</button>
          <span id="font-label">100%</span>
          <button type="button" id="font-in" title="Perbesar huruf">A+</button>
        </div>
        <button type="button" class="tombol-ikon-top" id="tombol-tema" title="Tema gelap / terang" aria-label="Ganti tema">
          <span class="ikon-bulan"><?= $svg('bulan') ?></span><span class="ikon-matahari"><?= $svg('matahari') ?></span>
        </button>
        <div class="user-menu">
          <button type="button" class="tombol-user" id="tombol-user" aria-haspopup="true" aria-expanded="false">
            <span class="ava"><?= esc(strtoupper(substr($pengguna['nama'] ?? 'A', 0, 1))) ?></span>
            <span class="user-ringkas"><b><?= esc($pengguna['nama'] ?? '') ?></b><small><?= esc(ucfirst($pengguna['peran'] ?? '')) ?></small></span>
            <svg class="panah" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="dropdown-user" id="dropdown-user">
            <div class="dd-kepala">
              <span class="ava besar"><?= esc(strtoupper(substr($pengguna['nama'] ?? 'A', 0, 1))) ?></span>
              <div><b><?= esc($pengguna['nama'] ?? '') ?></b><small>@<?= esc($pengguna['username'] ?? '') ?> · <?= esc(ucfirst($pengguna['peran'] ?? '')) ?></small></div>
            </div>
            <a href="<?= base_url('profil') ?>"><?= $svg('user') ?>Profil Saya</a>
            <?php if (($pengguna['peran'] ?? '') === 'admin'): ?>
            <a href="<?= base_url('pengguna') ?>"><?= $svg('users') ?>Manajemen User</a>
            <?php endif; ?>
            <div class="dd-garis"></div>
            <a href="<?= base_url('logout') ?>" class="dd-keluar"><?= $svg('keluar') ?>Keluar</a>
          </div>
        </div>
      </div>
    </div>
    <main class="isi">
      <div class="atas"><?= esc($judul ?? '') ?></div>
      <div id="wadah-toast" aria-live="polite">
        <?php if (session()->getFlashdata('sukses')): ?>
          <div class="toast sukses" role="status"><span class="toast-ikon">✓</span><div class="toast-isi"><strong>Berhasil</strong><p><?= esc(session()->getFlashdata('sukses')) ?></p></div><button type="button" class="toast-tutup" aria-label="Tutup notifikasi">×</button></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('galat')): ?>
          <div class="toast galat" role="alert"><span class="toast-ikon">!</span><div class="toast-isi"><strong>Gagal</strong><p><?= esc(session()->getFlashdata('galat')) ?></p></div><button type="button" class="toast-tutup" aria-label="Tutup notifikasi">×</button></div>
        <?php endif; ?>
      </div>
      <?= $konten ?? '' ?>
    </main>
  </div>
</div>
