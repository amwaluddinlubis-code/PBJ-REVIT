<?php /* Partial bahan/index: modal Tambah/Ubah Bahan (form dikirim via fetch oleh komponen Alpine induk). */ ?>  <div id="modal-bahan" class="mk-lapik" hidden @click.self="tutupBahan()" @keydown.escape.window="tutupBahan()">
    <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="mb-judul" style="max-width:640px;width:94vw">
      <h3 id="mb-judul" style="margin-top:0" x-text="formBahanId > 0 ? 'Ubah Bahan' : 'Tambah Bahan'">Tambah Bahan</h3>
      <div class="galat" x-show="galatBahan" x-text="galatBahan" style="margin-bottom:10px"></div>
      <form id="form-bahan" @submit.prevent="simpanBahan()">
        <?= csrf_field() ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
          <label>Kode <input name="kode" required></label>
          <label>Nama Bahan <input name="nama" required></label>
          <label>Satuan <input name="satuan"></label>
          <label>Harga RAB (Rp) <input type="text" inputmode="numeric" data-uang name="harga_rab" required></label>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
          <button type="button" @click="tutupBahan()">Batal</button>
          <button type="submit" class="tombol" :disabled="memuat">Simpan</button>
        </div>
      </form>
    </div>
  </div>
