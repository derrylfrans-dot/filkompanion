<?php
// Pesan flash
if (!empty($flash)):
    $kelas = ['sukses' => 'alert--sukses', 'error' => 'alert--error', 'info' => ''][$flash['tipe']] ?? '';
    $ik = ['sukses' => 'check-circle', 'error' => 'alert-circle', 'info' => 'info'][$flash['tipe']] ?? 'info';
?>
  <div class="alert <?= $kelas ?> <?= $flashKelas ?? '' ?>" role="<?= $flash['tipe'] === 'error' ? 'alert' : 'status' ?>">
    <?= ikon($ik) ?>
    <div>
      <strong><?= e($flash['judul']) ?></strong>
      <?php if ($flash['pesan'] !== ''): ?><span><?= e($flash['pesan']) ?></span><?php endif; ?>
    </div>
  </div>
<?php endif; ?>
