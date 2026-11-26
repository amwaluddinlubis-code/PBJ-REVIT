<?php
/**
 * Halaman Panduan Penggunaan: daftar bab (jangkar) + isi panduan yang
 * dirender dari docs/Panduan_Penggunaan.md oleh MarkdownMini.
 */
?>
<?php if (($bab ?? []) !== []): ?>
<details class="panel panel-lipat" data-lipat-id="panduan-daftar-bab" open>
  <summary>Daftar Bab</summary>
  <ol class="panduan-bab">
    <?php foreach ($bab as $b): ?>
    <li><a href="#<?= esc($b['slug']) ?>"><?= esc($b['judul']) ?></a></li>
    <?php endforeach; ?>
  </ol>
</details>
<?php endif; ?>
<div class="panel panduan-isi">
<?= $isi ?? '' ?>
</div>
