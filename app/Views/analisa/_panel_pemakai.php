<?php /* Partial analisa/detail: panel Dipakai di Pekerjaan (daftar pekerjaan pemakai analisa ini). Tabel dirender helper tabel_bantu(). */ ?><div class="panel">
  <h2 style="margin-top:0">Dipakai di Pekerjaan (<?= count($pemakai ?? []) ?>)</h2>
  <?php if (empty($pemakai)): ?>
  <p class="kecil-teks" style="margin-bottom:0">Analisa ini belum dipakai pekerjaan mana pun di gedung aktif sekolah ini.</p>
  <?php else: ?>
  <?php
  $barisPakai = [];
  foreach ($pemakai as $pm) {
      $vb = (float) ($pm['volume_revisi'] ?? $pm['volume']);
      $barisPakai[] = ['sel' => [
          ['html' => '<a href="' . base_url('gedung/' . $pm['gedung_id']) . '">' . esc($pm['gedung']) . '</a>'],
          ['teks' => $pm['nama'], 'class' => 'potong', 'title' => $pm['nama']],
          ['teks' => rtrim(rtrim(number_format($vb, 3, ',', '.'), '0'), ',') . ' ' . ($pm['satuan'] ?? ''), 'angka' => true],
          ['html' => '<a class="tombol kecil" href="' . base_url('gedung/' . $pm['gedung_id'] . '/boq#pekerjaan-' . $pm['pekerjaan_id']) . '">Buka Pekerjaan</a>'],
      ]];
  }
  echo '<div class="bungkus-tabel">' . tabel_bantu([
      'kolom' => [['judul' => 'Gedung'], ['judul' => 'Pekerjaan'], ['judul' => 'Volume Berjalan', 'angka' => true], ['judul' => 'Aksi', 'atribut' => 'data-tanpa-urut']],
      'baris' => $barisPakai, 'cari' => 'Cari gedung / pekerjaan…', 'paginasi' => 10,
  ]) . '</div>';
  ?>
  <p class="kecil-teks" style="margin-bottom:0">Tombol Buka Pekerjaan menuju baris pekerjaan itu di BOQ gedungnya (halaman digulir &amp; baris disorot).</p>
  <?php endif; ?>
</div>
