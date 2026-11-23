<?php
$ikonCetak = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg>';
$bukaModal = static fn (string $id) => "document.getElementById('{$id}').hidden=false";
$tutupModal = static fn (string $id) => "document.getElementById('{$id}').hidden=true";
?>
<div class="panel">
  <p>Aturan: BAP dibuat dari rincian SPK dan tombol Buat BAP hilang bila semua rincian sudah ter-BAP; <b>satu BAP hanya mempunyai satu BAST</b>. Form BAP &amp; BAST dibuka sebagai modal lewat tombol Ubah. Nomor surat mengikuti pola 422/&lt;JENIS&gt;/&lt;urut&gt;/&lt;tahun&gt;.</p>
  <p><a class="tombol kecil abu" href="<?= base_url('dokumen/cetak-papan-nama') ?>" target="_blank">Cetak Papan Nama Kegiatan</a> <span class="kecil-teks">Papan nama kegiatan sesuai format Juknis, dicetak dari identitas sekolah. BAST Aset per gedung tersedia di halaman LPJ Gedung.</span></p>
  <table data-paginasi="10" data-cari="Cari SPK / paket / penyedia…">
    <tr><th>SPK</th><th>Paket / Penyedia</th><th>BAP &amp; BAST</th><th>Cetak SPK</th></tr>
    <?php foreach ($spk as $s): ?>
    <tr>
      <td><a href="<?= base_url('spk/detail/' . $s['id']) ?>"><?= (int) $s['nomor'] ?></a></td>
      <td><?= esc($s['paket'] ?? '—') ?><br><span class="kecil-teks"><?= esc($s['penyedia'] ?? '—') ?></span></td>
      <td>
        <?php foreach ($bap[$s['id']] ?? [] as $b): ?>
          <div style="margin-bottom:6px">BAP urut <?= (int) $b['nomor_urut'] ?> · <?= esc(tgl($b['tanggal'] ?? null, 'tgl?')) ?>
            <button type="button" class="tombol kecil abu" onclick="<?= $bukaModal('modal-bap-' . $b['id']) ?>">Ubah</button>
            <a class="tombol-cetak tc-bap" href="<?= base_url('dokumen/cetak-bap/' . $b['id']) ?>" target="_blank" title="Cetak BAP"><?= $ikonCetak ?></a>
            <?php foreach ($bast_per_bap[$b['id']] ?? [] as $bs): ?>
              <div style="margin:4px 0 0 16px">↳ BAST urut <?= (int) $bs['nomor_urut'] ?> · <?= esc(tgl($bs['tanggal'] ?? null, 'tgl?')) ?>
                <button type="button" class="tombol kecil abu" onclick="<?= $bukaModal('modal-bast-' . $bs['id']) ?>">Ubah</button>
                <a class="tombol-cetak tc-bast" href="<?= base_url('dokumen/cetak-bast/' . $bs['id']) ?>" target="_blank" title="Cetak BAST"><?= $ikonCetak ?></a>
              </div>
            <?php endforeach; ?>
            <?php if (empty($bast_per_bap[$b['id']])): ?>
              <form class="inline" method="post" action="<?= base_url('dokumen/bast-buat/' . $b['id']) ?>" style="margin-left:16px"><?= csrf_field() ?><button class="kecil" type="submit">+ Buat BAST</button></form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php foreach ($bast_umum[$s['id']] ?? [] as $bs): ?>
          <div style="margin-bottom:6px">BAST urut <?= (int) $bs['nomor_urut'] ?> · <?= esc(tgl($bs['tanggal'] ?? null, 'tgl?')) ?> <span class="kecil-teks">(belum tertaut BAP)</span>
            <button type="button" class="tombol kecil abu" onclick="<?= $bukaModal('modal-bast-' . $bs['id']) ?>">Ubah</button>
            <a class="tombol-cetak tc-bast" href="<?= base_url('dokumen/cetak-bast/' . $bs['id']) ?>" target="_blank" title="Cetak BAST"><?= $ikonCetak ?></a>
          </div>
        <?php endforeach; ?>
        <?php if ((int) $s['jumlah_terbap'] < (int) $s['jumlah_detail']): ?>
          <form class="inline" method="post" action="<?= base_url('dokumen/bap-buat/' . $s['id']) ?>"><?= csrf_field() ?><button class="kecil" type="submit">+ Buat BAP</button></form>
        <?php else: ?>
          <span class="kecil-teks">Semua rincian sudah ter-BAP (<?= (int) $s['jumlah_terbap'] ?>/<?= (int) $s['jumlah_detail'] ?>)</span>
        <?php endif; ?>
      </td>
      <td><a class="tombol-cetak tc-spk" href="<?= base_url('dokumen/cetak-spk/' . $s['id']) ?>" target="_blank" title="Cetak SPK"><?= $ikonCetak ?></a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php foreach ($spk as $s): ?>
  <?php foreach ($bap[$s['id']] ?? [] as $b): ?>
  <div id="modal-bap-<?= (int) $b['id'] ?>" class="mk-lapik" hidden onclick="if(event.target===this)this.hidden=true">
    <div class="mk-kartu" role="dialog" aria-modal="true" style="max-width:820px;width:96vw;text-align:left">
      <h3 style="margin-top:0">BAP Urut <?= (int) $b['nomor_urut'] ?> — SPK Nomor <?= (int) $s['nomor'] ?></h3>
      <form method="post" action="<?= base_url('dokumen/bap-simpan/' . $b['id']) ?>">
        <?= csrf_field() ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
          <label>Nomor Urut BAP <input type="number" name="nomor_urut" min="1" value="<?= esc($b['nomor_urut']) ?>" required></label>
          <label>Tanggal Pemeriksaan <input type="date" name="tanggal" value="<?= esc($b['tanggal'] ?? '') ?>"></label>
          <label>Tanggal SPK <input type="date" value="<?= esc($s['tanggal'] ?? '') ?>" readonly></label>
          <label>Tanggal Mulai SPK <input type="date" value="<?= esc($s['tgl_mulai'] ?? '') ?>" readonly></label>
          <label>Tanggal Selesai SPK <input type="date" value="<?= esc($s['tgl_selesai'] ?? '') ?>" readonly></label>
        </div>
        <table style="margin-top:14px">
          <tr><th>Nama Barang</th><th class="angka">Jumlah</th><th>Satuan</th><th>Kondisi</th><th>Keterangan</th></tr>
          <?php foreach ($bap_items[$b['id']] ?? [] as $it): ?>
          <tr>
            <td><?= esc($it['nama']) ?></td>
            <td class="angka"><?= number_format((float) $it['qty'], 2, ',', '.') ?></td>
            <td><?= esc($it['satuan'] ?? '') ?></td>
            <td>
              <select data-select-cari name="kondisi[<?= (int) $it['bd_id'] ?>]" style="width:140px">
                <?php foreach (['Baik', 'Cukup', 'Kurang', 'Tidak Sesuai'] as $k): ?>
                  <option<?= $it['kondisi'] === $k ? ' selected' : '' ?>><?= $k ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input name="keterangan[<?= (int) $it['bd_id'] ?>]" value="<?= esc($it['keterangan'] ?? '') ?>"></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <div class="aksi" style="margin-top:14px"><button type="submit">Simpan BAP</button>
        <button type="button" class="tombol abu" onclick="<?= $tutupModal('modal-bap-' . $b['id']) ?>">Batal</button></div>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
  <?php
  $daftarBast = $bast_umum[$s['id']] ?? [];
  foreach ($bap[$s['id']] ?? [] as $b) {
      foreach ($bast_per_bap[$b['id']] ?? [] as $bs) {
          $daftarBast[] = $bs;
      }
  }
  ?>
  <?php foreach ($daftarBast as $bs): ?>
  <div id="modal-bast-<?= (int) $bs['id'] ?>" class="mk-lapik" hidden onclick="if(event.target===this)this.hidden=true">
    <div class="mk-kartu" role="dialog" aria-modal="true" style="max-width:640px;width:96vw;text-align:left">
      <h3 style="margin-top:0">BAST Urut <?= (int) $bs['nomor_urut'] ?> — SPK Nomor <?= (int) $s['nomor'] ?></h3>
      <form method="post" action="<?= base_url('dokumen/bast-simpan/' . $bs['id']) ?>">
        <?= csrf_field() ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
          <label>Nomor Urut BAST <input type="number" name="nomor_urut" min="1" value="<?= esc($bs['nomor_urut']) ?>" required></label>
          <label>Tanggal Serah Terima <input type="date" name="tanggal" value="<?= esc($bs['tanggal'] ?? '') ?>"></label>
          <label>Tanggal SPK <input type="date" value="<?= esc($s['tanggal'] ?? '') ?>" readonly></label>
          <label>Tanggal Mulai SPK <input type="date" value="<?= esc($s['tgl_mulai'] ?? '') ?>" readonly></label>
          <label>Tanggal Selesai SPK <input type="date" value="<?= esc($s['tgl_selesai'] ?? '') ?>" readonly></label>
        </div>
        <div class="aksi" style="margin-top:14px"><button type="submit">Simpan BAST</button>
        <button type="button" class="tombol abu" onclick="<?= $tutupModal('modal-bast-' . $bs['id']) ?>">Batal</button></div>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
<?php endforeach; ?>
