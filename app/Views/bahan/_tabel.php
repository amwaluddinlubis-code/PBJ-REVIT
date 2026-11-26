<?php /* Partial bahan/index + bahan/tabel: tabel daftar bahan dirender
   helper tabel_bantu() (paginasi 10; pencarian lewat fetch Alpine di
   index, bukan data-cari). Baris merah = sisa negatif. */ ?>
<?php
$barisBahan = [];
foreach ($bahan as $b) {
    $sisa = (float) $b['qty_total'] - (float) $b['terpakai'];
    // Sel tarif analisa per gedung: lencana status + daftar tarif (HTML).
    $selTarif = '<span style="color:var(--ta-teks-3)">—</span>';
    if (($b['tarif_gedung'] ?? []) !== []) {
        $selTarif = '';
        if (($b['status_harga'] ?? null) === 'sama') {
            $selTarif .= '<span class="lencana hijau">Sama dengan Bahan</span>';
        } elseif (($b['status_harga'] ?? null) === 'beda') {
            $selTarif .= '<span class="lencana kuning">Beda dengan Bahan</span>';
        } else {
            $selTarif .= '<span class="lencana abu">Harga Bahan Masih 0</span>';
        }
        if (! empty($b['tarif_seragam'])) {
            $n = $b['tarif_seragam']['nilai'];
            $selTarif .= '<div style="font-size:.92em;white-space:nowrap">Semua Gedung (' . (int) $b['tarif_seragam']['jumlah'] . '): <span' . ($n['sama'] ? '' : ' style="font-weight:700"') . '>' . rp($n['tarif']) . ($n['satuan'] !== '' && $n['satuan'] !== ($b['satuan'] ?? '') ? '/' . esc($n['satuan']) : '') . '</span></div>';
        } else {
            foreach ($b['tarif_gedung'] as $tg) {
                $selTarif .= '<div style="font-size:.92em;white-space:nowrap">' . esc($tg['gedung']) . ': ';
                foreach ($tg['nilai'] as $i => $n) {
                    $selTarif .= ($i > 0 ? ' · ' : '') . '<span' . ($n['sama'] ? '' : ' style="font-weight:700"') . '>' . rp($n['tarif']) . ($n['satuan'] !== '' && $n['satuan'] !== ($b['satuan'] ?? '') ? '/' . esc($n['satuan']) : '') . '</span>';
                }
                $selTarif .= '</div>';
            }
        }
    }
    $barisBahan[] = [
        'class' => $sisa < 0 ? 'negatif' : '',
        'sel' => [
            ['html' => '<input type="checkbox" data-pilih-bahan value="' . (int) $b['id'] . '" x-model="terpilih">'],
            (string) $b['kode'],
            (string) $b['nama'],
            (string) ($b['satuan'] ?? ''),
            ['teks' => rp($b['harga_rab']), 'angka' => true],
            ['html' => $selTarif],
            ['teks' => number_format((float) $b['qty_total'], 2, ',', '.'), 'angka' => true],
            ['teks' => rp($b['anggaran']), 'angka' => true],
            ['teks' => number_format((float) $b['terpakai'], 2, ',', '.'), 'angka' => true],
            ['teks' => number_format($sisa, 2, ',', '.'), 'angka' => true],
            ['html' => '<button type="button" class="kecil" @click="bukaUbah($event.currentTarget.dataset)"'
                . ' data-id="' . (int) $b['id'] . '" data-kode="' . esc($b['kode'], 'attr') . '" data-nama="' . esc($b['nama'], 'attr') . '"'
                . ' data-satuan="' . esc($b['satuan'] ?? '', 'attr') . '" data-harga="' . (float) $b['harga_rab'] . '"'
                . ' data-qrkb="' . (float) ($b['qty_rkb'] ?? 0) . '" data-qadm="' . (float) ($b['qty_adm'] ?? 0) . '" data-qtoilet="' . (float) ($b['qty_toilet'] ?? 0) . '">Ubah</button>'],
        ],
    ];
}
if ($bahan === []) {
    $barisBahan[] = ['sel' => [['teks' => 'Tidak ada bahan yang cocok. Coba kata kunci lain, atau master memang masih kosong.', 'colspan' => 11]]];
}
echo tabel_bantu([
    'paginasi' => 10,
    'kolom' => [
        ['judul_html' => '<input type="checkbox" title="Pilih semua bahan pada tabel ini" @change="pilihSemua($event)">', 'lebar' => '34px'],
        ['judul' => 'Kode'],
        ['judul' => 'Nama Bahan'],
        ['judul' => 'Satuan'],
        ['judul' => 'Harga RAB', 'angka' => true],
        ['judul' => 'Tarif Analisa per Gedung'],
        ['judul' => 'Qty RAB', 'angka' => true],
        ['judul' => 'Anggaran', 'angka' => true],
        ['judul' => 'Terpakai (SPK)', 'angka' => true],
        ['judul' => 'Sisa', 'angka' => true],
        ['judul' => ''],
    ],
    'baris' => $barisBahan,
]);
?>
