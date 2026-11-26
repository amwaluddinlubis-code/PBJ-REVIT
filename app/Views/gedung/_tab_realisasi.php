<?php /* Partial gedung/detail tab Realisasi: panel Kemajuan per Pekerjaan + Riwayat Pencatatan Terakhir. Tabel dirender helper tabel_bantu(); form Catat per baris tetap sebaris (isian cepat volume + tanggal). */ ?><div class="panel">
  <h2>Kemajuan per Pekerjaan</h2>
  <div class="bungkus-tabel"><?php
  $fmtVol = static fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ',');
  $barisKemajuan = [];
  foreach ($pekerjaan as $p) {
      $persen = (float) $p['volume'] > 0 ? min(100, (float) $p['volume_terealisasi'] / (float) $p['volume'] * 100) : 0;
      $barisKemajuan[] = ['sel' => [
          ['html' => esc($p['nama']) . (! empty($p['bab']) ? ' <span class="kecil-teks">(' . esc($p['bab']) . ')</span>' : ''), 'class' => 'potong', 'title' => (string) $p['nama']],
          ['teks' => $fmtVol($p['volume']) . ' ' . ($p['satuan'] ?? ''), 'angka' => true],
          ['teks' => $fmtVol($p['volume_terealisasi']), 'angka' => true],
          ['html' => '<div class="sel-fisik"><div class="bar' . ($persen >= 99.5 ? ' hijau' : '') . '"><div style="width:' . $persen . '%"></div></div><span class="kecil-teks">' . number_format($persen, 1, ',', '.') . '%</span></div>'],
          ['html' => '<form method="post" action="' . base_url('gedung/pekerjaan/' . $p['id'] . '/realisasi') . '" style="display:flex;gap:5px">'
              . csrf_field()
              . '<input type="text" name="volume" placeholder="Volume" required style="margin:0;width:84px">'
              . '<input type="date" name="tanggal" value="' . date('Y-m-d') . '" style="margin:0;width:132px">'
              . '<button class="kecil" type="submit">Catat</button></form>'],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari…',
      'paginasi' => 25,
      'class' => 'satu-baris',
      'kolom' => [
          ['judul' => 'Pekerjaan'],
          ['judul' => 'Volume BOQ', 'angka' => true],
          ['judul' => 'Terealisasi', 'angka' => true],
          ['judul' => '% Fisik'],
          ['judul' => 'Catat Kemajuan', 'lebar' => '300px'],
      ],
      'baris' => $barisKemajuan,
  ]);
  ?></div>
</div>
<div class="panel">
  <h2>Riwayat Pencatatan Terakhir</h2>
  <div class="bungkus-tabel"><?php
  $barisRiwayat = [];
  foreach ($riwayat as $r) {
      $barisRiwayat[] = ['sel' => [
          (string) tgl($r['tanggal']),
          (string) $r['nama_pekerjaan'],
          ['teks' => $fmtVol($r['volume']), 'angka' => true],
          (string) ($r['keterangan'] ?? ''),
          ['html' => '<form class="inline" method="post" action="' . base_url('gedung/realisasi/' . $r['id'] . '/hapus') . '" data-konfirmasi="Hapus catatan ini?">' . csrf_field() . '<button class="kecil bahaya" type="submit">Hapus</button></form>'],
      ]];
  }
  if ($riwayat === []) {
      $barisRiwayat[] = ['sel' => [['teks' => 'Belum ada pencatatan kemajuan.', 'colspan' => 5]]];
  }
  echo tabel_bantu([
      'cari' => 'Cari riwayat…',
      'class' => 'satu-baris',
      'kolom' => [
          ['judul' => 'Tanggal'],
          ['judul' => 'Pekerjaan'],
          ['judul' => 'Volume', 'angka' => true],
          ['judul' => 'Keterangan'],
          ['judul' => 'Aksi'],
      ],
      'baris' => $barisRiwayat,
  ]);
  ?></div>
</div>
