<?php
// Validasi pendaftaran relawan
require __DIR__ . '/layout/kepala.php';
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Validasi Relawan</span>
</nav>
<div class="judul-halaman"><div>
  <h1>Validasi Pendaftaran Relawan</h1>
  <p>Periksa kelengkapan data dan bukti keahlian sebelum mengaktifkan relawan.</p>
</div></div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<div class="grid grid--daftar-detail">
  <div class="tumpuk">
    <div class="statistik" style="margin:0">
      <div class="stat"><span>Menunggu validasi</span><strong><?= (int) $ringkasan['Menunggu Validasi'] ?></strong><small>pendaftar</small></div>
      <div class="stat stat--biru"><span>Relawan aktif</span><strong><?= (int) $ringkasan['Aktif'] ?></strong><small>relawan</small></div>
      <div class="stat stat--merah"><span>Ditolak</span><strong><?= (int) $ringkasan['Ditolak'] ?></strong><small>pendaftaran</small></div>
    </div>
    <section class="kartu kartu--tanpa-padding" aria-labelledby="judul-daftar">
      <div class="kartu__kepala">
        <span class="ikon-tile"><?= ikon('users') ?></span>
        <div><h2 id="judul-daftar">Daftar Pendaftaran</h2><p>Pilih satu baris untuk meninjau detail.</p></div>
        <form class="kanan" method="get" action="index.php" role="search">
          <input type="hidden" name="r" value="admin/relawan">
          <div class="input-ikon" style="width:260px"><?= ikon('search') ?>
            <input class="input" type="search" name="cari" value="<?= e($cari) ?>" placeholder="Cari nama, email, atau ID" aria-label="Cari pendaftaran"></div>
        </form>
      </div>
      <div class="tabel-wrap">
        <table class="tabel">
          <thead><tr><th>ID</th><th>Nama pendaftar</th><th>Keahlian</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($daftar as $d): ?>
            <tr data-href="<?= e(url('admin/relawan', ['id' => $d['idRelawan'], 'cari' => $cari])) ?>"
                class="<?= $detail && $detail['idRelawan'] === $d['idRelawan'] ? 'dipilih' : '' ?>">
              <td><?= e(VolunteerDB::idPendaftaran($d['idRelawan'])) ?></td>
              <td><span class="tebal"><?= e($d['nama']) ?></span><small><?= e($d['emailUB']) ?></small></td>
              <td><?= e(implode(', ', $d['keahlianList'])) ?></td>
              <td><?= badge($d['statusPendaftaran']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$daftar): ?><tr><td colspan="4" class="tabel-kosong">Tidak ada pendaftaran yang cocok.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <section class="kartu" aria-label="Detail dan dokumen">
    <?php if (!$detail): ?>
      <p class="kosong"><?= ikon('users') ?><br>Belum ada pendaftaran untuk ditinjau.</p>
    <?php else: $data = $detail['dataRelawanData']; ?>
      <div class="kartu__kepala">
        <span class="avatar" aria-hidden="true"><?= e(inisial($detail['nama'])) ?></span>
        <div><h2><?= e($detail['nama']) ?></h2>
          <p><?= e(VolunteerDB::idPendaftaran($detail['idRelawan'])) ?> · <?= e($detail['emailUB']) ?></p></div>
        <span class="kanan"><?= badge($detail['statusPendaftaran']) ?></span>
      </div>
      <p class="kotak-info" style="margin-bottom:20px"><?= ikon('calendar') ?>Dikirim <?= e(tglIndo($detail['tanggalDaftar'], 'lengkap')) ?>
        · Status kelengkapan: <?= count($data['dokumen']) > 0 ? 'lengkap' : 'belum lengkap' ?></p>
      <p class="label-seksi">Keahlian diajukan</p>
      <div class="tag-baris">
        <?php foreach ($detail['keahlianList'] as $i => $k): ?>
          <span class="tag <?= $i % 2 ? 'tag--kuning' : '' ?>"><?= e(daftarLayanan()[$k] ?? $k) ?></span>
        <?php endforeach; ?>
        <?php foreach ($data['keahlianTambahan'] as $kt): ?>
          <span class="tag tag--kuning"><?= e($kt['nama']) ?> (tambahan)</span>
        <?php endforeach; ?>
      </div>
      <?php $semuaDok = array_merge($data['dokumen'], array_map(fn ($t) => ['file' => $t['file'], 'nama' => $t['namaAsli'], 'ukuran' => $t['ukuran']], $data['keahlianTambahan'])); ?>
      <p class="label-seksi">Dokumen bukti (<?= count($semuaDok) ?>)</p>
      <div class="daftar-berkas" style="margin-bottom:20px">
        <?php foreach ($semuaDok as $d): ?>
          <div class="berkas"><span class="berkas__ikon"><?= ikon('file-text') ?></span>
            <div><div class="berkas__nama"><?= e($d['nama']) ?></div>
              <small><?= e(strtoupper(pathinfo($d['nama'], PATHINFO_EXTENSION))) ?> · <?= e(ukuranFile((int) $d['ukuran'])) ?></small></div>
            <div class="berkas__aksi"><a class="tautan" target="_blank"
              href="<?= url('relawan/dokumen', ['id' => $detail['idRelawan'], 'file' => $d['file']]) ?>"><?= ikon('eye') ?>Lihat</a></div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($detail['statusPendaftaran'] === 'Menunggu Validasi'): ?>
        <form method="post" action="<?= url('admin/relawan/keputusan') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="idPendaftaran" value="<?= e($detail['idRelawan']) ?>">
          <div class="field">
            <label for="alasan">Catatan Admin / alasan penolakan</label>
            <textarea class="textarea" id="alasan" name="alasan" maxlength="255" data-maks="255" data-hitung="hitung-alasan"
                      placeholder="Tuliskan alasan bila menolak pendaftaran..."></textarea>
            <span class="bantuan-field"><span>Wajib diisi jika menolak. Dikirim ke pendaftar.</span><span id="hitung-alasan">0/255</span></span>
          </div>
          <div class="aksi aksi--antara">
            <button type="submit" name="hasil" value="Tolak" class="tombol tombol--bahaya" data-konfirmasi="Tolak pendaftaran ini?"
                    data-pesan="Alasan penolakan akan dikirim kepada pendaftar." data-ya="Ya, tolak" data-wajib="alasan"
                    data-pesan-wajib="Alasan penolakan wajib diisi sebelum menolak."><?= ikon('x-circle') ?>Tolak</button>
            <button type="submit" name="hasil" value="Setujui" class="tombol" data-konfirmasi="Setujui pendaftaran ini?"
                    data-pesan="Status pendaftar menjadi Relawan Aktif dan notifikasi hasil validasi dikirim." data-ya="Ya, setujui"><?= ikon('check-circle') ?>Setujui Pendaftaran</button>
          </div>
        </form>
      <?php else: ?>
        <div class="alert <?= $detail['statusPendaftaran'] === 'Aktif' ? 'alert--sukses' : 'alert--error' ?>">
          <?= ikon($detail['statusPendaftaran'] === 'Aktif' ? 'check-circle' : 'x-circle') ?>
          <div><strong>Sudah diputuskan: <?= e($detail['statusPendaftaran']) ?></strong>
            <?= e(tglIndo($detail['tanggalValidasi'], 'lengkap')) ?><?= $detail['alasanPenolakan'] ? ' · Alasan: ' . e($detail['alasanPenolakan']) : '' ?></div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
