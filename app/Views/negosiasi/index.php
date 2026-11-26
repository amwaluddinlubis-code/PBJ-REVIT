<?php /* Negosiasi: tabel isian massal (grid) memakai helper tabel_bantu();
   form simpan tetap satu form halaman (bukan modal — seluruh tabel
   adalah permukaan isian). */ ?>
<?php $rp = static fn ($n) => $n === null ? '—' : 'Rp ' . number_format((float) $n, 0, ',', '.'); ?>
<div data-bulk-akar x-data="komponenBulk('form-bulk-nego','ids-nego')">
<div class="panel">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <form id="form-bulk-nego" method="post" action="<?= base_url('negosiasi/hapus-massal') ?>" data-konfirmasi-judul="Hapus Hasil Negosiasi Terpilih" data-konfirmasi-tombol="Ya, Hapus" style="display:inline">
      <?= csrf_field() ?>
      <span id="ids-nego"></span>
      <button type="button" class="bahaya" :disabled="terpilih.length === 0" @click="kirim('Hapus hasil negosiasi {n} bahan terpilih?')">Hapus Terpilih (<span x-text="terpilih.length">0</span>)</button>
    </form>
    <form method="post" action="<?= base_url('negosiasi/kosongkan') ?>" data-konfirmasi="Kosongkan seluruh hasil negosiasi sekolah ini? Semua baris penawaran & negosiasi akan dihapus (hapus lunak)." data-konfirmasi-judul="Kosongkan Hasil Negosiasi" data-konfirmasi-tombol="Ya, Kosongkan" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="bahaya">Kosongkan Hasil Negosiasi</button>
    </form>
    <span class="kecil-teks">Centang bahan pada tabel di bawah untuk menghapus hasil negosiasinya.</span>
  </div>
</div>
<div class="panel">
  <p>Negosiasi dilakukan dengan penyedia pemilik <strong>Harga Terpilih</strong> (survei termurah). Harga Penawaran terisi awal dari harga terpilih; isi Harga Negosiasi bila ada kesepakatan lebih rendah. Total dan Hemat dihitung dari qty RAB.</p>
  <p class="kecil-teks">Penyedia default: <?= esc($identitas['penyedia_default_nama'] ?: '— (belum diisi di Pengaturan)') ?></p>
  <form method="post" action="<?= base_url('negosiasi/simpan') ?>">
    <?= csrf_field() ?>
    <div style="overflow-x:auto">
    <?php
    $tPen = 0; $tNeg = 0;
    $barisNego = [];
    foreach ($bahan as $b) {
        $qty = (float) $b['qty_total'];
        $pen = $b['penawaran']; $neg = $b['nego'] ?? $pen;
        $totPen = $pen !== null ? $qty * (float) $pen : null;
        $totNeg = $neg !== null ? $qty * (float) $neg : null;
        if ($totPen !== null) $tPen += $totPen;
        if ($totNeg !== null) $tNeg += $totNeg;
        $barisNego[] = ['sel' => [
            ['html' => '<input type="checkbox" data-pilih value="' . (int) $b['id'] . '" x-model="terpilih">'],
            (string) $b['kode'],
            (string) $b['nama'],
            ['teks' => number_format($qty, 2, ',', '.'), 'angka' => true],
            ['teks' => $rp($b['terpilih']), 'angka' => true],
            ['html' => '<input type="number" name="penawaran[' . (int) $b['id'] . ']" min="0" step="1" value="' . esc($pen ?? '', 'attr') . '" style="width:120px">', 'angka' => true],
            ['html' => '<input type="number" name="nego[' . (int) $b['id'] . ']" min="0" step="1" value="' . esc($b['nego'] ?? '', 'attr') . '" style="width:120px">', 'angka' => true],
            ['teks' => $rp($totPen), 'angka' => true],
            ['teks' => $rp($totNeg), 'angka' => true],
            ['teks' => ($totPen !== null && $totNeg !== null) ? $rp($totPen - $totNeg) : '—', 'angka' => true],
        ]];
    }
    $barisNego[] = ['total' => [
        ['teks' => 'TOTAL', 'colspan' => 7, 'gaya' => 'text-align:right'],
        ['teks' => $rp($tPen), 'angka' => true],
        ['teks' => $rp($tNeg), 'angka' => true],
        ['teks' => $rp($tPen - $tNeg), 'angka' => true],
    ]];
    echo tabel_bantu([
        'cari' => 'Cari bahan…',
        'paginasi' => 25,
        'kolom' => [
            ['judul_html' => '<input type="checkbox" title="Pilih semua bahan" @change="pilihSemua($event)">', 'lebar' => '34px'],
            ['judul' => 'Kode'],
            ['judul' => 'Nama Bahan'],
            ['judul' => 'Qty RAB', 'angka' => true],
            ['judul' => 'Harga Terpilih', 'angka' => true],
            ['judul' => 'Harga Penawaran', 'angka' => true],
            ['judul' => 'Harga Negosiasi', 'angka' => true],
            ['judul' => 'Total Penawaran', 'angka' => true],
            ['judul' => 'Total Negosiasi', 'angka' => true],
            ['judul' => 'Hemat', 'angka' => true],
        ],
        'baris' => $barisNego,
    ]);
    ?>
    </div>
    <div class="aksi md:col-span-2"><button type="submit">Simpan Negosiasi</button></div>
  </form>
</div>
</div>
