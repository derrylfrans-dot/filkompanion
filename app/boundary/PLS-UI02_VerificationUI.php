<?php
// Konfirmasi kehadiran
require __DIR__ . '/layout/kepala.php';
$bisaKonfirmasi = $tiket['status'] === 'Selesai' && !$tiket['konfirmasiMahasiswa'];
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <a href="<?= url('tiket', ['id' => $tiket['idTiket']]) ?>">Tiket Saya</a><?= ikon('chevron-right') ?>
  <span aria-current="page">Konfirmasi Pendampingan</span>
</nav>
<div class="judul-halaman"><div>
  <h1>Konfirmasi Pendampingan</h1>
  <p>Periksa bukti kegiatan dari relawan, lalu konfirmasi kehadiran atau laporkan kendala bila ada ketidaksesuaian.</p>
</div></div>

<div class="grid" style="grid-template-columns: 480px 1fr">
  <section class="kartu" aria-labelledby="judul-ringkasan">
    <div class="kartu__kepala">
      <span class="ikon-tile"><?= ikon('ticket') ?></span>
      <div><h2 id="judul-ringkasan">Ringkasan Tiket</h2><p>Detail layanan pendampingan</p></div>
    </div>
    <div class="tag-baris" style="align-items:center"><?= badge($tiket['status']) ?><span class="kartu-tugas__id"><?= e($tiket['idTiket']) ?></span></div>
    <div class="detail-list">
      <div class="detail-item"><?= ikon('users') ?><div><small>Layanan</small><strong><?= e(layananSingkat($tiket['jenisLayanan'])) ?></strong></div></div>
      <div class="detail-item"><?= ikon('calendar') ?><div><small>Jadwal</small>
        <strong><?= e(tglIndo($tiket['jadwalKelas'], 'hari')) ?> · <?= e(jam($tiket['waktuMulai'])) ?> – <?= e(jam($tiket['waktuSelesai'])) ?></strong></div></div>
      <div class="detail-item"><?= ikon('map-pin') ?><div><small>Lokasi</small><strong><?= e($tiket['lokasi']) ?></strong></div></div>
      <div class="detail-item"><?= ikon('user') ?><div><small>Relawan Pendamping</small><strong><?= e($tiket['namaRelawan'] ?? '-') ?></strong></div></div>
      <div class="detail-item"><?= ikon('check-circle') ?><div><small>Status konfirmasi</small>
        <strong><?= $tiket['konfirmasiMahasiswa'] ? 'Kehadiran sudah dikonfirmasi' : 'Belum dikonfirmasi' ?></strong></div></div>
    </div>
  </section>

  <section class="kartu" aria-labelledby="judul-bukti">
    <div class="kartu__kepala">
      <span class="ikon-tile ikon-tile--kuning"><?= ikon('image') ?></span>
      <div><h2 id="judul-bukti">Bukti Kegiatan</h2>
        <p><?= $waktuBukti ? 'Diunggah pada ' . e(tglIndo($waktuBukti, 'lengkap')) : 'Bukti belum diunggah relawan' ?></p></div>
    </div>
    <div class="pratinjau-foto">
      <?php if ($tiket['buktiFoto']): ?>
        <a href="<?= url('bukti/lihat', ['id' => $tiket['idTiket']]) ?>" target="_blank">
          <img src="<?= url('bukti/lihat', ['id' => $tiket['idTiket']]) ?>" alt="Foto bukti kegiatan <?= e($tiket['idTiket']) ?>"></a>
      <?php else: ?>
        <span class="info-baris"><?= ikon('image') ?>Foto bukti belum tersedia.</span>
      <?php endif; ?>
    </div>
    <?php require __DIR__ . '/layout/flash.php'; ?>
    <?php if ($bisaKonfirmasi): ?>
      <div class="alert"><?= ikon('alert-circle') ?><div>Pilih <b>Laporkan Kendala</b> jika pendampingan tidak sesuai, misalnya relawan
        tidak hadir atau terlambat. Laporan diteruskan ke Admin FILKOM untuk ditindaklanjuti.</div></div>
      <form method="post" action="<?= url('konfirmasi/simpan') ?>" class="aksi">
        <?= csrf_field() ?>
        <input type="hidden" name="idTiket" value="<?= e($tiket['idTiket']) ?>">
        <button type="submit" name="pilihan" value="Laporkan Kendala" class="tombol tombol--bahaya"><?= ikon('alert') ?>Laporkan Kendala</button>
        <button type="submit" name="pilihan" value="Konfirmasi" class="tombol"><?= ikon('check') ?>Konfirmasi Kehadiran</button>
      </form>
    <?php elseif ($tiket['konfirmasiMahasiswa']): ?>
      <?php if (empty($flash)): ?>
        <div class="alert alert--sukses"><?= ikon('check-circle') ?><div><strong>Kehadiran sudah dikonfirmasi</strong>Terima kasih atas konfirmasimu.</div></div>
      <?php endif; ?>
    <?php else: ?>
      <div class="alert alert--kuning"><?= ikon('clock') ?><div><strong>Belum dapat dikonfirmasi</strong>
        Konfirmasi tersedia setelah relawan menyelesaikan tiket. Status saat ini: <?= e($tiket['status']) ?>.</div></div>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
