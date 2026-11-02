<?php
// Unggah bukti kegiatan
require __DIR__ . '/layout/kepala.php';
$status = $statusPelaksanaan['statusPelaksanaan'];
$bisaUnggah = $statusPelaksanaan['formBukti'];
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <a href="<?= url('penugasan') ?>">Penugasan</a><?= ikon('chevron-right') ?>
  <span aria-current="page">Selesaikan Tiket</span>
</nav>
<div class="judul-halaman"><div>
  <h1>Selesaikan Tiket Pendampingan</h1>
  <p>Periksa bukti kegiatan, lalu tandai tiket sebagai selesai agar durasi pengabdian tercatat.</p>
</div></div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<div class="grid" style="grid-template-columns: 480px 1fr; margin-bottom:24px">
  <section class="kartu" aria-labelledby="judul-ringkasan">
    <div class="kartu__kepala">
      <span class="ikon-tile"><?= ikon('ticket') ?></span>
      <div><h2 id="judul-ringkasan">Ringkasan Tiket</h2><p>Detail layanan pendampingan</p></div>
    </div>
    <div class="tag-baris" style="align-items:center"><?= badge($status) ?><span class="kartu-tugas__id"><?= e($tiket['idTiket']) ?></span></div>
    <div class="detail-list">
      <div class="detail-item"><?= ikon('users') ?><div><small>Layanan</small><strong><?= e(layananSingkat($tiket['jenisLayanan'])) ?></strong></div></div>
      <div class="detail-item"><?= ikon('calendar') ?><div><small>Jadwal</small>
        <strong><?= e(tglIndo($tiket['jadwalKelas'], 'hari')) ?> · <?= e(jam($tiket['waktuMulai'])) ?> – <?= e(jam($tiket['waktuSelesai'])) ?></strong></div></div>
      <div class="detail-item"><?= ikon('map-pin') ?><div><small>Lokasi</small><strong><?= e($tiket['lokasi']) ?></strong></div></div>
      <div class="detail-item"><?= ikon('user') ?><div><small>Mahasiswa</small><strong><?= e($tiket['namaMahasiswa']) ?></strong></div></div>
    </div>
  </section>

  <section class="kartu" aria-labelledby="judul-unggah">
    <div class="kartu__kepala">
      <span class="ikon-tile ikon-tile--kuning"><?= ikon('image') ?></span>
      <div><h2 id="judul-unggah">Unggah Bukti Kegiatan</h2><p>Pilih foto dari perangkat atau ambil foto langsung.</p></div>
    </div>
    <?php if ($bisaUnggah): ?>
      <form method="post" action="<?= url('bukti/unggah') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="idTiket" value="<?= e($tiket['idTiket']) ?>">
        <div class="dropzone" data-dropzone data-daftar="daftar-foto" style="position:relative;padding:24px;margin-bottom:16px">
          <span class="dropzone__ikon"><?= ikon('upload') ?></span>
          <strong><?= $tiket['buktiFoto'] ? 'Ganti foto bukti' : 'Seret &amp; lepas foto di sini' ?></strong>
          <span>JPG atau PNG · maks. 5 MB per file</span>
          <label class="tombol tombol--garis tombol--kecil"><?= ikon('file-plus') ?>Pilih File
            <input type="file" id="fileFoto" name="fileFoto" accept="image/jpeg,image/png" capture="environment"></label>
        </div>
        <div class="daftar-berkas" id="daftar-foto" style="margin-bottom:16px"></div>
        <?php if ($tiket['buktiFoto']): ?>
          <div class="berkas" style="margin-bottom:16px"><span class="berkas__ikon"><?= ikon('image') ?></span>
            <div><div class="berkas__nama">bukti_<?= e($tiket['idTiket']) ?>.<?= e(pathinfo($tiket['buktiFoto'], PATHINFO_EXTENSION)) ?></div>
              <small>Tersimpan pada tiket</small></div>
            <div class="berkas__aksi">
              <a class="tautan" target="_blank" href="<?= url('bukti/lihat', ['id' => $tiket['idTiket']]) ?>"><?= ikon('eye') ?>Lihat</a>
              <button type="submit" class="tautan tautan--bahaya" formaction="<?= url('bukti/hapus') ?>" formnovalidate><?= ikon('trash') ?>Hapus</button>
            </div></div>
        <?php endif; ?>
        <div class="aksi">
          <a class="tombol tombol--garis" href="<?= url('penugasan', ['id' => $tiket['idTiket']]) ?>">Batal</a>
          <button type="submit" class="tombol" data-wajib-berkas="fileFoto"><?= $tiket['buktiFoto'] ? 'Unggah Ulang' : 'Unggah' ?></button>
        </div>
      </form>
    <?php elseif ($tiket['buktiFoto']): ?>
      <div class="pratinjau-foto"><img src="<?= url('bukti/lihat', ['id' => $tiket['idTiket']]) ?>" alt="Foto bukti kegiatan <?= e($tiket['idTiket']) ?>"></div>
    <?php else: ?>
      <div class="alert alert--kuning"><?= ikon('clock') ?><div><strong>Belum dapat mengunggah</strong>
        Fitur unggah aktif setelah sesi dimulai (status Dilaksanakan). Status saat ini: <?= e($status) ?>.</div></div>
    <?php endif; ?>
  </section>
</div>

<section class="kartu">
  <form method="post" action="<?= url('bukti/selesai') ?>" class="aksi">
    <?= csrf_field() ?>
    <input type="hidden" name="idTiket" value="<?= e($tiket['idTiket']) ?>">
    <span class="info-baris aksi__info"><?= ikon('history') ?>
      <?php if ($status === 'Selesai' || $status === 'Bermasalah'): ?>
        Tiket sudah selesai. Durasi <?= angka($durasi) ?> jam tercatat ke akumulasi jam pengabdianmu.
      <?php else: ?>
        Durasi <?= angka($durasi) ?> jam akan dicatat ke akumulasi jam pengabdianmu setelah tiket selesai, dengan status Menunggu Verifikasi hingga divalidasi Admin FILKOM.
      <?php endif; ?></span>
    <a class="tombol tombol--garis" href="<?= url('penugasan') ?>">Batal</a>
    <button type="submit" class="tombol" <?= $kesiapan['siap'] ? '' : 'disabled' ?>
            data-konfirmasi="Selesaikan tiket <?= e($tiket['idTiket']) ?>?"
            data-pesan="Status tiket menjadi Selesai dan durasi <?= angka($durasi) ?> jam dicatat." data-ya="Ya, selesaikan"><?= ikon('check') ?>Selesaikan Tiket</button>
  </form>
</section>
<?php require __DIR__ . '/layout/kaki.php'; ?>
