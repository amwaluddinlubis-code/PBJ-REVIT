<?php /* Partial analisa/detail: panel komponen AHSP (tabel bahan/upah/alat + aksi ubah per baris).
   Tabel dirender helper tabel_bantu(); Hapus komponen kini dari modal Ubah (form_modal). */ ?>
<?php $lencana = ['bahan' => '#dbeafe', 'upah' => '#dcfce7', 'alat' => '#e5e7eb']; $total = 0.0; foreach ($komponen as $k) { $total += (float) $k['koefisien'] * (float) ($k['tarif'] ?? 0); } ?><div class="panel">
  <h2 style="margin-top:0"><?= esc($a['kode']) ?> — <?= esc($a['nama'] ?? '') ?><?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?> <span style="background:#fef3c7;color:#92400e;padding:2px 9px;border-radius:99px;font-size:12px;vertical-align:middle">harga satuan belum terverifikasi</span><?php endif; ?></h2>
  <?php if ((float) ($a['harga_satuan'] ?? 0) <= 0): ?>
  <p style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:8px 12px">Harga satuan analisa ini tidak terbaca dari sheet Harsat dokumen sumber (tertulis #REF! di dokumennya). Menyimpan perubahan komponen di bawah akan mengisinya dari jumlah komponen.</p>
  <?php endif; ?>
  <p class="kecil-teks" id="ringkas-analisa" style="margin-top:0">Satuan analisa: <strong><?= esc($a['satuan'] ?? '-') ?></strong> · Harga satuan tersimpan: <strong><?= rp($a['harga_satuan'] ?? 0) ?></strong> · Jumlah komponen: <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong> per satuan. Klik Ubah pada komponen untuk mengubah koefisien/tarif lewat modal — kebutuhan bahan &amp; upah di semua gedung yang memakai analisa ini langsung berubah.</p>
  <p style="margin:0 0 10px"><button type="button" class="tombol" id="btn-tambah-komp">+ Tambah Komponen</button></p>
  <?php if (! empty($acuanInfo)): ?>
  <p class="kecil-teks" style="margin:0 0 10px">Padanan AHSP Nasional: <strong><?= esc($acuanInfo['kode_nasional']) ?> — <?= esc($acuanInfo['nama']) ?></strong>. Kolom <strong>Koef. Acuan</strong> &amp; <strong>Nilai Acuan</strong> (koefisien acuan × tarif komponen ini) tampil sebagai pembanding di samping setiap komponen — dokumen nasional hanya menetapkan koefisien, tarif tetap tarif sekolah. Ubah koefisien lewat tombol Ubah bila ingin menyamakannya. <a href="<?= base_url('acuan') ?>">Buka modul Acuan</a></p>
  <?php endif; ?>

  <div id="wadah-komponen">
    <?php
    $fmtKoef = static fn ($n) => rtrim(rtrim(number_format((float) $n, 6, ',', '.'), '0'), ',');
    $kolomKomp = [
        ['judul' => 'Jenis'], ['judul' => 'Komponen'], ['judul' => 'Koefisien', 'angka' => true], ['judul' => 'Satuan'],
        ['judul' => 'Tarif (Rp)', 'angka' => true], ['judul' => 'Nilai / Satuan', 'angka' => true],
    ];
    if (! empty($acuanInfo)) {
        $kolomKomp[] = ['judul' => 'Koef. Acuan', 'angka' => true];
        $kolomKomp[] = ['judul' => 'Nilai Acuan', 'angka' => true];
    }
    $kolomKomp[] = ['judul' => 'Aksi', 'atribut' => 'data-tanpa-urut'];
    $barisKomp = [];
    foreach ($komponen as $k) {
        $nol = (float) ($k['tarif'] ?? 0) <= 0;
        $tombolUbah = '<button type="button" class="tombol kecil abu" data-ubah-komp data-id="' . (int) $k['id'] . '" data-nama="' . esc($k['nama_master'] ?? $k['nama'], 'attr') . '" data-jenis="' . esc(ucfirst($k['jenis']), 'attr') . '" data-koefisien="' . esc((string) $k['koefisien'], 'attr') . '" data-satuan="' . esc($k['satuan'] ?? '', 'attr') . '" data-tarif="' . (int) ($k['tarif'] ?? 0) . '"';
        if (! empty($acuanInfo) && ($acuanPerKomp[(int) $k['id']]['koef'] ?? null) !== null) {
            $tombolUbah .= ' data-koef-acuan="' . esc($fmtKoef($acuanPerKomp[(int) $k['id']]['koef']), 'attr') . '"';
        }
        $tombolUbah .= '>Ubah</button>';
        $sel = [
            ['html' => '<span style="background:' . ($lencana[$k['jenis']] ?? '#e5e7eb') . ';padding:1px 7px;border-radius:99px;font-size:11px">' . esc(ucfirst($k['jenis'])) . '</span>'],
            ['html' => esc($k['nama_master'] ?? $k['nama']) . (! empty($k['kode_master']) ? ' <span class="kecil-teks">(' . esc($k['kode_master']) . ')</span>' : ''), 'class' => 'potong', 'title' => $k['nama']],
            ['teks' => $fmtKoef($k['koefisien']), 'angka' => true],
            ['teks' => $k['satuan'] ?? ''],
            ['html' => number_format((float) ($k['tarif'] ?? 0), 0, ',', '.') . ($nol ? ' <span style="background:#fef3c7;color:#92400e;padding:1px 7px;border-radius:99px;font-size:11px">tarif 0</span>' : ''), 'angka' => true],
            ['teks' => rp((float) $k['koefisien'] * (float) ($k['tarif'] ?? 0)), 'angka' => true],
        ];
        if (! empty($acuanInfo)) {
            $ak = $acuanPerKomp[(int) $k['id']] ?? ['koef' => null, 'nilai' => null, 'banyak' => false];
            $sel[] = ['teks' => $ak['koef'] !== null ? $fmtKoef($ak['koef']) . ($ak['banyak'] ? ' *' : '') : '—', 'angka' => true];
            $sel[] = ['teks' => $ak['nilai'] !== null ? rp($ak['nilai']) : '—', 'angka' => true];
        }
        $sel[] = ['html' => $tombolUbah, 'gaya' => 'white-space:nowrap'];
        $barisKomp[] = ['sel' => $sel, 'atribut' => $nol ? 'style="background:#fffbeb"' : ''];
    }
    $totalAcuan = 0.0;
    foreach ($acuanPerKomp ?? [] as $ak) {
        $totalAcuan += (float) ($ak['nilai'] ?? 0);
    }
    $selTotal = [
        ['html' => '<strong>Total komponen per satuan</strong>', 'colspan' => 5],
        ['html' => '<strong>' . rp($total) . '</strong>', 'angka' => true],
    ];
    if (! empty($acuanInfo)) {
        $selTotal[] = ['teks' => ''];
        $selTotal[] = ['html' => '<strong>' . rp($totalAcuan) . '</strong>', 'angka' => true];
    }
    $selTotal[] = ['teks' => ''];
    $barisKomp[] = ['total' => $selTotal];
    echo '<div class="bungkus-tabel">' . tabel_bantu(['kolom' => $kolomKomp, 'baris' => $barisKomp, 'cari' => 'Cari komponen…', 'class' => 'satu-baris']) . '</div>';
    ?>
    <?php if (! empty($acuanInfo) && array_filter($acuanPerKomp ?? [], static fn ($ak) => $ak['banyak'] && $ak['koef'] !== null)): ?>
    <p class="kecil-teks" style="margin:6px 0 0">* Koefisien acuan bertanda bintang adalah total untuk seluruh grup komponen sejenis pada analisa ini (grup berisi lebih dari satu komponen).</p>
    <?php endif; ?>
  </div>
</div>
