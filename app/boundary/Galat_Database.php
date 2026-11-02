<?php
// Galat koneksi database
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database belum siap · FILKOMpanion</title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<main class="login__form" style="min-height:100vh">
  <div class="login__kartu">
    <h1>Database belum siap</h1>
    <p>FILKOMpanion tidak dapat terhubung atau membaca database MySQL.</p>
    <div class="alert alert--error"><div>
      <strong>Periksa langkah berikut</strong>
      <ol>
        <li>MySQL/MariaDB sudah berjalan (XAMPP Control Panel → MySQL → Start).</li>
        <li>File <code>database/schema.sql</code> lalu <code>database/seed.sql</code> sudah diimpor.</li>
        <li>Pengaturan host, user, dan password di <code>config/config.php</code> sesuai.</li>
      </ol>
    </div></div>
    <p class="bantuan-field">Pesan teknis: <?= htmlspecialchars($pesanError ?? '', ENT_QUOTES, 'UTF-8') ?></p>
  </div>
</main>
</body>
</html>
