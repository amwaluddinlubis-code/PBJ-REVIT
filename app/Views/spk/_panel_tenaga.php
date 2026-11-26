<?php /* Partial spk/detail: panel Kebutuhan Tenaga Kerja dari baris upah SPK. */ ?><details class="panel panel-lipat" data-lipat-id="spk-detail-kebutuhan-tenaga-kerja">
  <summary>Kebutuhan Tenaga Kerja</summary>

  <p class="kecil-teks" style="margin-top:0">Catatan perkiraan tenaga kerja untuk paket ini — jenis tenaga, keahliannya, kebutuhan OH (orang-hari), dan perkiraan jumlah orang yang harus disediakan panitia. Terisi otomatis bila SPK dibuat dari Rencana Belanja; dapat ditambah atau diubah manual di sini, dan ikut tercetak pada SPK.</p>
  <?php if ($tenaga): ?>
<?php
$barisTenaga = [];
$totOh = 0; $totOrang = 0;
foreach ($tenaga as $t) {
    $totOh += (float) $t['oh'];
    $totOrang += (int) ($t['jumlah_orang'] ?? 0);
    $barisTenaga[] = ['sel' => [
        (string) $t['jenis'],
        (string) ($t['keahlian'] ?? '—'),
        ['teks' => number_format((float) $t['oh'], 2, ',', '.'), 'angka' => true],
        ['teks' => $t['jumlah_orang'] === null ? '—' : (string) (int) $t['jumlah_orang'], 'angka' => true],
        (string) ($t['keterangan'] ?? '—'),
        ['html' => '<form class="inline" method="post" action="' . base_url('spk/tenaga-hapus/' . $t['id']) . '" data-konfirmasi="Hapus baris kebutuhan tenaga ini?">'
            . csrf_field()
            . '<button type="submit" class="kecil bahaya">Hapus</button></form>'],
    ]];
}
$barisTenaga[] = ['total' => [
    ['html' => '<b>TOTAL</b>', 'colspan' => 2, 'gaya' => 'text-align:right'],
    ['html' => '<b>' . number_format($totOh, 2, ',', '.') . ' OH</b>', 'angka' => true],
    ['html' => '<b>' . $totOrang . ' orang</b>', 'angka' => true],
    ['teks' => '', 'colspan' => 2],
]];
echo tabel_bantu([
    'cari' => 'Cari tenaga…',
    'kolom' => [
        ['judul' => 'Jenis Tenaga'],
        ['judul' => 'Keahlian'],
        ['judul' => 'Kebutuhan (OH)', 'angka' => true],
        ['judul' => 'Perkiraan Orang', 'angka' => true],
        ['judul' => 'Keterangan'],
        ['judul' => ''],
    ],
    'baris' => $barisTenaga,
]);
?>
  <?php endif; ?>
  <form method="post" action="<?= base_url('spk/tenaga-tambah/' . $s['id']) ?>" style="margin-top:10px">
    <?= csrf_field() ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
      <label>Jenis<br>
        <select name="jenis" style="width:130px"><option value="Pekerja">Pekerja</option><option value="Tukang">Tukang</option></select>
      </label>
      <label>Keahlian (untuk Tukang)<br><input type="text" name="keahlian" placeholder="mis. Batu, Kayu, Besi, Cat" style="width:190px"></label>
      <label>Kebutuhan (OH)<br><input type="text" name="oh" inputmode="decimal" placeholder="0,00" style="width:110px"></label>
      <label>Perkiraan orang<br><input type="number" name="jumlah_orang" min="0" placeholder="—" style="width:110px"></label>
      <label>Keterangan<br><input type="text" name="keterangan" style="width:200px"></label>
      <button class="tombol kecil" type="submit">+ Tambah Baris</button>
    </div>
  </form>
</details>
