<?php
// Halaman beranda
require __DIR__ . '/layout/kepala.php';
$peran = $pengguna['rolePengguna'];
?>
<div class="judul-halaman">
  <div>
    <h1>Halo, <?= e(explode(' ', $pengguna['nama'])[0]) ?></h1>
    <p><?= $peran === 'Admin FILKOM' ? 'Ringkasan layanan pendampingan yang perlu ditindaklanjuti hari ini.'
        : 'Temani Aktivitas Kampusmu. Berikut ringkasan aktivitasmu di FILKOMpanion.' ?></p>
  </div>
  <span class="meta-kanan"><?= ikon('calendar') ?><?= e(tglIndo(date('Y-m-d'), 'hari')) ?></span>
</div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<?php if ($peran === 'Mahasiswa Disabilitas'): ?>
  <?php if (empty($profil['kategoriDisabilitas'])): ?>
    <div class="alert alert--kuning"><?= ikon('alert-circle') ?><div><strong>Lengkapi profil kebutuhan</strong>
      Profil kebutuhan dipakai untuk pencocokan relawan. <a href="<?= url('profil') ?>">Lengkapi sekarang →</a></div></div>
  <?php endif; ?>
  <div class="statistik">
    <div class="stat"><span>Menunggu Verifikasi</span><strong><?= (int) ($ringkasan['Menunggu Verifikasi'] ?? 0) ?></strong><small>tiket</small></div>
    <div class="stat stat--biru"><span>Dicarikan / Teralokasi</span><strong><?= (int) (($ringkasan['Tervalidasi'] ?? 0)
        + ($ringkasan['Menunggu Respons'] ?? 0) + ($ringkasan['Teralokasi'] ?? 0)) ?></strong><small>tiket</small></div>
    <div class="stat stat--hijau"><span>Selesai</span><strong><?= (int) ($ringkasan['Selesai'] ?? 0) ?></strong><small>tiket</small></div>
  </div>
  <div class="grid grid--dua">
    <section class="kartu">
      <div class="kartu__kepala"><span class="ikon-tile"><?= ikon('calendar') ?></span>
        <div><h2>Jadwal pendampingan terdekat</h2><p>Tiket aktif yang akan datang.</p></div></div>
      <?php foreach (array_slice($tiketMendatang, 0, 4) as $t): ?>
        <a class="kartu-tugas" href="<?= url('tiket', ['id' => $t['idTiket']]) ?>">
          <div class="kartu-tugas__atas"><span class="kartu-tugas__id"><?= e($t['idTiket']) ?></span>
            <span class="tag"><?= e(layananSingkat($t['jenisLayanan'])) ?></span><?= badge($t['status']) ?></div>
          <div class="kartu-tugas__info"><span><?= ikon('calendar') ?><?= e(tglIndo($t['jadwalKelas'], 'hari')) ?> · <?= e(rentangJam($t['waktuMulai'], $t['waktuSelesai'])) ?></span></div>
        </a>
      <?php endforeach; ?>
      <?php if (!$tiketMendatang): ?><p class="kosong">Belum ada jadwal. <a href="<?= url('tiket/ajukan') ?>">Ajukan tiket</a></p><?php endif; ?>
    </section>
    <section class="kartu">
      <div class="kartu__kepala"><span class="ikon-tile ikon-tile--kuning"><?= ikon('check-circle') ?></span>
        <div><h2>Perlu konfirmasi kehadiran</h2><p>Pendampingan selesai yang menunggu konfirmasimu.</p></div></div>
      <?php foreach ($perluKonfirmasi as $t): ?>
        <a class="kartu-tugas" href="<?= url('konfirmasi', ['id' => $t['idTiket']]) ?>">
          <div class="kartu-tugas__atas"><span class="kartu-tugas__id"><?= e($t['idTiket']) ?></span>
            <span class="tag"><?= e(layananSingkat($t['jenisLayanan'])) ?></span><?= badge('Selesai') ?></div>
          <div class="kartu-tugas__bawah">Relawan: <?= e($t['namaRelawan']) ?> · <?= e(tglIndo($t['jadwalKelas'])) ?></div>
        </a>
      <?php endforeach; ?>
      <?php if (!$perluKonfirmasi): ?><p class="kosong">Tidak ada yang perlu dikonfirmasi.</p><?php endif; ?>
    </section>
  </div>

<?php elseif ($peran === 'Relawan Pendamping'): ?>
  <?php if (!$relawan || $relawan['statusPendaftaran'] !== 'Aktif'): ?>
    <section class="kartu" style="max-width:760px">
      <div class="kartu__kepala"><span class="ikon-tile ikon-tile--kuning"><?= ikon('user-plus') ?></span>
        <div><h2>Status pendaftaran relawan</h2><p>Menu relawan aktif setelah pendaftaran divalidasi Admin FILKOM.</p></div>
        <span class="kanan"><?= badge($relawan['statusPendaftaran'] ?? 'Belum Dikirim') ?></span></div>
      <?php if ($relawan && $relawan['statusPendaftaran'] === 'Ditolak'): ?>
        <div class="alert alert--error"><?= ikon('x-circle') ?><div><strong>Alasan penolakan</strong><?= e($relawan['alasanPenolakan']) ?></div></div>
      <?php endif; ?>
      <a class="tombol" href="<?= url('relawan/daftar') ?>"><?= ikon('user-plus') ?>Buka Pendaftaran Relawan</a>
    </section>
  <?php else: ?>
    <?php $menungguRespons = array_filter($penugasan, fn ($t) => $t['status'] === 'Menunggu Respons'); ?>
    <?php if ($menungguRespons): ?>
      <div class="alert"><?= ikon('info') ?><div><strong><?= count($menungguRespons) ?> penugasan baru menunggu responsmu</strong>
        <a href="<?= url('penugasan') ?>">Tinjau penugasan →</a></div></div>
    <?php endif; ?>
    <div class="statistik">
      <div class="stat stat--biru"><span>Penugasan aktif</span><strong><?= count($penugasan) - count($menungguRespons) ?></strong><small>tiket</small></div>
      <div class="stat stat--hijau"><span>Jam terverifikasi</span><strong><?= angka($jam['terverifikasi']) ?></strong><small>jam</small></div>
      <div class="stat"><span>Menunggu verifikasi</span><strong><?= angka($jam['menunggu'] + $jam['perluDitinjau']) ?></strong><small>jam</small></div>
    </div>
    <div class="grid grid--dua">
      <a class="kartu kartu-tugas" href="<?= url('relawan/profil') ?>"><div class="kartu__kepala" style="margin:0"><span class="ikon-tile"><?= ikon('calendar') ?></span>
        <div><h2>Profil & Ketersediaan</h2><p>Perbarui keahlian dan blok waktu kosong.</p></div></div></a>
      <a class="kartu kartu-tugas" href="<?= url('riwayat') ?>"><div class="kartu__kepala" style="margin:0"><span class="ikon-tile ikon-tile--kuning"><?= ikon('history') ?></span>
        <div><h2>Riwayat & Jam</h2><p>Pantau akumulasi jam pengabdian untuk SKM.</p></div></div></a>
    </div>
  <?php endif; ?>

<?php else: ?>
  <div class="statistik statistik--4">
    <a class="stat" href="<?= url('admin/relawan') ?>" style="text-decoration:none;color:inherit"><span>Pendaftar menunggu validasi</span>
      <strong><?= (int) $pendaftaran['Menunggu Validasi'] ?></strong><small>pendaftar</small></a>
    <a class="stat stat--biru" href="<?= url('admin/tiket') ?>" style="text-decoration:none;color:inherit"><span>Tiket menunggu verifikasi</span>
      <strong><?= (int) ($ringkasan['Menunggu Verifikasi'] ?? 0) ?></strong><small>tiket</small></a>
    <a class="stat stat--merah" href="<?= url('admin/kendala') ?>" style="text-decoration:none;color:inherit"><span>Laporan kendala terbuka</span>
      <strong><?= (int) $laporanTerbuka ?></strong><small>laporan</small></a>
    <a class="stat stat--hijau" href="<?= url('admin/jam') ?>" style="text-decoration:none;color:inherit"><span>Relawan menunggu validasi jam</span>
      <strong><?= (int) $jamMenunggu ?></strong><small>relawan</small></a>
  </div>
  <section class="kartu">
    <div class="kartu__kepala"><span class="ikon-tile"><?= ikon('chart') ?></span>
      <div><h2>Status seluruh tiket</h2><p>Jumlah tiket berdasarkan status saat ini.</p></div></div>
    <div class="tag-baris">
      <?php foreach ($ringkasan as $status => $n): ?>
        <?= badge($status, $status . ': ' . $n) ?>
      <?php endforeach; ?>
    </div>
    <div class="aksi" style="justify-content:flex-start">
      <a class="tombol" href="<?= url('admin/tiket') ?>"><?= ikon('ticket') ?>Verifikasi Tiket</a>
      <a class="tombol tombol--garis" href="<?= url('admin/laporan') ?>"><?= ikon('chart') ?>Laporan Rekap</a>
    </div>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/layout/kaki.php'; ?>
