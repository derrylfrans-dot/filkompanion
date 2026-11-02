<?php
// Laporan rekapitulasi
require __DIR__ . '/layout/kepala.php';
$rekap = $hasil['rekapitulasi'] ?? null;
$unduh = fn ($f) => url('admin/laporan/unduh', ['format' => $f, 'awal' => $periode['awal'], 'akhir' => $periode['akhir']]);
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Laporan Rekap</span>
</nav>
<div class="judul-halaman"><div>
  <h1>Laporan Rekapitulasi Layanan</h1>
  <p>Rekap jumlah tiket, jam pengabdian terverifikasi, performa relawan, dan laporan kendala berdasarkan periode.</p>
</div></div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<section class="kartu" style="margin-bottom:24px">
  <form method="get" action="index.php" class="aksi" style="align-items:flex-end;justify-content:flex-start">
    <input type="hidden" name="r" value="admin/laporan">
    <div class="field" style="margin:0;width:220px"><label for="awal">Tanggal awal</label>
      <input class="input" type="date" id="awal" name="awal" required value="<?= e($periode['awal']) ?>"></div>
    <div class="field" style="margin:0;width:220px"><label for="akhir">Tanggal akhir</label>
      <input class="input" type="date" id="akhir" name="akhir" required value="<?= e($periode['akhir']) ?>"></div>
    <button type="submit" class="tombol"><?= ikon('filter') ?>Tampilkan</button>
    <span style="margin-left:auto;display:flex;gap:16px">
      <a class="tombol tombol--garis" href="<?= e($unduh('PDF')) ?>" <?= $rekap ? '' : 'aria-disabled="true" tabindex="-1"' ?>><?= ikon('download') ?>Unduh PDF</a>
      <a class="tombol tombol--garis" href="<?= e($unduh('XLSX')) ?>" <?= $rekap ? '' : 'aria-disabled="true" tabindex="-1"' ?>><?= ikon('download') ?>Unduh XLSX</a>
    </span>
  </form>
</section>

<div class="aksi aksi--antara" style="margin-bottom:16px">
  <h2 style="font-size:17px">Periode laporan: <?= e($periode['label']) ?></h2>
  <?php if ($rekap): ?><span class="meta-kanan">Diperbarui <?= e(tglIndo($rekap['dibuat'], 'lengkap')) ?></span><?php endif; ?>
</div>

<?php if (!$rekap): ?>
  <section class="kartu kosong">
    <?= ikon('chart') ?>
    <h2 style="font-size:18px;color:var(--teks)"><?= e($hasil['pesan']) ?></h2>
    <p>Pilih rentang tanggal lain yang memiliki tiket pendampingan.</p>
  </section>
<?php else:
  $maks = max(1, max(array_column($rekap['jumlahTiket']['perMinggu'], 'jumlah') ?: [1])); ?>
  <div class="statistik statistik--4">
    <div class="stat stat--biru"><span>Jumlah tiket</span><strong><?= (int) $rekap['jumlahTiket']['total'] ?></strong><small>tiket</small></div>
    <div class="stat stat--hijau"><span>Tiket selesai</span><strong><?= (int) $rekap['jumlahTiket']['selesai'] ?></strong><small>tiket</small></div>
    <div class="stat"><span>Jam terverifikasi</span><strong><?= angka($rekap['jamTerverifikasi']) ?></strong><small>jam</small></div>
    <div class="stat stat--merah"><span>Laporan kendala</span><strong><?= (int) $rekap['laporanKendala'] ?></strong><small>laporan</small></div>
  </div>
  <div class="grid grid--laporan">
    <section class="kartu" aria-labelledby="judul-grafik">
      <div class="kartu__kepala"><span class="ikon-tile"><?= ikon('chart') ?></span>
        <div><h2 id="judul-grafik">Perkembangan Tiket per Minggu</h2></div></div>
      <div class="grafik" role="img" aria-label="Jumlah tiket per minggu: <?= e(implode(', ', array_map(fn ($m) => $m['label'] . ' ' . $m['jumlah'], $rekap['jumlahTiket']['perMinggu']))) ?>">
        <?php foreach ($rekap['jumlahTiket']['perMinggu'] as $m): ?>
          <div class="grafik__kolom" title="<?= e($m['rentang']) ?>">
            <span class="grafik__nilai"><?= (int) $m['jumlah'] ?></span>
            <span class="grafik__batang" style="height:<?= round($m['jumlah'] / $maks * 150) ?>px"></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="grafik__label"><?php foreach ($rekap['jumlahTiket']['perMinggu'] as $m): ?><span><?= e($m['label']) ?></span><?php endforeach; ?></div>
    </section>
    <section class="kartu kartu--tanpa-padding" aria-labelledby="judul-performa">
      <div class="kartu__kepala"><span class="ikon-tile"><?= ikon('users') ?></span>
        <div><h2 id="judul-performa">Performa Relawan</h2></div></div>
      <div class="tabel-wrap">
        <table class="tabel">
          <thead><tr><th>Relawan</th><th>Tiket selesai</th><th>Jam terverifikasi</th><th>Laporan kendala</th></tr></thead>
          <tbody>
          <?php foreach ($rekap['dataPerforma'] as $p): ?>
            <tr><td class="tebal"><?= e($p['nama']) ?></td><td><?= (int) $p['tiketSelesai'] ?></td>
              <td><?= angka($p['jamTerverifikasi']) ?> jam</td><td><?= (int) $p['laporanKendala'] ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$rekap['dataPerforma']): ?><tr><td colspan="4" class="tabel-kosong">Belum ada performa relawan pada periode ini.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/layout/kaki.php'; ?>
