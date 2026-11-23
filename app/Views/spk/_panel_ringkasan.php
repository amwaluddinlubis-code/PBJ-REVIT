<?php /* Partial spk/detail: panel ringkasan SPK (nomor, paket, penyedia, nilai, aksi dokumen). */ ?><div class="panel">
  <h2>SPK Nomor <?= (int) $s['nomor'] ?> — <?= esc($s['paket'] ?? '') ?></h2>
  <div class="kecil-teks">
    Tanggal <?= esc(tgl($s['tanggal'] ?? null)) ?> · Penyedia <?= trim((string) ($s['penyedia'] ?? '')) !== '' ? esc($s['penyedia']) : '<a href="' . base_url('spk/ubah/' . $s['id']) . '">— (belum diisi, lengkapi)</a>' ?><?php if (! empty($s['penerima_manual'])): ?> <span class="lencana abu">penyedia Masyarakat</span><?php endif; ?> (ttd: <?= esc($s['ttd_penyedia'] ?? '—') ?>) ·
    Periode <?= esc(tgl($s['tgl_mulai'] ?? null)) ?> s.d. <?= esc(tgl($s['tgl_selesai'] ?? null)) ?> · Status <?= esc($s['status']) ?> ·
    <a href="<?= base_url('spk/ubah/' . $s['id']) ?>">Ubah kepala SPK</a>
  </div>
</div>
