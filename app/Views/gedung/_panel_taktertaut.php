<?php /* Partial gedung/detail tab Bahan: panel Komponen Belum Tertaut ke Master Bahan + form tautkan. Tabel dirender helper tabel_bantu(); form tautkan per baris tetap sebaris. */ ?><?php if (! empty($kebutuhan['tak_tertaut'])): ?>
<div class="panel">
  <h2>Komponen Belum Tertaut ke Master Bahan (<?= count($kebutuhan['tak_tertaut']) ?>)</h2>
  <p style="margin-top:0" class="kecil-teks">Komponen AHSP berikut belum punya padanan di master bahan sehingga belum terhitung. Tautkan ke bahan yang sesuai — penautan berlaku untuk semua gedung.</p>
  <div class="bungkus-tabel"><?php
  $opsiBahan = '<option value="">— pilih bahan —</option>';
  foreach ($semuaBahan as $sb) {
      $opsiBahan .= '<option value="' . (int) $sb['id'] . '">' . esc($sb['kode']) . ' — ' . esc($sb['nama']) . '</option>';
  }
  $barisTaut = [];
  foreach ($kebutuhan['tak_tertaut'] as $t) {
      $barisTaut[] = ['sel' => [
          ['teks' => (string) $t['nama'], 'class' => 'potong', 'title' => (string) $t['nama']],
          ['teks' => number_format((float) $t['qty_butuh'], 2, ',', '.'), 'angka' => true],
          ['html' => '<form method="post" action="' . base_url('gedung/tautkan') . '" style="display:flex;gap:6px">'
              . csrf_field()
              . '<input type="hidden" name="nama_komponen" value="' . esc($t['nama'], 'attr') . '">'
              . '<input type="hidden" name="gedung_id" value="' . (int) $g['id'] . '">'
              . '<select name="bahan_id" required style="margin:0" data-select-cari data-placeholder="— pilih bahan —">' . $opsiBahan . '</select>'
              . '<button class="kecil" type="submit">Tautkan</button></form>'],
      ]];
  }
  echo tabel_bantu([
      'cari' => 'Cari komponen…',
      'paginasi' => 25,
      'class' => 'satu-baris',
      'kolom' => [
          ['judul' => 'Komponen AHSP'],
          ['judul' => 'Kebutuhan', 'angka' => true],
          ['judul' => 'Tautkan ke Bahan Master', 'lebar' => '46%'],
      ],
      'baris' => $barisTaut,
  ]);
  ?></div>
</div>
<?php endif; ?>
