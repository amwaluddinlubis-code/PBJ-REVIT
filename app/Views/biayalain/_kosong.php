<?php /* Partial biayalain/index: keadaan kosong (belum ada biaya non-gedung). */ ?>  <?php if ($kelompok === []): ?>
  <div class="panel biaya-kosong">
    <div class="kosong-ikon">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#0049b8" stroke-width="1.6"><path d="M3 3v18h18V3H3z"/><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H5z"/></svg>
    </div>
    <h3 style="margin:0 0 6px;font-size:13px;font-weight:600">Belum Ada Biaya Non-Gedung</h3>
    <p class="petunjuk-field">Impor dokumen perencana yang memuat sheet <i>Prcn dan pgws</i> / <i>ADMINISTRASI</i>, atau tambahkan baris manual lewat tombol di atas.</p>
    <div style="display:flex;justify-content:center;gap:10px">
      <a class="tombol abu" href="<?= base_url('impor') ?>">Kelola Impor Dokumen</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Tabel Data per Kelompok -->
