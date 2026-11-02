<?php
// Halaman bantuan
$isiBantuan = function () { ?>
  <div class="judul-halaman"><div>
    <h1>Bantuan & Aksesibilitas</h1>
    <p>Panduan singkat penggunaan FILKOMpanion untuk mahasiswa, relawan, dan Admin FILKOM.</p>
  </div></div>
  <div class="grid grid--dua">
    <section class="kartu" id="bantuan">
      <div class="kartu__kepala"><span class="ikon-tile"><?= ikon('help') ?></span><div><h2>Panduan penggunaan</h2></div></div>
      <ol>
        <li><strong>Mahasiswa</strong>: lengkapi Profil Kebutuhan, ajukan tiket, pantau di Tiket Saya, lalu konfirmasi kehadiran atau laporkan kendala.</li>
        <li><strong>Relawan</strong>: kirim Pendaftaran Relawan, atur Profil & Ketersediaan, terima Penugasan, unggah bukti, lalu selesaikan tiket.</li>
        <li><strong>Admin FILKOM</strong>: validasi relawan, verifikasi tiket dengan KRS, tindak lanjuti kendala, validasi jam, dan unduh laporan rekap.</li>
      </ol>
    </section>
    <section class="kartu" id="aksesibilitas">
      <div class="kartu__kepala"><span class="ikon-tile ikon-tile--kuning"><?= ikon('eye') ?></span><div><h2>Aksesibilitas antarmuka</h2></div></div>
      <ul>
        <li>Kontras warna jelas; status selalu memakai ikon dan teks, tidak hanya warna.</li>
        <li>Seluruh form dapat dioperasikan dengan keyboard (Tab, Spasi, Enter).</li>
        <li>Tidak ada instruksi berbasis suara; semua informasi tertulis.</li>
        <li>Blok kalender ketersediaan dapat diubah dengan klik atau tombol Spasi.</li>
      </ul>
    </section>
    <section class="kartu" id="privasi">
      <div class="kartu__kepala"><span class="ikon-tile"><?= ikon('shield') ?></span><div><h2>Kebijakan privasi</h2></div></div>
      <p>FILKOMpanion hanya menyimpan data yang diperlukan untuk layanan pendampingan: identitas akun UB, kategori dan
        kebutuhan pendampingan (tanpa data medis), jadwal tiket, bukti kegiatan, dan jam pengabdian. Kata sandi tidak
        pernah disimpan karena autentikasi dilakukan melalui akun UB.</p>
    </section>
  </div>
<?php };
if (!empty($pengguna)) {
    require __DIR__ . '/layout/kepala.php';
    $isiBantuan();
    require __DIR__ . '/layout/kaki.php';
} else { ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bantuan · FILKOMpanion</title>
  <link rel="stylesheet" href="<?= aset('css/app.css') ?>">
</head>
<body><main class="halaman" style="max-width:1200px;margin:0 auto">
  <p><a class="tautan" href="<?= url('login') ?>">← Kembali ke halaman masuk</a></p><br>
  <?php $isiBantuan(); ?>
</main></body>
</html>
<?php } ?>
