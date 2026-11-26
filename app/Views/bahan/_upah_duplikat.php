<?php /* Partial bahan/_panel_upah: blok Duplikat Jenis Tenaga + form gabung per grup.
   Tabel anggota dirender helper tabel_bantu() (radio penentu baris utama + hidden anggota[]). */ ?>
<?php if (! empty($duplikatUpah)): ?>
  <h3 style="margin-bottom:6px">Duplikat Jenis Tenaga</h3>
  <?php foreach ($duplikatUpah as $grup): ?>
  <form method="post" action="<?= base_url('bahan/upah-gabung') ?>" data-konfirmasi="Gabungkan duplikat jenis tenaga ini mengikuti tarif baris utama?">
    <?= csrf_field() ?>
    <?php
    $barisGrupUpah = [];
    foreach ($grup['anggota'] as $i => $u) {
        $barisGrupUpah[] = ['sel' => [
            ['html' => '<input type="radio" name="utama" value="' . (int) $u['id'] . '"' . ($i === 0 ? ' checked' : '') . '>'],
            ['html' => esc($u['jenis']) . '<input type="hidden" name="anggota[]" value="' . (int) $u['id'] . '">'],
            (string) ($u['satuan'] ?? 'OH'),
            ['teks' => rp($u['tarif']), 'angka' => true],
        ]];
    }
    echo tabel_bantu([
        'kolom' => [
            ['judul' => 'Utama', 'lebar' => '36px'],
            ['judul' => 'Jenis'],
            ['judul' => 'Satuan'],
            ['judul' => 'Tarif', 'angka' => true],
        ],
        'baris' => $barisGrupUpah,
    ]);
    ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:8px 0">
      <label>Tarif final (Rp) <input type="text" data-uang name="tarif_final" value="<?= (int) $grup['anggota'][0]['tarif'] ?>"></label>
      <span class="kecil-teks">Tarif final ini yang menjadi acuan Master Upah setelah digabung.</span>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit" class="kecil">Perbaiki &amp; Gabungkan "<?= esc($grup['nama']) ?>"</button></div>
  </form>
  <?php endforeach; ?>
  <?php endif; ?>
