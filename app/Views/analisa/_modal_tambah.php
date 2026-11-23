<?php /* Partial analisa/detail: modal Tambah Komponen. */ ?><div class="mk-lapik" id="modal-tambah-komp" hidden>
  <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="judul-tambah-komp" style="max-width:520px;text-align:left">
    <h3 id="judul-tambah-komp" style="text-align:left">Tambah Komponen</h3>
    <form id="form-tambah-komp" method="post" action="<?= base_url('analisa/' . $a['id'] . '/komponen-tambah') ?>">
      <?= csrf_field() ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;text-align:left">
        <label>Jenis
          <select name="jenis" data-select-cari>
            <option value="bahan">Bahan</option>
            <option value="upah">Upah</option>
            <option value="alat">Alat</option>
          </select>
        </label>
        <label>Bahan dari master (jenis Bahan)
          <?= select_cari('bahan_id', array_merge([['value' => '', 'label' => '— ketik nama manual —']], array_map(static fn ($b) => ['value' => (int) $b['id'], 'label' => $b['kode'] . ' — ' . $b['nama']], $bahan))) ?>
        </label>
        <label>Nama komponen <input type="text" name="nama" placeholder="nama komponen"></label>
        <label>Koefisien <input type="text" name="koefisien" id="ft-koef" placeholder="0,0000"></label>
        <label>Satuan <input type="text" name="satuan" placeholder="m3 / OH / zak"></label>
        <label>Tarif (Rp) <input type="text" inputmode="numeric" data-uang name="tarif" id="ft-tarif" placeholder="otomatis bila dari master"></label>
      </div>
      <p class="kecil-teks" style="text-align:left">Nilai per satuan dari isian ini: <strong id="nilai-tambah">Rp 0</strong>. Untuk jenis Upah, isi Nama persis seperti jenis di Master Upah agar tarif master berlaku; untuk Bahan dari master, tarif kosong memakai harga acuan bahan.</p>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button type="button" class="tombol abu" data-tutup-modal>Batal</button>
        <button type="submit" class="tombol">+ Tambah Komponen</button>
      </div>
    </form>
  </div>
</div>
