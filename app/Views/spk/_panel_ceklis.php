<?php /* Partial spk/detail: panel Ceklis Kelengkapan Dokumen Bukti Belanja. */ ?><div class="panel">
  <h2>Ceklis Kelengkapan Dokumen Bukti Belanja</h2>
  <p class="kecil-teks" style="margin-top:0">Menurut Juknis, pengadaan di luar SIPLah dibuktikan dengan dokumen perencanaan, hasil pembandingan, hasil negosiasi, surat pemesanan, hasil pemeriksaan barang, dan BAST. Status modul terisi otomatis; dokumen fisik ditandai manual.</p>
  <table data-cari="Cari dokumen…">
    <tr><th style="width:60px">Status</th><th>Dokumen</th><th style="width:170px">Penanda</th></tr>
    <?php foreach ($ceklis as $c): ?>
    <tr>
      <td><?= $c['ada'] ? '✓ Ada' : '✗ Belum' ?></td>
      <td><?= esc($c['label']) ?></td>
      <td>
        <?php if ($c['manual']): ?>
        <form class="inline" method="post" action="<?= base_url('spk/ceklis/' . $s['id']) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="item" value="<?= esc($c['kunci']) ?>">
          <label style="display:inline-flex;gap:6px;align-items:center"><input type="checkbox" name="ada" value="1" <?= $c['ada'] ? 'checked' : '' ?> onchange="this.form.submit()"> Sudah ada (fisik)</label>
        </form>
        <?php elseif (! empty($c['tautan'])): ?>
        <a class="kecil-teks" href="<?= base_url($c['tautan']) ?>"><?= esc($c['label_tautan'] ?? 'Buka modulnya') ?> →</a>
        <?php else: ?>
        <span class="kecil-teks">otomatis dari modul</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
