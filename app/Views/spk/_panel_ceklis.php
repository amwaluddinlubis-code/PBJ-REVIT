<?php /* Partial spk/detail: panel Ceklis Kelengkapan Dokumen Bukti Belanja. Tabel dirender helper tabel_bantu(). */ ?><details class="panel panel-lipat" data-lipat-id="spk-detail-ceklis-kelengkapan-dokumen-bukti-belanja">
  <summary>Ceklis Kelengkapan Dokumen Bukti Belanja</summary>

  <p class="kecil-teks" style="margin-top:0">Menurut Juknis, pengadaan di luar SIPLah dibuktikan dengan dokumen perencanaan, hasil pembandingan, hasil negosiasi, surat pemesanan, hasil pemeriksaan barang, dan BAST. Status modul terisi otomatis; dokumen fisik ditandai manual.</p>
<?php
$barisCeklis = [];
foreach ($ceklis as $c) {
    if ($c['manual']) {
        $penanda = '<form class="inline" method="post" action="' . base_url('spk/ceklis/' . $s['id']) . '">'
            . csrf_field()
            . '<input type="hidden" name="item" value="' . esc($c['kunci']) . '">'
            . '<label style="display:inline-flex;gap:6px;align-items:center"><input type="checkbox" name="ada" value="1" ' . ($c['ada'] ? 'checked' : '') . ' onchange="this.form.submit()"> Sudah ada (fisik)</label>'
            . '</form>';
    } elseif (! empty($c['jangkar'])) {
        $penanda = '<a class="kecil-teks" href="' . esc($c['jangkar'], 'attr') . '">' . esc($c['label_tautan'] ?? 'Isi di panel') . ' ↓</a>';
    } elseif (! empty($c['tautan'])) {
        $penanda = '<a class="kecil-teks" href="' . base_url($c['tautan']) . '">' . esc($c['label_tautan'] ?? 'Buka modulnya') . ' →</a>';
    } else {
        $penanda = '<span class="kecil-teks">otomatis dari modul</span>';
    }
    $barisCeklis[] = ['sel' => [
        $c['ada'] ? '✓ Ada' : '✗ Belum',
        (string) $c['label'],
        ['html' => $penanda],
    ]];
}
echo tabel_bantu([
    'cari' => 'Cari dokumen…',
    'kolom' => [
        ['judul' => 'Status', 'lebar' => '60px'],
        ['judul' => 'Dokumen'],
        ['judul' => 'Penanda', 'lebar' => '170px'],
    ],
    'baris' => $barisCeklis,
]);
?>
</details>
