<?php /* Partial gedung/detail tab BOQ: panel Ubah Nama / Keterangan Gedung — form kini modal helper form_modal (simpan AJAX ke Gedung::perbarui). */ ?><div class="panel">
  <h2>Ubah Nama / Keterangan Gedung</h2>
  <?php $dataGedung = htmlspecialchars(json_encode([
      'id' => (int) $g['id'], 'nama' => $g['nama'], 'keterangan' => $g['keterangan'] ?? '',
      'nilai_boq' => (string) (int) round((float) $g['nilai_boq']),
  ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
  <button type="button" class="tombol kecil" onclick="bukaFormModal('modal-ubah-gedung', <?= $dataGedung ?>)"><span class="ikon-tombol" aria-hidden="true"><?= ikonSvg('pensil') ?></span> Ubah Gedung</button>
</div>
<?= form_modal([
    'id' => 'modal-ubah-gedung',
    'judul' => 'Ubah Gedung',
    'judul_ubah' => 'Ubah Gedung',
    'aksi' => base_url('gedung/' . $g['id'] . '/perbarui'),
    'kolom' => [
        ['nama' => 'nama', 'label' => 'Nama gedung', 'wajib' => true],
        ['nama' => 'keterangan', 'label' => 'Keterangan'],
        ['nama' => 'nilai_boq', 'label' => 'Nilai BOQ dokumen (Rp)', 'jenis' => 'uang'],
    ],
    'catatan_html' => '<p class="kecil-teks" style="margin:12px 0 2px">Nilai BOQ dokumen adalah angka resmi dari dokumen perencana (terisi saat impor). Nilai ini <strong>beku</strong>: tidak berubah oleh edit volume/pekerjaan — ubah hanya di sini bila angka dokumennya memang direvisi.</p>',
    'tombol' => ['hapus' => false, 'simpan_teks' => 'Simpan', 'simpan_teks_ubah' => 'Simpan'],
]) ?>
