<nav class="remah" aria-label="Jejak halaman"><a href="<?= base_url() ?>">Dashboard</a> <span aria-hidden="true">/</span> <a href="<?= base_url('spk') ?>">Register SPK</a> <span aria-hidden="true">/</span> <span>SPK Nomor <?= (int) $s['nomor'] ?></span></nav>
<?= $this->include('spk/_panel_ringkasan') ?>
<?= $this->include('spk/_panel_ketentuan') ?>
<?= $this->include('spk/_panel_barang') ?>
<?= $this->include('spk/_panel_tuntaskan') ?>
<?= $this->include('spk/_panel_tenaga') ?>
<?= $this->include('spk/_panel_ceklis') ?>
<?= $this->include('spk/_panel_tambah') ?>
<?= $this->include('spk/_skrip') ?>
