<?php /* Partial spk/detail: panel ringkasan SPK (desain ulang 26 Nov 2026) — fakta kepala SPK dalam kisi + Ubah SPK lewat modal helper form_modal (_modal_ubah_spk). */ ?>
<details class="panel panel-lipat" data-lipat-id="spk-detail-spk-nomor" open>
  <summary>SPK Nomor <?= (int) $s['nomor'] ?> — <?= esc($s['paket'] ?? '') ?></summary>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-2" style="margin-top:4px">
    <div><span class="kecil-teks">Tanggal SPK</span><br><strong><?= esc(tgl($s['tanggal'] ?? null)) ?></strong></div>
    <div><span class="kecil-teks">Periode Pelaksanaan</span><br><strong><?= esc(tgl($s['tgl_mulai'] ?? null)) ?> s.d. <?= esc(tgl($s['tgl_selesai'] ?? null)) ?></strong></div>
    <div><span class="kecil-teks">Status</span><br><strong><?= esc($s['status']) ?></strong></div>
    <div><span class="kecil-teks">Penyedia</span><br><?php if (trim((string) ($s['penyedia'] ?? '')) !== ''): ?><strong><?= esc($s['penyedia']) ?></strong><?php if (! empty($s['penerima_manual'])): ?> <span class="lencana abu">penyedia Masyarakat</span><?php endif; ?><br><span class="kecil-teks">Tanda tangan: <?= esc($s['ttd_penyedia'] ?? '—') ?></span><?php else: ?><strong>— belum diisi</strong><br><span class="kecil-teks">Klik Ubah SPK untuk melengkapi penyedia.</span><?php endif; ?></div>
    <div><span class="kecil-teks">Gedung Tujuan</span><br><strong><?= ! empty($gabungan) ? 'Gabungan beberapa gedung' : esc(($gedung_spk ?? null) ?: '—') ?></strong></div>
    <div><span class="kecil-teks">Nilai Paket</span><br><strong><?= rp($nilai_paket) ?></strong> <span class="kecil-teks">(<?= count($detail) ?> rincian)</span></div>
  </div>
  <p style="margin:12px 0 0">
    <button type="button" class="tombol" onclick="bukaFormModal('modal-ubah-spk', <?= esc(json_encode([
        'id' => (int) $s['id'], 'nomor' => (int) $s['nomor'], 'paket' => (string) ($s['paket'] ?? ''),
        'penyedia_id' => $s['penyedia_id'] !== null ? (int) $s['penyedia_id'] : '', 'penerima_manual' => (string) ($s['penerima_manual'] ?? ''),
        'ttd_penyedia' => (string) ($s['ttd_asli'] ?? ''), 'tanggal' => (string) ($s['tanggal'] ?? ''),
        'tgl_mulai' => (string) ($s['tgl_mulai'] ?? ''), 'tgl_selesai' => (string) ($s['tgl_selesai'] ?? ''),
        'gedung_id' => $s['gedung_id'] !== null ? (int) $s['gedung_id'] : '', 'status' => (string) ($s['status'] ?? 'Aktif'),
    ], JSON_UNESCAPED_UNICODE), 'attr') ?>)">Ubah SPK</button>
    <a class="tombol abu" href="<?= base_url('dokumen/cetak-spk/' . $s['id']) ?>" target="_blank">Cetak SPK</a>
    <a class="tombol abu" href="<?= base_url('spk') ?>">Register SPK</a>
  </p>
</details>
