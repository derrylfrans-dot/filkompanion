<?php
// Halaman login
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk · FILKOMpanion</title>
  <link rel="icon" href="<?= aset('img/logo.svg') ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= aset('css/app.css') ?>">
</head>
<body>
<div class="login">
  <section class="login__brand" aria-label="Tentang FILKOMpanion">
    <span class="login__lingkar" aria-hidden="true"></span>
    <span class="login__lingkar2" aria-hidden="true"></span>
    <div class="login__logo">
      <img src="<?= aset('img/logo-terang.svg') ?>" alt="">
      <span>FILKOM<b>panion</b></span>
    </div>
    <p class="login__kicker">LAYANAN PENDAMPINGAN · FILKOM UB</p>
    <h1 class="login__judul">Temani Aktivitas<br>Kampusmu.</h1>
    <p class="login__deskripsi">FILKOMpanion membantu mahasiswa disabilitas memperoleh layanan pendampingan sesuai
      kebutuhan, jadwal, dan jenis layanan, serta membantu pengelolaan relawan pendamping.</p>
    <ul class="fitur">
      <li><span class="bulat"><?= ikon('ticket') ?></span>
        <span><strong>Pengajuan pendampingan</strong><span>Ajukan layanan sesuai jadwal dan kebutuhan.</span></span></li>
      <li><span class="bulat"><?= ikon('users') ?></span>
        <span><strong>Pencocokan relawan</strong><span>Relawan dipilih berdasarkan keahlian dan ketersediaan.</span></span></li>
      <li><span class="bulat"><?= ikon('clipboard') ?></span>
        <span><strong>Pencatatan kegiatan</strong><span>Bukti kegiatan dan jam pengabdian tercatat rapi.</span></span></li>
    </ul>
    <div class="login__akses">
      <strong>AKSESIBILITAS</strong>
      <p>Kontras jelas · Struktur sederhana · Navigasi konsisten · Tanpa instruksi berbasis suara</p>
      <p>Fakultas Ilmu Komputer · Universitas Brawijaya</p>
    </div>
  </section>

  <main class="login__form">
    <div class="login__kartu">
      <h1>Selamat Datang Kembali</h1>
      <p>Masuk menggunakan akun email Universitas Brawijaya untuk mengakses layanan pendampingan.</p>

      <?php $flashKelas = ''; require __DIR__ . '/layout/flash.php'; ?>

      <form method="post" action="<?= url('login/submit') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="field">
          <label for="emailUB">Email UB</label>
          <div class="input-ikon">
            <?= ikon('mail') ?>
            <input class="input" type="email" id="emailUB" name="emailUB" required autocomplete="email"
                   placeholder="contoh: nama@ub.ac.id" value="<?= e($isian['emailUB'] ?? '') ?>"
                   aria-describedby="bantuan-email">
          </div>
          <span class="bantuan-field" id="bantuan-email">Akun email UB yang dipakai untuk autentikasi.</span>
        </div>
        <button type="submit" class="tombol tombol--penuh"><?= ikon('log-in') ?>Masuk dengan Akun UB</button>
      </form>

      <p class="login__catatan"><?= ikon('shield') ?>
        <span>Autentikasi diproses melalui Google Workspace UB. Kata sandi tidak diketik atau disimpan di FILKOMpanion.</span></p>

      <div class="alert alert--ringkas">
        <?= ikon('info') ?>
        <div>
          <strong>Khusus akun email UB</strong>
          Hanya akun email UB yang dapat digunakan. Peran (Mahasiswa Disabilitas, Relawan Pendamping aktif, atau
          Admin FILKOM) ditentukan otomatis setelah masuk.
        </div>
      </div>
      <p class="login__bantuan">
        <a class="tautan" href="<?= url('bantuan') ?>"><?= ikon('help') ?><u>Butuh bantuan akses? Buka panduan aksesibilitas</u></a>
      </p>
    </div>

    <aside class="login__relawan">
      <span class="bulat"><?= ikon('user-plus') ?></span>
      <div>
        <strong>Ingin menjadi Relawan Pendamping?</strong>
        Masuk dengan akun UB, lalu ajukan lewat menu Pendaftaran Relawan. Status relawan aktif diberikan setelah
        divalidasi Admin FILKOM.
      </div>
    </aside>

    <footer class="login__kaki">
      <span>© 2026 FILKOMpanion</span>
      <a href="<?= url('bantuan') ?>#privasi">Kebijakan Privasi</a> ·
      <a href="<?= url('bantuan') ?>#bantuan">Bantuan</a> ·
      <a href="<?= url('bantuan') ?>#aksesibilitas">Aksesibilitas</a>
    </footer>
  </main>
</div>
</body>
</html>
