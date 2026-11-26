<?php /* Partial biayalain/index: daftar kelompok biaya (details per kelompok + tabel baris + aksi).
   Tabel dirender helper tabel_bantu() (app/Common.php) dari konfigurasi kolom + baris;
   tombol Ubah membuka modal helper form_modal() — Hapus kini dari dalam modal.
   Baris yang sudah tertaut ke SPK aktif ($tautan_spk dari controller) menampilkan
   lencana tautan "SPK {nomor}" di kolom Aksi dan kotak centangnya dinonaktifkan. */ ?>
<?php $angka = static fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); ?>
<?php foreach ($kelompok as $namaKelompok => $barisList): ?>
  <details class="panel biaya-data"<?= count($barisList) <= 10 ? ' open' : '' ?>>
    <summary class="tabel-kepala">
      <h2 style="margin:0;font-size:13px;font-weight:600"><?= esc($namaKelompok) ?></h2>
      <span class="petunjuk-field"><?= count($barisList) ?> baris</span>
      <svg class="lipat-panah" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="tabel-envelop">
    <?php
    $barisTabel = [];
    $sub = 0;
    $subPot = 0;
    foreach ($barisList as $r) {
        $sub += (float) $r['jumlah'];
        $subPot += (float) $r['potongan'];
        $tautan = $tautan_spk[(int) $r['id']] ?? null;
        $dataUbah = htmlspecialchars(json_encode([
            'id' => (int) $r['id'], 'kelompok' => $r['kelompok'], 'uraian' => $r['uraian'],
            'volume' => $angka($r['volume']), 'satuan' => $r['satuan'] ?? '',
            'periode' => $r['periode'] !== null ? $angka($r['periode']) : '', 'satuan_periode' => $r['satuan_periode'] ?? '',
            'harga_satuan' => (string) (int) round((float) $r['harga_satuan']), 'keterangan' => $r['keterangan'] ?? '',
            'pajak' => $r['pajak'] ?? '', 'sumber' => $r['sumber'] ?? 'manual',
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        $barisTabel[] = ['sel' => [
            ['html' => '<input type="checkbox" data-pilih value="' . (int) $r['id'] . '" x-model="terpilih"' . ($tautan !== null ? ' disabled title="Sudah tertaut ke SPK ' . (int) $tautan['nomor'] . '"' : '') . '>'],
            ['html' => '<div class="baris-uraian"><b>' . esc($r['uraian']) . '</b>' . (! empty($r['keterangan']) ? '<br><span class="kecil-teks">' . esc($r['keterangan']) . '</span>' : '') . '</div>'],
            ['html' => $angka($r['volume']) . ' <span class="kecil-teks" style="color:var(--ta-teks-3)">' . esc($r['satuan'] ?? '') . '</span>', 'angka' => true],
            ['html' => $r['periode'] !== null ? $angka($r['periode']) . ' <span class="kecil-teks" style="color:var(--ta-teks-3)">' . esc($r['satuan_periode'] ?? '') . '</span>' : '—', 'angka' => true],
            ['teks' => rp($r['harga_satuan']), 'angka' => true],
            ['html' => '<b>' . rp($r['jumlah']) . '</b>', 'angka' => true],
            ['html' => (float) $r['potongan'] > 0 ? rp($r['potongan']) . '<br><span class="kecil-teks">' . esc($r['label_pajak']) . '</span>' : '—', 'angka' => true],
            ['teks' => rp($r['bersih']), 'angka' => true],
            ['html' => '<span class="lencana ' . ($r['sumber'] === 'impor' ? 'biru' : 'abu') . '">' . ($r['sumber'] === 'impor' ? 'Impor' : 'Manual') . '</span>'],
            ['html' => '<div class="aksi-baris"><button type="button" class="tombol kecil ubah-baris" onclick="bukaFormModal(\'modal-biaya\', ' . $dataUbah . ')">Ubah</button>' . ($tautan !== null ? '<a class="lencana biru" style="text-decoration:none" href="' . base_url('spk/detail/' . (int) $tautan['spk_id']) . '" title="Buka rincian SPK ' . (int) $tautan['nomor'] . '">SPK ' . (int) $tautan['nomor'] . '</a>' : '') . '</div>'],
        ]];
    }
    $barisTabel[] = ['total' => [
        ['html' => '<b>Subtotal ' . esc($namaKelompok) . '</b>', 'colspan' => 5],
        ['html' => '<b>' . rp($sub) . '</b>', 'angka' => true],
        ['html' => '<b>' . rp($subPot) . '</b>', 'angka' => true],
        ['html' => '<b>' . rp($sub - $subPot) . '</b>', 'angka' => true],
        ['teks' => '', 'colspan' => 2],
    ], 'class' => 'subtotal-baris'];
    echo tabel_bantu([
        'kolom' => [
            ['judul_html' => '<input type="checkbox" title="Pilih semua baris biaya" @change="pilihSemua($event)">', 'lebar' => '34px'],
            ['judul' => 'Uraian'],
            ['judul' => 'Volume', 'angka' => true],
            ['judul' => 'Periode', 'angka' => true],
            ['judul' => 'Harga Satuan', 'angka' => true],
            ['judul' => 'Jumlah', 'angka' => true],
            ['judul' => 'Potongan Pajak', 'angka' => true],
            ['judul' => 'Bersih Diterima', 'angka' => true],
            ['judul' => 'Sumber', 'lebar' => '110px'],
            ['judul' => 'Aksi', 'lebar' => '140px'],
        ],
        'baris' => $barisTabel,
        'cari' => 'Cari baris biaya…',
        'paginasi' => 25,
        'class' => 'tabel-data',
    ]);
    ?>
    </div>
  </details>
<?php endforeach; ?>

</div>
