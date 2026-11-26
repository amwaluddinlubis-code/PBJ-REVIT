<?php /* Jejak Audit (baca-saja): tabel daftar memakai helper tabel_bantu(). */ ?>
<h1>Jejak Audit</h1>
<p class="kecil-teks">Catatan aksi penting yang menyentuh uang dan persetujuan: siapa melakukan apa dan kapan. 500 catatan terakhir untuk sekolah aktif (termasuk catatan masuk akun). Catatan tidak dapat diubah dari aplikasi.</p>

<div class="panel">
  <?php if ($daftar === []): ?>
    <p class="kecil-teks">Belum ada catatan audit.</p>
  <?php else: ?>
  <div class="bungkus-tabel">
    <?php
    $barisAudit = [];
    foreach ($daftar as $a) {
        $barisAudit[] = ['sel' => [
            tglWaktu($a['created_at']),
            (string) ($a['username'] ?? '-'),
            $a['entitas'] . ($a['entitas_id'] ? ' #' . (int) $a['entitas_id'] : ''),
            (string) $a['aksi'],
            ['teks' => (string) ($a['keterangan'] ?? '-'), 'class' => 'potong', 'title' => (string) ($a['keterangan'] ?? '')],
            (string) ($a['ip'] ?? '-'),
        ]];
    }
    echo tabel_bantu([
        'class' => 'satu-baris',
        'cari' => '',
        'paginasi' => 25,
        'kolom' => [
            ['judul' => 'Waktu'],
            ['judul' => 'Pengguna'],
            ['judul' => 'Entitas'],
            ['judul' => 'Aksi'],
            ['judul' => 'Keterangan'],
            ['judul' => 'IP'],
        ],
        'baris' => $barisAudit,
    ]);
    ?>
  </div>
  <?php endif; ?>
</div>
