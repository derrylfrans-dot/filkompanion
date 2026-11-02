<?php
// Halaman 403
require __DIR__ . '/layout/kepala.php';
?>
<div class="kartu kosong">
  <?= ikon('lock') ?>
  <h1 style="font-size:22px;color:var(--teks)">Akses ditolak</h1>
  <p style="margin:8px 0 20px">Menu ini tidak tersedia untuk peran <?= e($pengguna['rolePengguna']) ?>.</p>
  <a class="tombol" href="<?= url('beranda') ?>">Kembali ke <?= e($akarBreadcrumb) ?></a>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
