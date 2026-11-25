<?php
/**
 * Partial layout: kerangka + bilah samping (logo, pemilih sekolah,
 * navigasi grup menu, profil pengguna). Membuka <div class="kerangka">
 * yang ditutup partial _atas. Variabel diwarisi dari layout/main.php.
 */
$seg = explode('/', uri_string())[0] ?? '';
$aktif = static fn (string $s): string => $seg === $s ? ' class="aktif"' : '';
$svg = static fn (string $k): string => ikonSvg($k);
?>
<div class="kerangka">
  <div class="lapik" id="lapik-samping"></div>
  <aside class="samping" id="samping">
    <div class="logo">
      <div class="baris-ap"><div class="kotak-logo">AP</div><div class="nama-logo">Analisa Perencana<small>Perencanaan · Belanja · LPJ</small></div></div>
      <form method="post" action="<?= base_url('sekolah/pilih') ?>">
        <?= csrf_field() ?>
        <label class="label-samping" for="pilihan-sekolah">Sekolah aktif</label>
        <select data-select-cari id="pilihan-sekolah" name="sekolah_id" onchange="this.form.submit()">
          <?php foreach ($semuaSekolah ?? [] as $sk): ?>
            <option value="<?= (int) $sk['id'] ?>"<?= ((int) ($sekolahAktif['id'] ?? 0) === (int) $sk['id']) ? ' selected' : '' ?>><?= esc($sk['nama_sekolah'] ?? ('Sekolah #' . $sk['id'])) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <nav>
      <a href="<?= base_url('/') ?>"<?= $seg === '' ? ' class="aktif"' : '' ?> title="Dashboard"><?= $svg('grid') ?><span class="judul-induk">Dashboard</span></a>
      <?php
      $uriPenuh = uri_string();
      $indukAktif = [
        'perencanaan' => in_array($seg, ['impor', 'analisa', 'acuan', 'validasi-harga', 'bersih', 'rencana-belanja', 'rencana-mingguan', 'simulasi', 'biaya-non-gedung'], true),
        'gedung'      => in_array($seg, ['gedung', 'foto-progres', 'kurva-s', 'laporan-kemajuan', 'revisi-pekerjaan'], true),
        'belanja'     => in_array($seg, ['spk', 'belum-bernomor', 'survei', 'negosiasi', 'dokumen'], true),
        'material'    => in_array($seg, ['bahan', 'material', 'sisa-bahan'], true),
        'keuangan'    => in_array($seg, ['invoice', 'buku-kas', 'penerimaan-dana', 'bukti-setor', 'upah'], true),
        'laporan'     => in_array($seg, ['lpj', 'laporan', 'template-laporan'], true),
        'pengaturan'  => in_array($seg, ['pengaturan', 'sekolah', 'pengguna', 'profil', 'pemeliharaan-db', 'audit'], true),
      ];
      $bolehMenu = static fn (string $k): bool => ($aksesMenu[$k] ?? true) !== false;
      $grupAda = [
        'perencanaan' => $bolehMenu('impor') || $bolehMenu('analisa') || $bolehMenu('acuan') || $bolehMenu('validasi-harga') || $bolehMenu('bersih') || $bolehMenu('rencana-belanja') || $bolehMenu('rencana-mingguan') || $bolehMenu('simulasi') || $bolehMenu('biaya-non-gedung'),
        'gedung'      => $bolehMenu('gedung') || $bolehMenu('foto-progres') || $bolehMenu('kurva-s') || $bolehMenu('laporan-kemajuan') || $bolehMenu('revisi-pekerjaan'),
        'belanja'     => $bolehMenu('spk') || $bolehMenu('belum-bernomor') || $bolehMenu('survei') || $bolehMenu('negosiasi') || $bolehMenu('dokumen'),
        'material'    => $bolehMenu('bahan') || $bolehMenu('material') || $bolehMenu('sisa-bahan'),
        'keuangan'    => $bolehMenu('penerimaan-dana') || $bolehMenu('invoice') || $bolehMenu('buku-kas') || $bolehMenu('bukti-setor') || $bolehMenu('upah'),
        'laporan'     => $bolehMenu('lpj') || $bolehMenu('laporan') || $bolehMenu('template-laporan'),
        'pengaturan'  => $bolehMenu('pengaturan') || $bolehMenu('sekolah') || (($pengguna['peran'] ?? '') === 'admin'),
      ];
      $induk = static function (string $kunci, string $ikonNama, string $label) use ($indukAktif, $svg): void {
          $buka = $indukAktif[$kunci] ?? false;
          echo '<div class="induk' . ($buka ? ' buka' : '') . '" data-induk="' . $kunci . '">'
             . '<button type="button" class="parent' . ($buka ? ' aktif' : '') . '" title="' . esc($label) . '">' . $svg($ikonNama) . '<span class="judul-induk">' . $label . '</span>'
             . '<svg class="panah" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></button><div class="anak">';
      };
      $tutupInduk = static fn (): string => '</div></div>';
      ?>
      <?php if ($grupAda['perencanaan']): ?>
      <?php $induk('perencanaan', 'rencana', 'Perencanaan'); ?>
        <?php if ($bolehMenu('impor')): ?><a href="<?= base_url('impor') ?>"<?= $aktif('impor') ?>>Impor Dokumen Perencana</a><?php endif; ?>
        <?php if ($bolehMenu('analisa')): ?><a href="<?= base_url('analisa') ?>"<?= $aktif('analisa') ?>>Analisa Pekerjaan</a><?php endif; ?>
        <?php if ($bolehMenu('acuan')): ?><a href="<?= base_url('acuan') ?>"<?= $aktif('acuan') ?>>Acuan AHSP</a><?php endif; ?>
        <?php if ($bolehMenu('validasi-harga')): ?><a href="<?= base_url('validasi-harga') ?>"<?= $aktif('validasi-harga') ?>>Validasi Harga Satuan</a><?php endif; ?>
        <?php if ($bolehMenu('bersih')): ?><a href="<?= base_url('bersih') ?>"<?= $aktif('bersih') ?>>Pembersihan Hasil Impor</a><?php endif; ?>
        <?php if ($bolehMenu('rencana-belanja')): ?><a href="<?= base_url('rencana-belanja') ?>"<?= $aktif('rencana-belanja') ?>>Rencana Belanja</a><?php endif; ?>
        <?php if ($bolehMenu('rencana-mingguan')): ?><a href="<?= base_url('rencana-mingguan') ?>"<?= $aktif('rencana-mingguan') ?>>Rencana Mingguan</a><?php endif; ?>
        <?php if ($bolehMenu('simulasi')): ?><a href="<?= base_url('simulasi') ?>"<?= $aktif('simulasi') ?>>Simulasi Mingguan</a><?php endif; ?>
        <?php if ($bolehMenu('biaya-non-gedung')): ?><a href="<?= base_url('biaya-non-gedung') ?>"<?= $aktif('biaya-non-gedung') ?>>Biaya Non-Gedung</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['gedung']): ?>
      <?php $induk('gedung', 'gedung', 'Pembangunan Gedung'); ?>
        <?php if ($bolehMenu('gedung')): ?><a href="<?= base_url('gedung') ?>"<?= $aktif('gedung') ?>>Kelola Gedung</a><?php endif; ?>
        <?php if ($bolehMenu('gedung')): ?>
        <?php foreach ($semuaGedung ?? [] as $g): ?>
          <a href="<?= base_url('gedung/' . $g['id']) ?>"<?= ($seg === 'gedung' && (explode('/', $uriPenuh)[1] ?? '') === (string) $g['id']) ? ' class="aktif"' : '' ?>><?= esc($g['nama']) ?><?php if (isset($g['persen_fisik'])): ?><span class="hitung"><?= (int) round((float) $g['persen_fisik']) ?>%</span><?php endif; ?></a>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($bolehMenu('foto-progres')): ?><a href="<?= base_url('foto-progres') ?>"<?= $aktif('foto-progres') ?>>Foto Progres</a><?php endif; ?>
        <?php if ($bolehMenu('kurva-s')): ?><a href="<?= base_url('kurva-s') ?>"<?= $aktif('kurva-s') ?>>Kurva S</a><?php endif; ?>
        <?php if ($bolehMenu('laporan-kemajuan')): ?><a href="<?= base_url('laporan-kemajuan') ?>"<?= $aktif('laporan-kemajuan') ?>>Laporan Kemajuan</a><?php endif; ?>
        <?php if ($bolehMenu('revisi-pekerjaan')): ?><a href="<?= base_url('revisi-pekerjaan') ?>"<?= $aktif('revisi-pekerjaan') ?>>BA Revisi Pekerjaan</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['belanja']): ?>
      <?php $induk('belanja', 'spk', 'Belanja'); ?>
        <?php if ($bolehMenu('spk')): ?><a href="<?= base_url('spk') ?>"<?= $aktif('spk') ?>>Register SPK</a><?php endif; ?>
        <?php if ($bolehMenu('belum-bernomor')): ?><a href="<?= base_url('belum-bernomor') ?>"<?= $aktif('belum-bernomor') ?>>Belum Bernomor SPK</a><?php endif; ?>
        <?php if ($bolehMenu('survei')): ?><a href="<?= base_url('survei') ?>"<?= $aktif('survei') ?>>Survei Harga</a><?php endif; ?>
        <?php if ($bolehMenu('negosiasi')): ?><a href="<?= base_url('negosiasi') ?>"<?= $aktif('negosiasi') ?>>Negosiasi</a><?php endif; ?>
        <?php if ($bolehMenu('dokumen')): ?><a href="<?= base_url('dokumen') ?>"<?= $aktif('dokumen') ?>>BAP &amp; BAST / Cetak</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['material']): ?>
      <?php $induk('material', 'kotak', 'Material'); ?>
        <?php if ($bolehMenu('bahan')): ?><a href="<?= base_url('bahan') ?>"<?= $aktif('bahan') ?>>Master Bahan &amp; Upah</a><?php endif; ?>
        <?php if ($bolehMenu('material')): ?><a href="<?= base_url('material') ?>"<?= $aktif('material') ?>>Buku Material</a><?php endif; ?>
        <?php if ($bolehMenu('sisa-bahan')): ?><a href="<?= base_url('sisa-bahan') ?>"<?= $aktif('sisa-bahan') ?>>Sisa Bahan</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['keuangan']): ?>
      <?php $induk('keuangan', 'jurnal', 'Keuangan'); ?>
        <?php if ($bolehMenu('penerimaan-dana')): ?><a href="<?= base_url('penerimaan-dana') ?>"<?= $aktif('penerimaan-dana') ?>>Penerimaan Dana</a><?php endif; ?>
        <?php if ($bolehMenu('invoice')): ?><a href="<?= base_url('invoice') ?>"<?= $uriPenuh === 'invoice' ? ' class="aktif"' : '' ?>>Invoice</a><?php endif; ?>
        <?php if ($bolehMenu('invoice')): ?><a href="<?= base_url('invoice/jurnal') ?>"<?= $uriPenuh === 'invoice/jurnal' ? ' class="aktif"' : '' ?>>Jurnal Pembayaran</a><?php endif; ?>
        <?php if ($bolehMenu('buku-kas')): ?><a href="<?= base_url('buku-kas') ?>"<?= $aktif('buku-kas') ?>>Buku Kas (BKU)</a><?php endif; ?>
        <?php if ($bolehMenu('bukti-setor')): ?><a href="<?= base_url('bukti-setor') ?>"<?= $aktif('bukti-setor') ?>>Bukti Setor &amp; Pajak</a><?php endif; ?>
        <?php if ($bolehMenu('upah')): ?><a href="<?= base_url('upah') ?>"<?= $aktif('upah') ?>>Pembayaran Upah</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['laporan']): ?>
      <?php $induk('laporan', 'lpj', 'Laporan'); ?>
        <?php if ($bolehMenu('lpj')): ?><a href="<?= base_url('lpj') ?>"<?= $aktif('lpj') ?>>LPJ &amp; Realisasi</a><?php endif; ?>
        <?php if ($bolehMenu('lpj')): ?><a href="<?= base_url('lpj/rab-gabungan') ?>"<?= $aktif('lpj') ?>>RAB Gabungan</a><?php endif; ?>
        <?php if ($bolehMenu('laporan')): ?><a href="<?= base_url('laporan') ?>"<?= $aktif('laporan') ?>>Laporan &amp; Ekspor</a><?php endif; ?>
        <?php if ($bolehMenu('template-laporan')): ?><a href="<?= base_url('template-laporan') ?>"<?= $aktif('template-laporan') ?>>Template Laporan</a><?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
      <?php if ($grupAda['pengaturan']): ?>
      <?php $induk('pengaturan', 'gear', 'Pengaturan'); ?>
        <?php if ($bolehMenu('pengaturan')): ?><a href="<?= base_url('pengaturan') ?>"<?= $aktif('pengaturan') ?>>Identitas &amp; Pengaturan</a><?php endif; ?>
        <?php if ($bolehMenu('sekolah')): ?><a href="<?= base_url('sekolah') ?>"<?= $aktif('sekolah') ?>>Kelola Sekolah</a><?php endif; ?>
        <?php if (($pengguna['peran'] ?? '') === 'admin'): ?>
        <a href="<?= base_url('pengguna') ?>"<?= $aktif('pengguna') ?>>Manajemen User</a>
        <a href="<?= base_url('pemeliharaan-db') ?>"<?= $aktif('pemeliharaan-db') ?>>Pemeliharaan Database</a>
        <a href="<?= base_url('audit') ?>"<?= $aktif('audit') ?>>Jejak Audit</a>
        <?php if ($bolehMenu('pengaturan')): ?><a href="<?= base_url('pengaturan/akses-menu') ?>"<?= $uriPenuh === 'pengaturan/akses-menu' ? ' class="aktif"' : '' ?>>Akses Menu</a><?php endif; ?>
        <?php endif; ?>
      <?= $tutupInduk() ?>
      <?php endif; ?>
    </nav>
    <div class="profil">
      <div class="ava"><?= esc(strtoupper(substr($pengguna['nama'] ?? 'A', 0, 1))) ?></div>
      <div class="siapa"><?= esc($pengguna['nama'] ?? '') ?><small><?= esc(ucfirst($pengguna['peran'] ?? '')) ?> · <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></small></div>
    </div>
  </aside>
