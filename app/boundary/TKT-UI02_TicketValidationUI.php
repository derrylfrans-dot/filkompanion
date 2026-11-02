<?php
// Verifikasi tiket
require __DIR__ . '/layout/kepala.php';
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Verifikasi Tiket</span>
</nav>
<div class="judul-halaman"><div>
  <h1>Verifikasi Tiket dan KRS</h1>
  <p>Bandingkan jadwal pengajuan dengan KRS mahasiswa sebelum meneruskan tiket ke pencarian relawan.</p>
</div></div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<div class="grid grid--daftar-detail">
  <section class="kartu kartu--tanpa-padding" aria-labelledby="judul-daftar">
    <div class="kartu__kepala">
      <span class="ikon-tile"><?= ikon('ticket') ?></span>
      <div><h2 id="judul-daftar">Tiket Menunggu Verifikasi</h2><p><?= count($daftar) ?> tiket perlu ditinjau.</p></div>
      <form class="kanan" method="get" action="index.php" role="search">
        <input type="hidden" name="r" value="admin/tiket">
        <div class="input-ikon" style="width:320px"><?= ikon('search') ?>
          <input class="input" type="search" name="cari" value="<?= e($cari) ?>" placeholder="Cari ID tiket atau ID mahasiswa" aria-label="Cari tiket"></div>
      </form>
    </div>
    <div class="tabel-wrap">
      <table class="tabel">
        <thead><tr><th>ID</th><th>Mahasiswa</th><th>Layanan</th><th>Jadwal</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($daftar as $t): ?>
          <tr data-href="<?= e(url('admin/tiket', ['id' => $t['idTiket'], 'cari' => $cari])) ?>"
              class="<?= $detail && $detail['idTiket'] === $t['idTiket'] ? 'dipilih' : '' ?>">
            <td><?= e($t['idTiket']) ?></td>
            <td><span class="tebal"><?= e($t['namaMahasiswa']) ?></span><small><?= e($t['idMahasiswa']) ?></small></td>
            <td><?= e(layananSingkat($t['jenisLayanan'])) ?></td>
            <td class="nowrap"><span class="tebal"><?= e(tglIndo($t['jadwalKelas'], 'hariPendek')) ?></span>
              <small><?= e(rentangJam($t['waktuMulai'], $t['waktuSelesai'])) ?><?= $t['jumlahSeri'] > 1 ? ' · berulang ×' . (int) $t['jumlahSeri'] : '' ?></small></td>
            <td><?= badge($t['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$daftar): ?><tr><td colspan="5" class="tabel-kosong">Tidak ada tiket yang menunggu verifikasi.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="kartu" aria-label="Detail tiket">
    <?php if (!$detail): ?>
      <p class="kosong"><?= ikon('check-circle') ?><br>Semua tiket sudah diverifikasi.</p>
    <?php else:
      $sesuai = $perbandingan && $perbandingan['hasil'] === 'SESUAI';
      $kelas = $perbandingan['kelas'] ?? null; ?>
      <div class="kartu__kepala">
        <span class="ikon-tile ikon-tile--kuning"><?= ikon('file-text') ?></span>
        <div><h2>Detail <?= e($detail['idTiket']) ?></h2>
          <p><?= e($detail['namaMahasiswa']) ?> · <?= e($detail['idMahasiswa']) ?> · <?= e(layananSingkat($detail['jenisLayanan'])) ?></p></div>
      </div>
      <div class="detail-list" style="margin-bottom:20px">
        <div class="detail-item"><?= ikon('map-pin') ?><div><small>Lokasi</small><strong><?= e($detail['lokasi']) ?></strong></div></div>
        <div class="detail-item"><?= ikon($detail['jumlahSeri'] > 1 ? 'repeat' : 'calendar') ?><div><small>Jadwal diajukan</small>
          <strong><?php if ($detail['jumlahSeri'] > 1): ?>
            Setiap <?= e(tglIndo($detail['jadwalKelas'], 'namaHari')) ?> <?= e(rentangJam($detail['waktuMulai'], $detail['waktuSelesai'])) ?>
            · <?= e(tglIndo($detail['jadwalKelas'], 'tanpaTahun')) ?> – <?= e(tglIndo($detail['akhirSeri'])) ?> (<?= (int) $detail['jumlahSeri'] ?> tiket)
          <?php else: ?>
            <?= e(tglIndo($detail['jadwalKelas'], 'hari')) ?> · <?= e(rentangJam($detail['waktuMulai'], $detail['waktuSelesai'])) ?>
          <?php endif; ?></strong></div></div>
        <?php if ($detail['catatanTiket'] || $detail['catatanKebutuhan']): ?>
          <div class="detail-item"><?= ikon('message') ?><div><small>Catatan</small>
            <strong style="font-size:14px;font-weight:500"><?= e($detail['catatanTiket'] ?: $detail['catatanKebutuhan']) ?></strong></div></div>
        <?php endif; ?>
      </div>
      <div class="krs">
        <div class="krs__kepala">Perbandingan KRS
          <?= $perbandingan === null ? badge('Perlu Ditinjau', 'KRS belum tersedia') : badge($sesuai ? 'Sesuai' : 'Tidak Sesuai') ?></div>
        <div class="krs__isi">
          <div><small>Jadwal tiket</small>
            <strong><?= e(tglIndo($detail['jadwalKelas'], 'namaHari')) ?>, <?= e(rentangJam($detail['waktuMulai'], $detail['waktuSelesai'])) ?></strong>
            <span>Ruang <?= e(ruangSingkat($detail['lokasi'])) ?></span></div>
          <div><small>Jadwal KRS</small>
            <?php if ($kelas): ?>
              <strong><?= e($kelas['hari']) ?>, <?= e(str_replace(':', '.', $kelas['mulai'])) ?>–<?= e(str_replace(':', '.', $kelas['selesai'])) ?></strong>
              <span>Ruang <?= e(ruangSingkat($kelas['ruang'])) ?> · Kelas <?= e($kelas['kelas']) ?> · <?= e($kelas['mataKuliah']) ?></span>
            <?php else: ?>
              <strong>Tidak ada kelas</strong><span>pada hari tersebut</span>
            <?php endif; ?></div>
        </div>
      </div>
      <form method="post" action="<?= url('admin/tiket/keputusan') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="idTiket" value="<?= e($detail['idTiket']) ?>">
        <div class="field">
          <label for="alasan">Alasan penolakan</label>
          <textarea class="textarea" id="alasan" name="alasan" maxlength="255" data-maks="255" data-hitung="hitung-alasan"
                    placeholder="Wajib diisi bila tiket ditolak..." style="min-height:72px"></textarea>
          <span class="bantuan-field"><span>Dikirim kepada mahasiswa.</span><span id="hitung-alasan">0/255</span></span>
        </div>
        <div class="aksi aksi--antara">
          <button type="submit" name="hasil" value="Tolak" class="tombol tombol--bahaya"
                  data-konfirmasi="Tolak <?= $detail['jumlahSeri'] > 1 ? (int) $detail['jumlahSeri'] . ' tiket berulang' : 'tiket ' . e($detail['idTiket']) ?>?"
                  data-pesan="Status tiket menjadi Ditolak dan alasan dikirim kepada mahasiswa." data-ya="Ya, tolak"
                  data-wajib="alasan" data-pesan-wajib="Alasan penolakan wajib diisi."><?= ikon('x-circle') ?>Tolak</button>
          <button type="submit" name="hasil" value="Setujui" class="tombol" <?= $sesuai ? '' : 'disabled title="Jadwal tidak sesuai KRS"' ?>
                  data-konfirmasi="Setujui dan teruskan ke pencarian relawan?"
                  data-pesan="Tiket berstatus Tervalidasi lalu dicocokkan dengan keahlian dan ketersediaan relawan." data-ya="Ya, setujui"><?= ikon('check-circle') ?>Setujui &amp; Teruskan</button>
        </div>
      </form>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
