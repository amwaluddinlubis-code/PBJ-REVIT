<?php $namaBulan = static fn ($ym) => bulanTahun($ym); ?>
<details class="panel panel-lipat" data-lipat-id="spj-panel" open>
  <summary>SPJ Bulanan — <?= esc($sekolahAktif['nama_sekolah'] ?? '') ?></summary>

  <p style="margin-top:0;color:var(--ta-teks-2)">Surat Pertanggungjawaban (SPJ) per bulan tertutup: daftar bukti dari snapshot beku Buku Kas Umum beserta rujukan dokumen asalnya. Bulan yang ditutup sebelum snapshot diterapkan ditandai belum lengkap — buka kembali &amp; tutup ulang bulan itu (admin) agar snapshotnya terbentuk.</p>
  <form method="get" action="<?= base_url('spj-bulanan') ?>" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
    <select name="bulan" data-select-cari>
      <?php foreach ($daftar_tutup as $b => $t): $ym = sprintf('%04d-%02d', (int) $t['tahun'], (int) $b); ?>
        <option value="<?= esc($ym) ?>"<?= $ym === $bulan ? ' selected' : '' ?>><?= esc($namaBulan($ym)) ?></option>
      <?php endforeach; ?>
      <?php if ($daftar_tutup === []): ?><option value="<?= esc($bulan) ?>"><?= esc($namaBulan($bulan)) ?></option><?php endif; ?>
    </select>
    <button type="submit" class="kecil">Tampilkan</button>
    <a class="tombol abu" href="<?= base_url('buku-kas/cetak?buku=bku&bulan=' . $bulan) ?>" target="_blank">Cetak BKU Bulan Ini</a>
  </form>
</details>

<?php if ($tutup === null): ?>
<div class="panel"><p style="margin:0">Bulan <?= esc($namaBulan($bulan)) ?> belum ditutup — SPJ tersedia sesudah buku bulan itu ditutup di menu <a href="<?= base_url('buku-kas') ?>">Buku Kas</a>.</p></div>
<?php elseif (! $snapshot_ada): ?>
<div class="panel"><p style="margin:0"><strong>SPJ <?= esc($namaBulan($bulan)) ?> belum lengkap:</strong> bulan ini ditutup sebelum snapshot diterapkan sehingga daftar buktinya belum beku. Buka kembali buku bulan ini (admin) lalu tutup ulang agar snapshot &amp; nomor buktinya terbentuk.</p></div>
<?php else: ?>
<details class="panel panel-lipat" data-lipat-id="spj-bulan" open>
  <summary>Daftar Bukti SPJ — <?= esc($namaBulan($bulan)) ?></summary>
  <?php
  $tautanSumber = [
      'invoice' => ['label' => 'Jurnal Pembayaran', 'url' => 'invoice/jurnal'],
      'upah' => ['label' => 'Pembayaran Upah', 'url' => 'upah'],
      'setor' => ['label' => 'Bukti Setor', 'url' => 'bukti-setor'],
      'manual' => ['label' => 'Buku Kas', 'url' => 'buku-kas'],
  ];
  $barisSpj = [];
  foreach ($baris as $e) {
      $ts = $tautanSumber[$e['sumber']] ?? $tautanSumber['manual'];
      $barisSpj[] = ['sel' => [
          (string) $e['nomor'],
          (string) tgl($e['tanggal'] ?? null),
          (string) $e['uraian'],
          (string) ($e['kanal'] === 'tunai' ? 'Tunai' : 'Bank'),
          ['teks' => $e['masuk'] > 0 ? rp($e['masuk']) : '—', 'angka' => true],
          ['teks' => $e['keluar'] > 0 ? rp($e['keluar']) : '—', 'angka' => true],
          ['html' => '<a class="tombol kecil abu" href="' . base_url($ts['url']) . '">' . $ts['label'] . '</a>'],
      ]];
  }
  if ($baris === []) {
      $barisSpj[] = ['sel' => [['teks' => 'Tidak ada transaksi pada bulan ini.', 'colspan' => 7]]];
  }
  echo tabel_bantu([
      'cari' => 'Cari bukti…', 'paginasi' => 25,
      'kolom' => [
          ['judul' => 'No. Bukti'],
          ['judul' => 'Tanggal'],
          ['judul' => 'Uraian'],
          ['judul' => 'Kanal'],
          ['judul' => 'Penerimaan', 'angka' => true],
          ['judul' => 'Pengeluaran', 'angka' => true],
          ['judul' => 'Dokumen Asal'],
      ],
      'baris' => $barisSpj,
  ]);
  ?>
  <p class="kecil-teks">Saldo awal <?= rp($tutup['saldo_awal']) ?> · total masuk <?= rp($tutup['total_masuk']) ?> · total keluar <?= rp($tutup['total_keluar']) ?> · saldo akhir <?= rp($tutup['saldo_akhir']) ?> — ditutup oleh <?= esc($tutup['ditutup_oleh'] ?? '—') ?> pada <?= esc((string) tglWaktu($tutup['ditutup_pada'] ?? null)) ?>.</p>
</details>
<?php endif; ?>
