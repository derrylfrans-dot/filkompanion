<?php
// Validasi jam relawan
require __DIR__ . '/layout/kepala.php';
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Validasi Jam</span>
</nav>
<div class="judul-halaman">
  <div>
    <h1>Validasi Akumulasi Jam Pengabdian</h1>
    <p>Tinjau total jam relawan per periode, lalu sahkan atau tandai data yang perlu diperiksa kembali.</p>
  </div>
  <form method="get" action="index.php" class="field" style="margin:0;width:310px">
    <input type="hidden" name="r" value="admin/jam">
    <label for="periode">Filter periode</label>
    <div class="input-ikon"><?= ikon('calendar') ?>
      <select class="select input" id="periode" name="periode" data-kirim-otomatis>
        <?php foreach ($daftarPeriode as $p): ?>
          <option value="<?= e($p) ?>" <?= $p === $periode ? 'selected' : '' ?>><?= e($p) ?></option>
        <?php endforeach; ?>
      </select></div>
  </form>
</div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<div class="grid grid--daftar-detail">
  <section class="kartu kartu--tanpa-padding" aria-labelledby="judul-daftar">
    <div class="kartu__kepala">
      <span class="ikon-tile"><?= ikon('clock') ?></span>
      <div><h2 id="judul-daftar">Daftar Akumulasi Jam</h2><p><?= count($daftarJam) ?> relawan pada periode terpilih.</p></div>
    </div>
    <div class="tabel-wrap">
      <table class="tabel">
        <thead><tr><th>Relawan</th><th>Total jam</th><th>Tiket</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($daftarJam as $r): ?>
          <tr data-href="<?= e(url('admin/jam', ['periode' => $periode, 'id' => $r['idRelawan']])) ?>"
              class="<?= $detail && $detail['idRelawan'] === $r['idRelawan'] ? 'dipilih' : '' ?>">
            <td><span class="tebal"><?= e($r['nama']) ?></span><small><?= e($r['idRelawan']) ?></small></td>
            <td><?= angka($r['totalJam']) ?> jam</td>
            <td><?= (int) $r['jumlahTiket'] ?></td>
            <td><?= badge($r['status']) ?></td>
            <td><a class="tautan" href="<?= url('admin/jam', ['periode' => $periode, 'id' => $r['idRelawan']]) ?>">Pilih</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$daftarJam): ?><tr><td colspan="5" class="tabel-kosong">Belum ada jam pengabdian pada periode ini.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="kartu" aria-label="Detail relawan">
    <?php if (!$detail): ?>
      <p class="kosong"><?= ikon('users') ?><br>Pilih relawan untuk meninjau akumulasi jam.</p>
    <?php else: ?>
      <div class="kartu__kepala">
        <span class="ikon-tile ikon-tile--kuning"><?= ikon('user') ?></span>
        <div><h2>Detail Relawan</h2><p><?= e($detail['nama']) ?> · <?= e($detail['idRelawan']) ?></p></div>
      </div>
      <div class="baris-field" style="margin-bottom:20px">
        <div class="stat stat--kecil stat--biru"><span>Total jam</span><strong><?= angka($detail['totalJam']) ?></strong><small>jam</small></div>
        <div class="stat stat--kecil"><span>Tiket sumber</span><strong><?= (int) $detail['jumlahTiket'] ?></strong><small>tiket</small></div>
      </div>
      <div class="detail-list" style="margin-bottom:20px">
        <div class="detail-item"><?= ikon('check-circle') ?><div><small>Sudah terverifikasi sebelumnya</small>
          <strong><?= angka($detail['terverifikasi']) ?> jam</strong></div></div>
        <div class="detail-item"><?= ikon('clock') ?><div><small>Menunggu validasi</small>
          <strong><?= angka($detail['menunggu']) ?> jam</strong></div></div>
        <?php if ($detail['catatanPeninjauan'] && $detail['status'] === 'Perlu Ditinjau'): ?>
          <div class="detail-item"><?= ikon('message') ?><div><small>Catatan peninjauan</small>
            <strong style="font-size:14px;font-weight:500"><?= e($detail['catatanPeninjauan']) ?></strong></div></div>
        <?php endif; ?>
      </div>
      <?php if ($detail['laporanTerbuka'] > 0): ?>
        <div class="alert alert--error"><?= ikon('alert') ?><div><strong><?= (int) $detail['laporanTerbuka'] ?> laporan kendala masih terbuka</strong>
          Selesaikan peninjauan kendala di menu <a href="<?= url('admin/kendala') ?>">Laporan Kendala</a> sebelum validasi jam.</div></div>
      <?php else: ?>
        <div class="alert alert--sukses"><?= ikon('check-circle') ?><div>Tidak ada laporan kendala terbuka untuk relawan ini.</div></div>
      <?php endif; ?>
      <p class="info-baris" style="font-size:16px;color:var(--teks);font-weight:600;margin-bottom:16px">Status hasil <?= badge($detail['status']) ?></p>
      <?php if ($detail['status'] !== 'Terverifikasi'): ?>
        <form method="post" action="<?= url('admin/jam/validasi') ?>" class="aksi aksi--antara">
          <?= csrf_field() ?>
          <input type="hidden" name="idRelawan" value="<?= e($detail['idRelawan']) ?>">
          <input type="hidden" name="periode" value="<?= e($periode) ?>">
          <button type="submit" class="tombol tombol--garis" formaction="<?= url('admin/jam/tinjau') ?>"
                  data-konfirmasi="Tandai data perlu ditinjau?" data-catatan
                  data-pesan="Pengesahan jam ditahan sampai data diperiksa kembali." data-ya="Ya, tandai"><?= ikon('alert') ?>Tandai Perlu Ditinjau</button>
          <button type="submit" class="tombol" <?= $detail['laporanTerbuka'] > 0 ? 'disabled' : '' ?>
                  data-konfirmasi="Validasi <?= angka($detail['menunggu']) ?> jam milik <?= e($detail['nama']) ?>?"
                  data-pesan="Status jam berubah menjadi Terverifikasi dan siap untuk proses konversi SKM." data-ya="Ya, validasi"><?= ikon('shield') ?>Validasi Jam</button>
        </form>
      <?php else: ?>
        <div class="alert"><?= ikon('info') ?><div>Seluruh jam relawan ini pada periode terpilih sudah terverifikasi dan siap dikonversi menjadi poin SKM.</div></div>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
