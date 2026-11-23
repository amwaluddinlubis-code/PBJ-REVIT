<div class="aksi md:col-span-2" style="margin:0 0 12px"><button type="button" class="tombol" onclick="bukaSekolahBaru()">+ Tambah Sekolah</button></div>
<div class="panel">
  <p>Setiap sekolah mempunyai data sendiri yang terpisah penuh: bahan/RAB, penyedia, SPK, survei, invoice, dan buku material. Yang tampil di seluruh aplikasi adalah <strong>sekolah aktif</strong> — ganti lewat daftar ini atau pengalih di menu samping.</p>
  <table data-paginasi="10" data-cari="Cari sekolah…">
    <tr><th>Sekolah</th><th>NPSN</th><th>Kecamatan</th><th>Tahun Anggaran</th><th class="angka">Bahan</th><th class="angka">SPK</th><th>Status</th><th></th></tr>
    <?php foreach ($daftar as $s): $aktif = (int) ($sekolahAktif['id'] ?? 0) === (int) $s['id']; ?>
    <tr<?= $aktif ? ' style="font-weight:bold"' : '' ?>>
      <td><?= esc($s['nama_sekolah'] ?? '') ?></td>
      <td><?= esc($s['npsn'] ?? '—') ?></td>
      <td><?= esc($s['kecamatan'] ?? '—') ?></td>
      <td><?= esc($s['tahun_anggaran'] ?? '—') ?></td>
      <td class="angka"><?= (int) $s['jumlah_bahan'] ?></td>
      <td class="angka"><?= (int) $s['jumlah_spk'] ?></td>
      <td><?= $aktif ? 'Aktif sekarang' : '' ?></td>
      <td>
        <?php if (! $aktif): ?><a href="<?= base_url('sekolah/pilih/' . $s['id']) ?>">Jadikan Aktif</a> · <?php endif; ?>
        <form class="inline" method="post" action="<?= base_url('sekolah/hapus/' . $s['id']) ?>" data-konfirmasi="Hapus sekolah kosong ini?"><?= csrf_field() ?><button class="kecil bahaya" type="submit">Hapus</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?= $this->include('sekolah/_modal_form') ?>
