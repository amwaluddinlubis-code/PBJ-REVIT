<?php /* Partial bahan/index: panel Gabungkan Bahan Manual (nama berbeda) + skrip penyalin pilihan combobox ke isian anggota. */ ?>
<details class="panel panel-lipat" data-lipat-id="gabung-manual">
  <summary>Gabungkan Bahan Manual (Nama Berbeda)</summary>
  <p style="margin-top:0;color:var(--ta-teks-2)">Untuk dua bahan yang sebenarnya sama tetapi <strong>namanya berbeda</strong> (mis. "Kayu Dolken" dan "Dolken") sehingga tidak tertangkap ALERT duplikat. Pilih bahan utama dan bahan yang digabungkan ke dalamnya: semua referensi (SPK, analisa, survei, negosiasi, buku material, konversi) dialihkan ke utama, kuantitas RAB dijumlahkan, dan bahan duplikat dihapus lunak.</p>
  <form method="post" action="<?= base_url('bahan/gabung') ?>" data-konfirmasi="Gabungkan bahan duplikat ke bahan utama yang dipilih? Referensi bahan duplikat akan dialihkan ke utama." id="form-gabung-manual">
    <?= csrf_field() ?>
    <input type="hidden" name="anggota[]" id="gabung-anggota-utama">
    <input type="hidden" name="anggota[]" id="gabung-anggota-duplikat">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:8px 0">
      <label style="flex:1;min-width:220px">Bahan Utama (dipertahankan)
        <?= select_cari('utama', array_merge([['value' => '', 'label' => '— pilih bahan utama —']], array_map(static fn ($b) => ['value' => (int) $b['id'], 'label' => $b['kode'] . ' — ' . $b['nama'] . ' (' . ($b['satuan'] ?? '-') . ', ' . rp($b['harga_rab']) . ')'], $semuaBahan ?? [])), '', ['id' => 'gabung-utama']) ?>
      </label>
      <label style="flex:1;min-width:220px">Bahan Duplikat (digabungkan, lalu dihapus)
        <?= select_cari('duplikat', array_merge([['value' => '', 'label' => '— pilih bahan duplikat —']], array_map(static fn ($b) => ['value' => (int) $b['id'], 'label' => $b['kode'] . ' — ' . $b['nama'] . ' (' . ($b['satuan'] ?? '-') . ', ' . rp($b['harga_rab']) . ')'], $semuaBahan ?? [])), '', ['id' => 'gabung-duplikat']) ?>
      </label>
      <label>Satuan final <input type="text" name="satuan_final" size="8" placeholder="= utama"></label>
      <label>Harga final (Rp) <input type="text" data-uang name="harga_final" placeholder="= utama"></label>
    </div>
    <div class="aksi" style="justify-content:flex-start"><button type="submit">Gabungkan Manual</button></div>
  </form>
  <script>
  document.getElementById('form-gabung-manual').addEventListener('submit', function () {
    document.getElementById('gabung-anggota-utama').value = document.getElementById('gabung-utama').value;
    document.getElementById('gabung-anggota-duplikat').value = document.getElementById('gabung-duplikat').value;
  });
  </script>
</details>
