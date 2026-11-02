<?php
// Riwayat jam pengabdian
require __DIR__ . '/layout/kepala.php';
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Riwayat &amp; Jam</span>
</nav>
<div class="judul-halaman">
  <div>
    <h1>Riwayat dan Total Jam Pengabdian</h1>
    <p>Pantau riwayat penugasan dan perkembangan akumulasi jam pengabdianmu.</p>
  </div>
  <form method="get" action="index.php" class="field" style="margin:0;width:310px">
    <input type="hidden" name="r" value="riwayat">
    <label for="periode">Filter periode</label>
    <div class="input-ikon"><?= ikon('calendar') ?>
      <select class="select input" id="periode" name="periode" data-kirim-otomatis>
        <?php foreach ($daftarPeriode as $p): ?>
          <option value="<?= e($p) ?>" <?= $p === $periode ? 'selected' : '' ?>><?= e($p) ?></option>
        <?php endforeach; ?>
      </select></div>
    <noscript><button class="tombol tombol--kecil" type="submit">Terapkan</button></noscript>
  </form>
</div>

<div class="statistik">
  <div class="stat stat--biru"><span>Jam tercatat</span><strong><?= angka($totalJam['total']) ?></strong><small>jam</small></div>
  <div class="stat stat--hijau"><span>Terverifikasi</span><strong><?= angka($totalJam['terverifikasi']) ?></strong><small>jam</small></div>
  <div class="stat"><span>Menunggu verifikasi</span><strong><?= angka($totalJam['menunggu'] + $totalJam['perluDitinjau']) ?></strong><small>jam</small></div>
</div>

<div class="alert"><?= ikon('info') ?><div><strong>Informasi SKM</strong>
  Hanya jam berstatus Terverifikasi yang disahkan untuk proses konversi SKM.</div></div>

<section class="kartu kartu--tanpa-padding" aria-labelledby="judul-riwayat">
  <div class="kartu__kepala">
    <span class="ikon-tile"><?= ikon('history') ?></span>
    <div><h2 id="judul-riwayat">Riwayat Penugasan</h2>
      <p>Menampilkan <?= count($riwayatTiket) ?> dari <?= (int) $jumlahSemua ?> tiket pada periode terpilih.</p></div>
  </div>
  <div class="tabel-wrap">
    <table class="tabel">
      <thead><tr><th>ID Tiket</th><th>Tanggal</th><th>Layanan</th><th>Durasi</th><th>Status tiket</th><th>Status jam</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($riwayatTiket as $t): ?>
        <tr>
          <td class="tebal"><?= e($t['idTiket']) ?></td>
          <td><?= e(tglIndo($t['jadwalKelas'])) ?></td>
          <td><?= e($t['jenisLayanan']) ?></td>
          <td><?= $t['durasiTercatat'] !== null ? angka($t['durasiTercatat']) . ' jam' : '-' ?></td>
          <td><?= badge($t['status']) ?></td>
          <td><?= $t['statusJam'] ? badge($t['statusJam']) : '<span class="bantuan-field">Belum tercatat</span>' ?></td>
          <td><a class="tautan" href="<?= url('bukti', ['id' => $t['idTiket']]) ?>">Detail<?= ikon('chevron-right') ?></a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$riwayatTiket): ?>
        <tr><td colspan="7" class="tabel-kosong">Belum ada riwayat penugasan pada periode ini.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require __DIR__ . '/layout/kaki.php'; ?>
