<?php /* Partial analisa/detail: modal Ubah Komponen. */ ?><div class="mk-lapik" id="modal-ubah-komp" hidden>
  <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="judul-ubah-komp" style="max-width:440px;text-align:left">
    <h3 id="judul-ubah-komp" style="text-align:left">Ubah Komponen</h3>
    <p class="kecil-teks" style="text-align:left"><span id="fu-jenis"></span> · <strong id="fu-nama"></strong></p>
    <form id="form-ubah-komp" method="post" action="<?= base_url('analisa/' . $a['id'] . '/komponen-ubah') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="komponen_id" id="fu-id">
      <label style="display:block;text-align:left;margin-bottom:8px">Koefisien
        <input type="text" name="koefisien" id="fu-koef" style="width:100%">
      </label>
      <label style="display:block;text-align:left;margin-bottom:8px">Satuan
        <input type="text" name="satuan" id="fu-satuan" style="width:100%">
      </label>
      <label style="display:block;text-align:left;margin-bottom:8px">Tarif (Rp)
        <input type="text" inputmode="numeric" data-uang name="tarif" id="fu-tarif" style="width:100%">
      </label>
      <p class="kecil-teks" style="text-align:left">Nilai per satuan analisa: <strong id="nilai-ubah">Rp 0</strong> <span style="opacity:.75">(terhitung langsung saat mengetik)</span></p>
      <p class="kecil-teks" id="fu-acuan" style="text-align:left" hidden>Koefisien acuan AHSP Nasional untuk komponen ini: <strong id="fu-koef-acuan"></strong> — samakan bila memang ingin mengikuti acuan.</p>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button type="button" class="tombol abu" data-tutup-modal>Batal</button>
        <button type="submit" class="tombol">Simpan Komponen</button>
      </div>
    </form>
  </div>
</div>
