<?php
// Header dan sidebar
$peran = $pengguna['rolePengguna'] ?? '';
$relawanAktif = $statusRelawanLogin === 'Aktif';
if ($peran === 'Mahasiswa Disabilitas') {
    $areaMenu = 'Area Mahasiswa';
    $labelPeran = 'Mahasiswa';
    $menu = [
        ['beranda', 'home', 'Beranda', 'beranda'],
        ['profil', 'user', 'Profil Kebutuhan', 'profil'],
        ['tiket/ajukan', 'ticket', 'Ajukan Tiket', 'ajukan'],
        ['tiket', 'file-text', 'Tiket Saya', 'tiket'],
    ];
} elseif ($peran === 'Relawan Pendamping') {
    $areaMenu = 'Area Relawan';
    $labelPeran = $relawanAktif ? 'Relawan Pendamping' : 'Calon Relawan';
    $menu = $relawanAktif ? [
        ['beranda', 'home', 'Beranda', 'beranda'],
        ['relawan/profil', 'calendar', 'Profil & Ketersediaan', 'profil-relawan'],
        ['penugasan', 'clipboard', 'Penugasan', 'penugasan'],
        ['riwayat', 'history', 'Riwayat & Jam', 'riwayat'],
    ] : [
        ['beranda', 'home', 'Beranda', 'beranda'],
        ['relawan/daftar', 'user-plus', 'Pendaftaran Relawan', 'daftar'],
        [null, 'calendar', 'Profil & Ketersediaan', ''],
        [null, 'clipboard', 'Penugasan', ''],
        [null, 'history', 'Riwayat & Jam', ''],
    ];
} else {
    $areaMenu = 'Area Admin';
    $labelPeran = 'Admin FILKOM';
    $menu = [
        ['beranda', 'grid', 'Dashboard', 'beranda'],
        ['admin/relawan', 'shield', 'Validasi Relawan', 'admin-relawan'],
        ['admin/tiket', 'ticket', 'Verifikasi Tiket', 'admin-tiket'],
        ['admin/tiket-manual', 'file-plus', 'Tiket Manual', 'admin-manual'],
        ['admin/kendala', 'alert', 'Laporan Kendala', 'admin-kendala'],
        ['admin/jam', 'clock', 'Validasi Jam', 'admin-jam'],
        ['admin/laporan', 'chart', 'Laporan Rekap', 'admin-laporan'],
    ];
}
$akarBreadcrumb = $peran === 'Admin FILKOM' ? 'Dashboard' : 'Beranda';
$menuAktif = $menuAktif ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($judulHalaman ?? 'FILKOMpanion') ?> · FILKOMpanion</title>
  <link rel="icon" href="<?= aset('img/logo.svg') ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= aset('css/app.css') ?>">
</head>
<body>
<div class="app">
  <header class="header">
    <a class="brand" href="<?= url('beranda') ?>">
      <img src="<?= aset('img/logo.svg') ?>" alt="">
      <span>
        <span class="brand__nama"><b>FILKOM</b>panion</span><br>
        <span class="brand__tag">Temani Aktivitas Kampusmu.</span>
      </span>
    </a>
    <div class="header__sistem">
      <strong>Sistem Pendampingan Mahasiswa Disabilitas</strong>
      <span>Fakultas Ilmu Komputer · Universitas Brawijaya</span>
    </div>
    <div class="header__kanan">
      <a class="tautan-bantuan" href="<?= url('bantuan') ?>"><?= ikon('help') ?><span>Bantuan</span></a>
      <button type="button" class="lonceng" data-buka-notif data-csrf="<?= e(Auth::tokenCsrf()) ?>"
              aria-haspopup="true" aria-expanded="false" aria-controls="panel-notif"
              aria-label="Notifikasi, <?= (int) ($notifikasi['belumDibaca'] ?? 0) ?> belum dibaca">
        <?= ikon('bell') ?>
        <?php if (!empty($notifikasi['belumDibaca'])): ?>
          <span class="lonceng__jumlah"><?= (int) $notifikasi['belumDibaca'] ?></span>
        <?php endif; ?>
      </button>
      <div id="panel-notif" class="panel-notif" hidden>
        <h2>Notifikasi</h2>
        <?php foreach ($notifikasi['daftar'] ?? [] as $n): ?>
          <a class="notif <?= $n['dibaca'] ? '' : 'notif--baru' ?>" href="<?= e($n['tautan']) ?>">
            <strong><?= e($n['judul']) ?></strong>
            <span><?= e($n['pesan']) ?></span>
            <small><?= e(tglIndo($n['waktu'], 'lengkap')) ?></small>
          </a>
        <?php endforeach; ?>
        <?php if (empty($notifikasi['daftar'])): ?>
          <p class="notif-kosong">Belum ada notifikasi.</p>
        <?php endif; ?>
      </div>
      <div class="akun">
        <span class="avatar" aria-hidden="true"><?= e(inisial($pengguna['nama'] ?? '')) ?></span>
        <span class="akun__nama">
          <strong><?= e($pengguna['nama'] ?? '') ?></strong>
          <span><?= e($labelPeran) ?></span>
        </span>
        <button type="button" class="akun__tombol" data-buka-akun aria-haspopup="true" aria-expanded="false"
                aria-controls="menu-akun" aria-label="Menu akun"><?= ikon('chevron-down') ?></button>
        <div id="menu-akun" class="menu-akun" hidden>
          <a href="<?= url('bantuan') ?>"><?= ikon('help') ?>Panduan aksesibilitas</a>
          <form method="post" action="<?= url('logout') ?>">
            <?= csrf_field() ?>
            <button type="submit"><?= ikon('log-out') ?>Keluar</button>
          </form>
        </div>
      </div>
    </div>
  </header>

  <div class="isi">
    <nav class="sidebar" aria-label="Menu utama">
      <p class="sidebar__label"><?= e(strtoupper($areaMenu)) ?></p>
      <ul class="menu">
        <?php foreach ($menu as [$rute, $ik, $label, $kunci]): ?>
          <li>
            <?php if ($rute === null): ?>
              <span class="menu__terkunci" aria-disabled="true"><?= ikon($ik) ?>
                <span><?= e($label) ?><small><?= ikon('lock') ?>Aktif setelah validasi</small></span>
              </span>
            <?php else: ?>
              <a href="<?= url($rute) ?>" <?= $menuAktif === $kunci ? 'aria-current="page"' : '' ?>><?= ikon($ik) ?><?= e($label) ?></a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="kartu-bantuan">
        <strong><?= ikon('help') ?>Butuh bantuan?</strong>
        <p>Panduan penggunaan dan bantuan aksesibilitas antarmuka.</p>
        <a href="<?= url('bantuan') ?>">Buka panduan →</a>
      </div>
    </nav>

    <div class="konten">
      <main class="halaman" id="konten">
