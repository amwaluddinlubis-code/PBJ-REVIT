<div class="panel">
  <p>BAP dibuat dari rincian SPK (kondisi awal "Cukup", dapat diubah per barang). BAST dibuat per SPK. Nomor surat mengikuti pola 422/&lt;JENIS&gt;/&lt;urut&gt;/&lt;tahun&gt;.</p>
  <p><a class="tombol kecil abu" href="<?= base_url('dokumen/cetak-papan-nama') ?>" target="_blank">Cetak Papan Nama Kegiatan</a> <span class="kecil-teks">Papan nama kegiatan sesuai format Juknis, dicetak dari identitas sekolah. BAST Aset per gedung tersedia di halaman LPJ Gedung.</span></p>
  <table data-paginasi="10" data-cari="Cari SPK / paket / penyedia…">
    <tr><th>SPK</th><th>Paket / Penyedia</th><th>BAP</th><th>BAST</th><th>Cetak SPK</th></tr>
    <?php foreach ($spk as $s): ?>
    <tr>
      <td><a href="<?= base_url('spk/detail/' . $s['id']) ?>"><?= (int) $s['nomor'] ?></a></td>
      <td><?= esc($s['paket'] ?? '—') ?><br><span class="kecil-teks"><?= esc($s['penyedia'] ?? '—') ?></span></td>
      <td>
        <?php foreach ($bap[$s['id']] ?? [] as $b): ?>
          <div>BAP urut <?= (int) $b['nomor_urut'] ?> · <?= esc($b['tanggal'] ?? 'tgl?') ?>
            <a href="<?= base_url('dokumen/bap-ubah/' . $b['id']) ?>">Ubah</a> ·
            <a href="<?= base_url('dokumen/cetak-bap/' . $b['id']) ?>" target="_blank">Cetak</a></div>
        <?php endforeach; ?>
        <form class="inline" method="post" action="<?= base_url('dokumen/bap-buat/' . $s['id']) ?>"><?= csrf_field() ?><button class="kecil" type="submit">+ Buat BAP</button></form>
      </td>
      <td>
        <?php foreach ($bast[$s['id']] ?? [] as $b): ?>
          <div>BAST urut <?= (int) $b['nomor_urut'] ?> · <?= esc($b['tanggal'] ?? 'tgl?') ?>
            <a href="<?= base_url('dokumen/bast-ubah/' . $b['id']) ?>">Ubah</a> ·
            <a href="<?= base_url('dokumen/cetak-bast/' . $b['id']) ?>" target="_blank">Cetak</a></div>
        <?php endforeach; ?>
        <form class="inline" method="post" action="<?= base_url('dokumen/bast-buat/' . $s['id']) ?>"><?= csrf_field() ?><button class="kecil" type="submit">+ Buat BAST</button></form>
      </td>
      <td><a href="<?= base_url('dokumen/cetak-spk/' . $s['id']) ?>" target="_blank">Cetak SPK</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
