<?php /* Partial bahan/index: ALERT duplikat bahan + form Perbaiki & Gabungkan per grup.
   Tabel anggota dirender helper tabel_bantu() (radio penentu baris utama + hidden anggota[]). */ ?>
<?php if (! empty($duplikatBahan)): ?>
<details class="panel panel-lipat" data-lipat-id="alert-duplikat" open style="border-left:4px solid var(--ta-kuning,#b58900)">
  <summary>ALERT: Bahan Sama dengan Satuan / Harga Berbeda</summary>
  <p style="margin-top:0;color:var(--ta-teks-2)">Bahan berikut tercatat lebih dari satu kali dengan nama yang sama tetapi satuan atau harga yang berbeda. <strong>Perbaiki dulu</strong> — tentukan satuan &amp; harga final dan baris master utamanya — <strong>barulah gabungkan</strong> ke Master Bahan: semua referensi di SPK, analisa, survei harga, negosiasi, dan buku material dialihkan ke master itu, kuantitas RAB dijumlahkan, dan baris duplikat dihapus. Sesudah digabung, bahan ini hanya punya satu satuan dan satu harga acuan untuk semua modul.</p>
  <?php foreach ($duplikatBahan as $grup): ?>
  <form method="post" action="<?= base_url('bahan/gabung') ?>" data-konfirmasi="Gabungkan duplikat bahan ini? Referensi dari baris lain akan dialihkan ke baris utama yang dipilih.">
    <?= csrf_field() ?>
    <?php
    $barisGrup = [];
    foreach ($grup['anggota'] as $i => $b) {
        $barisGrup[] = ['sel' => [
            ['html' => '<input type="radio" name="utama" value="' . (int) $b['id'] . '"' . ($i === 0 ? ' checked' : '') . '>'],
            ['html' => esc($b['kode']) . '<input type="hidden" name="anggota[]" value="' . (int) $b['id'] . '">'],
            (string) $b['nama'],
            (string) ($b['satuan'] ?? ''),
            ['teks' => rp($b['harga_rab']), 'angka' => true],
            ['teks' => number_format((float) $b['qty_rkb'] + (float) $b['qty_adm'] + (float) $b['qty_toilet'], 2, ',', '.'), 'angka' => true],
        ]];
    }
    echo tabel_bantu([
        'kolom' => [
            ['judul' => 'Utama', 'lebar' => '36px'],
            ['judul' => 'Kode'],
            ['judul' => 'Nama'],
            ['judul' => 'Satuan'],
            ['judul' => 'Harga RAB', 'angka' => true],
            ['judul' => 'Qty RAB Total', 'angka' => true],
        ],
        'baris' => $barisGrup,
    ]);
    ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:8px 0">
      <label>Satuan final (perbaiki bila perlu) <input type="text" name="satuan_final" value="<?= esc($grup['anggota'][0]['satuan'] ?? '') ?>" size="8"></label>
      <label>Harga final (Rp) <input type="text" data-uang name="harga_final" value="<?= (int) $grup['anggota'][0]['harga_rab'] ?>"></label>
      <span class="kecil-teks">Satuan &amp; harga final ini yang menjadi acuan master setelah digabung.</span>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit">Perbaiki &amp; Gabungkan "<?= esc($grup['nama']) ?>"</button></div>
  </form>
  <?php endforeach; ?>
</details>
<?php endif; ?>
