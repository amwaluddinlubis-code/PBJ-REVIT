<?php $tot = 0; ?>
<div class="panel">
  <p>Baris-baris ini sudah punya barang, qty, dan harga, tetapi <strong>belum terhubung ke SPK mana pun</strong> sehingga tidak terbaca register dan dokumen. Pasangkan ke nomor SPK yang benar sesuai dokumen fisiknya. Bila nomornya belum ada, <a href="<?= base_url('spk/baru') ?>">buat SPK baru</a> dulu, atau periksa daftarnya di <a href="<?= base_url('spk') ?>">Register SPK</a>.</p>
<?php
$barisBelum = [];
foreach ($detail as $d) {
    $tot += (float) $d['total'];
    $opsiSpk = '';
    foreach ($spk as $s) {
        $opsiSpk .= '<option value="' . (int) $s['id'] . '">SPK ' . (int) $s['nomor'] . '</option>';
    }
    $aksiBaris = '<form class="inline" method="post" action="' . base_url('belum-bernomor/pasang/' . $d['id']) . '">'
        . csrf_field()
        . '<select data-select-cari name="spk_id" style="width:110px"><option value="">— pilih —</option>' . $opsiSpk . '</select>'
        . ' <button type="submit" class="kecil">Pasang</button></form>'
        . ' <form class="inline" method="post" action="' . base_url('spk/detail-hapus/' . $d['id']) . '" data-konfirmasi="Hapus baris belum bernomor ini? Sesudah dihapus, baris tidak bisa dipasangkan ke SPK mana pun.">'
        . csrf_field()
        . '<button type="submit" class="kecil bahaya">Hapus</button></form>';
    $barisBelum[] = ['sel' => [
        (string) ($d['kode'] ?: '—'),
        (string) $d['nama'],
        ['teks' => number_format((float) $d['qty'], 2, ',', '.'), 'angka' => true],
        (string) ($d['satuan'] ?? ''),
        ['teks' => rp($d['harga']), 'angka' => true],
        ['teks' => rp($d['total']), 'angka' => true],
        ['html' => $aksiBaris],
    ]];
}
$barisBelum[] = ['total' => [
    ['html' => '<b>TOTAL BELUM BERNOMOR</b>', 'colspan' => 5, 'gaya' => 'text-align:right'],
    ['html' => '<b>' . rp($tot) . '</b>', 'angka' => true],
    ['teks' => ''],
]];
echo tabel_bantu([
    'cari' => 'Cari baris belum bernomor…',
    'paginasi' => 10,
    'kolom' => [
        ['judul' => 'Kode'],
        ['judul' => 'Nama'],
        ['judul' => 'Qty', 'angka' => true],
        ['judul' => 'Satuan'],
        ['judul' => 'Harga', 'angka' => true],
        ['judul' => 'Total', 'angka' => true],
        ['judul' => 'Pasangkan ke SPK'],
    ],
    'baris' => $barisBelum,
]);
?>
</div>
