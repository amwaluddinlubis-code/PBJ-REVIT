<?php /* Partial bahan/index: modal pratinjau Sinkronkan Harga Bahan ke Analisa. Di dalam komponen Alpine induk. */ ?>  <div id="modal-sinkron" class="mk-lapik" hidden>
    <div class="mk-kartu" role="dialog" aria-modal="true" aria-labelledby="ms-judul" style="max-width:760px;width:94vw">
      <h3 id="ms-judul" style="margin-top:0">Sinkronkan Harga Bahan ke Analisa?</h3>
      <p class="kecil-teks" style="margin-top:0">Tarif komponen bahan di analisa akan diganti dengan harga acuan bahannya (negosiasi → penawaran → harga RAB), disesuaikan ke satuan komponen lewat faktor konversi — mis. harga per zak menjadi per kg — agar nilai AHSP tidak berubah oleh perbedaan satuan. Nilai BOQ dashboard berubah mengikuti hasil AHSP baru. <strong>Hanya komponen yang analisanya benar-benar dipakai pekerjaan di gedung didanai yang disinkronkan</strong> — komponen pada analisa yang tidak dipakai pekerjaan gedung mana pun tidak diubah. <strong>Hanya tarif yang berubah yang ditampilkan</strong> — <span x-text="sinkron ? sinkron.tetap : 0"></span> komponen yang sudah sama disembunyikan<span x-show="sinkron && sinkron.tanpa_harga > 0">, dan <span x-text="sinkron ? sinkron.tanpa_harga : 0"></span> komponen dilewati karena bahannya belum punya harga</span><span x-show="sinkron && sinkron.dilewati_satuan > 0">, dan <span x-text="sinkron ? sinkron.dilewati_satuan : 0"></span> komponen dilewati karena satuannya berbeda dari master dan belum ada faktor konversinya (mis. per buah vs per dus) — menyalin harga mentah akan merusak AHSP</span>.</p>
      <div style="max-height:46vh;overflow:auto;border:1px solid var(--ta-garis);border-radius:8px">
        <table class="satu-baris" style="margin:0">
          <tr><th>Analisa</th><th>Komponen / Bahan</th><th>Satuan</th><th class="angka">Tarif Lama</th><th class="angka">Tarif Baru</th></tr>
          <template x-for="b in (sinkron ? sinkron.berubah : [])" :key="b.komponen_id">
            <tr>
              <td x-text="b.kode_analisa"></td>
              <td><span x-text="b.komponen"></span><br><span class="kecil-teks" x-text="b.kode_bahan + ' · ' + b.nama_bahan"></span></td>
              <td x-text="b.satuan"></td>
              <td class="angka" x-text="'Rp ' + Math.round(b.tarif_lama).toLocaleString('id-ID')"></td>
              <td class="angka"><strong x-text="'Rp ' + Math.round(b.tarif_baru).toLocaleString('id-ID')"></strong></td>
            </tr>
          </template>
        </table>
      </div>
      <form method="post" action="<?= base_url('bahan/sinkron-analisa') ?>" style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
        <?= csrf_field() ?>
        <button type="button" @click="tutupSinkron()">Batal</button>
        <button type="submit" class="tombol">Ya, Sinkronkan <span x-text="sinkron ? sinkron.berubah.length : 0"></span> Komponen</button>
      </form>
    </div>
  </div>
