<?php

$tabAktif = $tab ?? 'boq';
?>
<nav class="remah" aria-label="Jejak halaman"><a href="<?= base_url() ?>">Dashboard</a> <span aria-hidden="true">/</span> <a href="<?= base_url('gedung') ?>">Kelola Gedung</a> <span aria-hidden="true">/</span> <span><?= esc($g['nama']) ?></span></nav>
<div style="margin-bottom:12px">
  <span class="lencana biru">Nilai BOQ <b>&nbsp;<?= rp($g['nilai_boq']) ?></b></span>
  <span class="lencana hijau">Realisasi <b>&nbsp;<?= rp($nilai_realisasi) ?> (<?= number_format($persen_fisik, 1, ',', '.') ?>%)</b></span>
  <?php if (! empty($g['sumber_sheet'])): ?><span class="lencana abu">Sumber sheet: <?= esc($g['sumber_sheet']) ?></span><?php endif; ?>
  <a class="tombol kecil" style="float:right" href="<?= base_url('rencana-belanja?gedung=' . $g['id']) ?>">Buat Rencana Belanja Gedung Ini</a>
  <?php foreach ($template_gedung ?? [] as $tpl): ?>
    <a class="tombol kecil abu" style="float:right;margin-right:6px" href="<?= base_url('template-laporan/render/' . $tpl['id'] . '?gedung=' . $g['id']) ?>" title="Render gedung ini dari Template Laporan">Template: <?= esc($tpl['nama']) ?></a>
  <?php endforeach; ?>
</div>
<div class="tab">
  <a href="<?= base_url('gedung/' . $g['id'] . '/boq') ?>"<?= $tabAktif === 'boq' ? ' class="aktif"' : '' ?>>BOQ Pekerjaan</a>
  <a href="<?= base_url('gedung/' . $g['id'] . '/bahan') ?>"<?= $tabAktif === 'bahan' ? ' class="aktif"' : '' ?>>Kebutuhan Bahan</a>
  <a href="<?= base_url('gedung/' . $g['id'] . '/upah') ?>"<?= $tabAktif === 'upah' ? ' class="aktif"' : '' ?>>Kebutuhan Upah</a>
  <a href="<?= base_url('gedung/' . $g['id'] . '/realisasi') ?>"<?= $tabAktif === 'realisasi' ? ' class="aktif"' : '' ?>>Realisasi</a>
  <a href="<?= base_url('lpj/gedung/' . $g['id']) ?>">LPJ Gedung</a>
</div>

<?php if ($tabAktif === 'boq'): ?>
<?= $this->include('gedung/_tab_boq') ?>
<?= $this->include('gedung/_panel_tambah_pekerjaan') ?>
<?= $this->include('gedung/_panel_ubah_gedung') ?>

<?php elseif ($tabAktif === 'bahan'): ?>
<?= $this->include('gedung/_tab_bahan') ?>
<?= $this->include('gedung/_panel_taktertaut') ?>
<?php elseif ($tabAktif === 'upah'): ?>
<?= $this->include('gedung/_tab_upah') ?>
<?php elseif ($tabAktif === 'realisasi'): ?>
<?= $this->include('gedung/_tab_realisasi') ?>
<?php endif; ?>

<script>
document.querySelectorAll('[data-buka-detail]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var baris = document.querySelector('[data-detail="' + btn.getAttribute('data-buka-detail') + '"]');
    if (!baris) return;
    var buka = baris.classList.toggle('terbuka');
    baris.style.display = buka ? '' : 'none';
    var panah = btn.querySelector('.panah');
    if (panah) panah.innerHTML = buka ? '&#9662;' : '&#9656;';
  });
});
</script>
