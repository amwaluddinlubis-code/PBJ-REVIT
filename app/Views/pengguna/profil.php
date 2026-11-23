<div class="panel">
  <h2>Data Profil</h2>
  <div style="display:flex;gap:14px;align-items:center;margin-bottom:16px">
    <div class="ava" style="width:52px;height:52px;font-size:19px;border-radius:50%;background:var(--ta-biru);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700"><?= esc(strtoupper(substr($saya['nama'] ?? 'A', 0, 1))) ?></div>
    <div>
      <div style="font-weight:700;font-size:15.2px"><?= esc($saya['nama'] ?? '') ?></div>
      <div class="kecil-teks">@<?= esc($saya['username'] ?? '') ?> · peran <?= esc(ucfirst($saya['peran'] ?? '')) ?> · terdaftar <?= esc(tgl($saya['created_at'] ?? null)) ?></div>
    </div>
  </div>
  <form method="post" action="<?= base_url('profil/simpan') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Nama lengkap<input type="text" name="nama" value="<?= esc($saya['nama'] ?? '') ?>" required></label>
      <label>Username (tidak dapat diubah)<input type="text" value="<?= esc($saya['username'] ?? '') ?>" disabled></label>
      <div class="aksi md:col-span-2"><button type="submit">Simpan Profil</button></div>
    </div>
  </form>
</div>

<div class="panel">
  <h2>Ganti Kata Sandi</h2>
  <form method="post" action="<?= base_url('profil/sandi') ?>">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <label>Kata sandi lama<input type="password" name="sandi_lama" required autocomplete="current-password"></label>
      <label>Kata sandi baru (min. 6 karakter)<input type="password" name="sandi_baru" required autocomplete="new-password"></label>
      <label>Ulangi kata sandi baru<input type="password" name="sandi_ulang" required autocomplete="new-password"></label>
      <div class="aksi md:col-span-2"><button type="submit">Ganti Kata Sandi</button></div>
    </div>
  </form>
</div>
