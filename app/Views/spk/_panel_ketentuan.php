<?php /* Partial spk/detail: panel Ketentuan Juknis Revitalisasi untuk paket ini (panel informasi). */ ?>
<details class="panel panel-lipat" data-lipat-id="spk-detail-ketentuan-juknis-revitalisasi-untuk-paket">
  <summary>Ketentuan Juknis Revitalisasi untuk Paket Ini</summary>

  <p class="kecil-teks" style="margin-bottom:0">
    Nilai paket <b><?= rp($nilai_paket) ?></b> · Bentuk surat pemesanan menurut nilai: <b><?= esc($ketentuan['dokumen']) ?></b> ·
    Metode penetapan penyedia: <b><?= esc($ketentuan['metode']) ?></b> dengan calon penyedia minimal <b><?= (int) $ketentuan['min_penyedia'] ?></b> ·
    Toko pembanding tercatat di Survei Harga untuk bahan paket ini: <b><?= (int) $pembanding ?> toko</b><?php if ($pembanding < (int) $ketentuan['min_penyedia']): ?> — <b style="color:#b91c1c">masih kurang dari minimum menurut Juknis, lengkapi di <a href="<?= base_url('survei') ?>">Survei Harga</a></b><?php endif; ?>.
    <?php if ($nilai_paket <= 50000000): ?>Menurut Juknis, nilai sampai dengan Rp50 juta cukup dengan nota/kuitansi — SPK yang lebih lengkap seperti ini tetap boleh dipakai.<?php endif; ?>
    <?php if ($ketentuan['materai']): ?>Kuitansi pembayaran Rp5 juta ke atas wajib dibubuhi materai Rp10.000.<?php endif; ?>
  </p>
</details>
