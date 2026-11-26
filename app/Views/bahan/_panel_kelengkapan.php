<?php /* Partial bahan/index: panel Kelengkapan Harga & Tarif (isi massal harga bahan & tarif upah yang masih kosong).
   Kedua tabel dirender helper tabel_bantu() (data-cari tetap; sel isian harga/tarif via html).
   Variabel diwarisi via $this->include(). */ ?><?php if (! empty($bahanTanpaHarga) || ! empty($upahTanpaTarif)): ?>
<details class="panel panel-lipat panel-waspada" data-lipat-id="kelengkapan-harga" <?= (count($bahanTanpaHarga) + count($upahTanpaTarif)) <= 15 ? 'open' : '' ?>>
  <summary>Kelengkapan Harga &amp; Tarif <span class="kecil-teks">(<?= count($bahanTanpaHarga) + count($upahTanpaTarif) ?> isian masih kosong)</span></summary>
  <p class="kecil-teks" style="margin:0 0 10px">Bahan tanpa harga acuan terhitung <strong>Rp 0</strong> di kebutuhan &amp; rencana belanja; jenis upah tanpa tarif terhitung Rp 0 di kebutuhan upah.
  Isi yang kosong di bawah ini lalu klik <strong>Simpan Semua Isian</strong> — yang sudah terisi tidak muncul lagi di panel ini.</p>
  <form method="post" action="<?= base_url('bahan/kelengkapan') ?>">
    <?= csrf_field() ?>
    <?php if (! empty($bahanTanpaHarga)): ?>
    <p style="margin:8px 0 4px"><strong><?= count($bahanTanpaHarga) ?> bahan belum punya harga acuan</strong></p>
    <div class="bungkus-tabel"><?php
    $barisHarga = [];
    foreach ($bahanTanpaHarga as $b) {
        $barisHarga[] = ['sel' => [
            (string) $b['kode'],
            ['teks' => (string) $b['nama'], 'class' => 'potong', 'title' => (string) $b['nama']],
            (string) $b['satuan'],
            ['html' => '<input type="text" inputmode="numeric" data-uang name="harga[' . (int) $b['id'] . ']" min="1" placeholder="isi harga…" style="width:170px">'],
        ]];
    }
    echo tabel_bantu([
        'cari' => 'Cari bahan…',
        'class' => 'satu-baris',
        'kolom' => [
            ['judul' => 'Kode'],
            ['judul' => 'Bahan'],
            ['judul' => 'Satuan'],
            ['judul' => 'Harga Acuan (Rp)', 'lebar' => '190px'],
        ],
        'baris' => $barisHarga,
    ]);
    ?></div>
    <?php endif; ?>
    <?php if (! empty($upahTanpaTarif)): ?>
    <p style="margin:12px 0 4px"><strong><?= count($upahTanpaTarif) ?> jenis upah belum punya tarif</strong></p>
    <div class="bungkus-tabel"><?php
    $barisTarif = [];
    foreach ($upahTanpaTarif as $u) {
        $barisTarif[] = ['sel' => [
            (string) $u['jenis'],
            ['html' => '<input type="text" inputmode="numeric" data-uang name="tarif[' . (int) $u['id'] . ']" min="1" placeholder="isi tarif…" style="width:170px">'],
        ]];
    }
    echo tabel_bantu([
        'cari' => 'Cari jenis tenaga…',
        'class' => 'satu-baris',
        'kolom' => [
            ['judul' => 'Jenis Tenaga'],
            ['judul' => 'Tarif per Hari (Rp)', 'lebar' => '190px'],
        ],
        'baris' => $barisTarif,
    ]);
    ?></div>
    <?php endif; ?>
    <p style="margin:12px 0 0"><button type="submit" class="tombol">Simpan Semua Isian</button></p>
  </form>
</details>
<?php endif; ?>
