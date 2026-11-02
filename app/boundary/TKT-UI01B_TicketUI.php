<?php
// Daftar tiket mahasiswa
require __DIR__ . '/layout/kepala.php';
$hitung = ['Menunggu Verifikasi' => 0, 'Dicarikan' => 0, 'Selesai' => 0];
foreach ($daftarTiket as $t) {
    if ($t['status'] === 'Menunggu Verifikasi') { $hitung['Menunggu Verifikasi']++; }
    if (in_array($t['status'], ['Tervalidasi', 'Menunggu Respons'], true)) { $hitung['Dicarikan']++; }
    if ($t['status'] === 'Selesai') { $hitung['Selesai']++; }
}
$labelMahasiswa = fn ($s) => $s === 'Menunggu Respons' ? badge('Tervalidasi', 'Dicarikan Relawan') : badge($s);
$cariRiwayat = function (array $tiket, string $status) {
    foreach ($tiket['riwayat'] as $r) {
        if ($r['status'] === $status) { return $r; }
    }
    return null;
};
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Tiket Saya</span>
</nav>
<div class="judul-halaman">
  <div>
    <h1>Tiket Saya</h1>
    <p>Pantau status tiket pendampingan dan batalkan tiket yang tidak lagi diperlukan.</p>
  </div>
  <a class="tombol" href="<?= url('tiket/ajukan') ?>"><?= ikon('plus') ?>Ajukan Tiket</a>
</div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<div class="statistik">
  <div class="stat"><span>Menunggu Verifikasi</span><strong><?= $hitung['Menunggu Verifikasi'] ?></strong><small>tiket</small></div>
  <div class="stat stat--biru"><span>Dicarikan Relawan</span><strong><?= $hitung['Dicarikan'] ?></strong><small>tiket</small></div>
  <div class="stat stat--hijau"><span>Selesai</span><strong><?= $hitung['Selesai'] ?></strong><small>tiket</small></div>
</div>

<div class="grid grid--tiket">
  <section class="kartu kartu--tanpa-padding" aria-labelledby="judul-daftar">
    <div class="kartu__kepala">
      <span class="ikon-tile"><?= ikon('ticket') ?></span>
      <div><h2 id="judul-daftar">Daftar Tiket</h2><p>Pilih tiket untuk melihat detail dan tahapan prosesnya.</p></div>
    </div>
    <div class="tabel-wrap">
      <table class="tabel">
        <thead><tr><th>ID</th><th>Layanan</th><th>Jadwal</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($daftarTiket as $t): ?>
          <tr data-href="<?= e(url('tiket', ['id' => $t['idTiket']])) ?>"
              class="<?= $detail && $detail['idTiket'] === $t['idTiket'] ? 'dipilih' : '' ?>">
            <td><?= e($t['idTiket']) ?></td>
            <td><?= e(layananSingkat($t['jenisLayanan'])) ?></td>
            <td class="nowrap"><span class="tebal"><?= e(tglIndo($t['jadwalKelas'], 'hariPendek')) ?></span>
              <small><?= e(rentangJam($t['waktuMulai'], $t['waktuSelesai'])) ?><?= $t['idSeri'] ? ' · berulang' : '' ?></small></td>
            <td><?= e(ruangSingkat($t['lokasi'])) ?></td>
            <td><?= $labelMahasiswa($t['status']) ?></td>
            <td><a class="tautan" href="<?= url('tiket', ['id' => $t['idTiket']]) ?>">Detail<?= ikon('chevron-right') ?></a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$daftarTiket): ?>
          <tr><td colspan="6" class="tabel-kosong">Belum ada tiket. Ajukan tiket pertamamu.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="kartu" aria-label="Detail tiket">
    <?php if (!$detail): ?>
      <p class="kosong"><?= ikon('ticket') ?><br>Pilih tiket untuk melihat detail.</p>
    <?php else:
      $s = $detail['status'];
      $verif = $cariRiwayat($detail, 'Tervalidasi');
      $tolak = $cariRiwayat($detail, 'Ditolak');
      $alok = $cariRiwayat($detail, 'Teralokasi');
      $sudahAlok = in_array($s, ['Teralokasi', 'Dilaksanakan', 'Selesai', 'Bermasalah'], true);
      $sudahJalan = in_array($s, ['Dilaksanakan', 'Selesai', 'Bermasalah'], true);
    ?>
      <div class="kartu__kepala">
        <span class="ikon-tile ikon-tile--kuning"><?= ikon('file-text') ?></span>
        <div><h2>Detail <?= e($detail['idTiket']) ?></h2>
          <p><?= e(layananSingkat($detail['jenisLayanan'])) ?> · <?= e(str_replace(' · ', ', ', $detail['lokasi'])) ?></p></div>
      </div>
      <div class="tag-baris" style="align-items:center">
        <?= $labelMahasiswa($s) ?>
        <span style="font-size:13px"><?= e(tglIndo($detail['jadwalKelas'], 'hari')) ?> · <?= e(rentangJam($detail['waktuMulai'], $detail['waktuSelesai'])) ?></span>
      </div>
      <ol class="timeline" aria-label="Tahapan proses tiket">
        <li class="selesai"><span class="titik"><?= ikon('check') ?></span><div><strong>Tiket diajukan</strong>
          <small><?= e(tglIndo($detail['createdAt'], 'lengkap')) ?><?= $detail['dibuatOleh'] === 'Admin' ? ' · oleh Admin FILKOM' : '' ?></small></div></li>
        <?php if ($tolak): ?>
          <li class="gagal"><span class="titik"><?= ikon('x') ?></span><div><strong>Ditolak Admin FILKOM</strong>
            <small><?= e(tglIndo($tolak['waktu'], 'lengkap')) ?> · <?= e($tolak['catatan'] ?? '') ?></small></div></li>
        <?php else: ?>
          <li class="<?= $verif ? 'selesai' : ($s === 'Menunggu Verifikasi' ? 'aktif' : 'menunggu') ?>"><span class="titik"><?= $verif ? ikon('check') : '' ?></span>
            <div><strong>Diverifikasi Admin FILKOM</strong>
              <small><?= $verif ? e(tglIndo($verif['waktu'], 'lengkap')) . ' · sesuai KRS' : 'Menunggu pemeriksaan data KRS' ?></small></div></li>
          <li class="<?= $sudahAlok ? 'selesai' : (in_array($s, ['Tervalidasi', 'Menunggu Respons'], true) ? 'aktif' : 'menunggu') ?>">
            <span class="titik"><?= $sudahAlok ? ikon('check') : '' ?></span>
            <div><strong>Relawan ditugaskan</strong>
              <small><?= $sudahAlok ? e($detail['namaRelawan'] ?? '-') . ' · Teralokasi' : 'Sedang dicarikan relawan yang sesuai' ?></small></div></li>
          <li class="<?= $sudahJalan ? 'selesai' : ($s === 'Teralokasi' ? 'aktif' : 'menunggu') ?>"><span class="titik"><?= $sudahJalan ? ikon('check') : '' ?></span>
            <div><strong>Menunggu pelaksanaan</strong>
              <small><?= e(tglIndo($detail['jadwalKelas'], 'lengkap')) ?></small></div></li>
          <?php if ($s === 'Bermasalah'): ?>
            <li class="gagal"><span class="titik"><?= ikon('alert') ?></span><div><strong>Kendala dilaporkan</strong>
              <small>Sedang ditindaklanjuti Admin FILKOM</small></div></li>
          <?php elseif ($s === 'Dibatalkan'): ?>
            <li class="gagal"><span class="titik"><?= ikon('minus') ?></span><div><strong>Tiket dibatalkan</strong>
              <small><?= e(tglIndo($cariRiwayat($detail, 'Dibatalkan')['waktu'] ?? null, 'lengkap')) ?></small></div></li>
          <?php else: ?>
            <li class="<?= $detail['konfirmasiMahasiswa'] ? 'selesai' : ($s === 'Selesai' ? 'aktif' : 'menunggu') ?>">
              <span class="titik"><?= $detail['konfirmasiMahasiswa'] ? ikon('check') : '' ?></span>
              <div><strong>Selesai &amp; konfirmasi kehadiran</strong>
                <small><?= $detail['konfirmasiMahasiswa'] ? 'Kehadiran relawan dikonfirmasi'
                    : ($s === 'Selesai' ? 'Menunggu konfirmasimu' : 'Setelah sesi berlangsung') ?></small></div></li>
          <?php endif; ?>
        <?php endif; ?>
      </ol>

      <?php if (in_array($s, TicketDB::STATUS_DAPAT_DIBATALKAN, true)):
        $batas = date('Y-m-d H:i:s', strtotime($detail['jadwalKelas']) - $batasBatalJam * 3600); ?>
        <div class="alert alert--kuning alert--tanpa-garis"><?= ikon('clock') ?>
          <div><?= $statusBatas === 'Boleh Batal'
              ? 'Pembatalan tersedia hingga ' . e(tglIndo($batas, 'hari')) . ' pukul ' . e(jam($batas)) . ' (' . (int) $batasBatalJam . ' jam sebelum sesi).'
              : 'Batas pembatalan telah lewat (' . e(tglIndo($batas, 'lengkap')) . '). Hubungi Admin FILKOM bila perlu membatalkan.' ?></div></div>
        <form method="post" action="<?= url('tiket/batal') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="idTiket" value="<?= e($detail['idTiket']) ?>">
          <input type="hidden" name="konfirmasi" value="1">
          <button type="submit" class="tombol tombol--bahaya tombol--penuh" <?= $statusBatas === 'Boleh Batal' ? '' : 'disabled' ?>
                  data-konfirmasi="Batalkan tiket <?= e($detail['idTiket']) ?>?"
                  data-pesan="Tiket akan berstatus Dibatalkan. Relawan yang sudah ditugaskan akan diberi informasi."
                  data-ya="Ya, batalkan"><?= ikon('x-circle') ?>Batalkan Tiket</button>
        </form>
      <?php elseif ($s === 'Selesai' && !$detail['konfirmasiMahasiswa']): ?>
        <a class="tombol tombol--penuh" href="<?= url('konfirmasi', ['id' => $detail['idTiket']]) ?>"><?= ikon('check-circle') ?>Konfirmasi Pendampingan</a>
      <?php elseif ($s === 'Ditolak' && $tolak): ?>
        <div class="alert alert--error"><?= ikon('x-circle') ?><div><strong>Tiket ditolak</strong><?= e($tolak['catatan'] ?? '') ?></div></div>
      <?php endif; ?>
      <?php if (in_array($s, ['Dilaksanakan', 'Selesai'], true)): ?>
        <p style="margin-top:14px;text-align:center"><a class="tautan tautan--bahaya" href="<?= url('kendala/lapor', ['id' => $detail['idTiket']]) ?>">
          <?= ikon('alert') ?>Laporkan kendala pendampingan</a></p>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
