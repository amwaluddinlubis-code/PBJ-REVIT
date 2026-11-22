<div class="panel">
  <h2>Akses Menu per Tingkatan User</h2>
  <p class="kecil-teks">Centang menu yang boleh dibuka tiap peran. Menu yang tidak dicentang hilang dari sidebar peran itu dan alamatnya ditolak server. Perubahan berlaku seketika, tanpa perlu pengguna masuk ulang.</p>
  <form method="post" action="<?= base_url('pengaturan/akses-menu') ?>">
    <?= csrf_field() ?>
    <div class="bungkus-tabel">
      <table>
        <tr>
          <th>Menu</th>
          <th style="text-align:center">Admin</th>
          <?php foreach ($peranList as $p): ?>
          <th style="text-align:center"><?= esc(ucfirst($p)) ?></th>
          <?php endforeach; ?>
        </tr>
        <?php foreach ($katalog as $grup => $items): ?>
        <tr>
          <td colspan="<?= 2 + count($peranList) ?>" style="font-weight:600;background:var(--ina-surface)"><?= esc($grup) ?></td>
        </tr>
        <?php foreach ($items as $kunci => $label): ?>
        <tr>
          <td><?= esc($label) ?></td>
          <td style="text-align:center"><input type="checkbox" checked disabled aria-label="Admin selalu boleh membuka <?= esc($label) ?>"></td>
          <?php foreach ($peranList as $p): ?>
          <td style="text-align:center"><input type="checkbox" name="akses[<?= esc($p) ?>][<?= esc($kunci) ?>]" value="1" <?= ($peta[$p][$kunci] ?? true) ? 'checked' : '' ?> aria-label="<?= esc($label) ?> untuk peran <?= esc($p) ?>"></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>
      </table>
    </div>
    <p class="kecil-teks">Menu berikut selalu khusus admin dan tidak diatur di sini: Manajemen User, Pemeliharaan Database, Jejak Audit. Dashboard, Profil, dan ganti sekolah selalu tersedia untuk semua peran. Pembatasan aksi tertentu yang sudah ada (mis. impor hanya operator, aksi uang hanya bendahara) tetap berlaku di atas pengaturan ini.</p>
    <div class="aksi">
      <button type="submit">Simpan Pengaturan</button>
    </div>
  </form>
  <form method="post" action="<?= base_url('pengaturan/akses-menu') ?>" data-konfirmasi="Kembalikan akses menu semua peran ke bawaan (semua menu katalog boleh dibuka)?" data-konfirmasi-judul="Atur Ulang Akses Menu" data-konfirmasi-tombol="Ya, Atur Ulang" style="display:inline-block;margin-top:8px">
    <?= csrf_field() ?>
    <input type="hidden" name="atur_ulang" value="1">
    <button type="submit" class="abu">Atur Ulang ke Bawaan</button>
  </form>
</div>
