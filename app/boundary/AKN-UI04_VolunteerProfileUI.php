<?php
// Profil relawan
require __DIR__ . '/layout/kepala.php';
$r = $dataRelawan;
$mingguan = $r['ketersediaanData']['mingguan'];
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Profil &amp; Ketersediaan</span>
</nav>
<div class="judul-halaman">
  <div>
    <h1>Profil dan Ketersediaan Relawan</h1>
    <p>Perbarui keahlian dan blok waktu kosong yang menjadi dasar pencocokan penugasan.</p>
  </div>
  <span class="meta-kanan"><?= ikon('clock') ?>Terakhir disimpan <?= e(tglIndo($r['updatedAt'] ?? $r['tanggalValidasi'], 'lengkap')) ?></span>
</div>

<form method="post" action="<?= url('relawan/profil/simpan') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="grid grid--profil" style="margin-bottom:24px">
    <section class="kartu" aria-label="Profil relawan" style="align-self:stretch">
      <div class="profil-mini" style="margin-bottom:18px">
        <span class="avatar avatar--besar" aria-hidden="true"><?= e(inisial($r['nama'])) ?></span>
        <div><strong><?= e($r['nama']) ?></strong><span><?= e($r['emailUB']) ?></span></div>
      </div>
      <div class="tag-baris" style="align-items:center">
        <?= badge('Relawan Aktif') ?><span class="kartu-tugas__id">ID <?= e($r['idRelawan']) ?></span>
      </div>
      <p class="bantuan-field">Divalidasi Admin FILKOM pada <?= e(tglIndo($r['tanggalValidasi'])) ?>.</p>
    </section>

    <section class="kartu" aria-labelledby="judul-keahlian">
      <div class="kartu__kepala">
        <span class="ikon-tile"><?= ikon('users') ?></span>
        <div><h2 id="judul-keahlian">Keahlian Pendampingan</h2>
          <p>Pilih dari daftar atau tambahkan keahlian lain beserta dokumen buktinya. Minimal satu keahlian.</p></div>
      </div>
      <div class="opsi-baris">
        <?php foreach (daftarLayanan() as $kode => $label): ?>
          <label class="opsi"><input type="checkbox" name="keahlian[]" value="<?= e($kode) ?>"
            <?= in_array($kode, $r['keahlianList'], true) ? 'checked' : '' ?>><?= e($label) ?></label>
        <?php endforeach; ?>
        <?php foreach ($r['dataRelawanData']['keahlianTambahan'] as $i => $kt): ?>
          <label class="opsi"><input type="checkbox" name="tambahanTetap[]" value="<?= (int) $i ?>" checked>
            <span><?= e($kt['nama']) ?> <span class="tag tag--kuning tag--kecil">Tambahan</span>
              <small><?= ikon('file-text') ?>Bukti:
                <a href="<?= url('relawan/dokumen', ['id' => $r['idRelawan'], 'file' => $kt['file']]) ?>" target="_blank"><?= e($kt['namaAsli']) ?></a></small></span>
          </label>
        <?php endforeach; ?>
        <button type="button" class="tombol tombol--garis" data-tambah-keahlian><?= ikon('plus') ?>Tambah Keahlian Lain</button>
      </div>
      <div id="daftar-tambahan" style="margin-top:16px"></div>
    </section>
  </div>

  <section class="kartu" aria-labelledby="judul-kalender">
    <div class="kartu__kepala">
      <span class="ikon-tile ikon-tile--kuning"><?= ikon('calendar') ?></span>
      <div><h2 id="judul-kalender">Kalender Ketersediaan Mingguan</h2>
        <p>Klik atau tekan Spasi pada blok untuk mengubah status.</p></div>
      <div class="kanan legenda" aria-hidden="true">Keterangan:
        <span class="blok"><input type="checkbox" checked disabled><span class="tidak"></span><span class="ya"><?= ikon('check') ?>Tersedia</span></span>
        <span class="blok"><input type="checkbox" disabled><span class="tidak"><?= ikon('minus') ?>Tidak tersedia</span><span class="ya"></span></span>
        <span class="ringkas-kuning" id="ringkasan-blok" aria-live="polite"></span>
      </div>
    </div>
    <table class="kalender" data-kalender>
      <caption class="sr-only">Blok waktu tersedia per hari</caption>
      <thead><tr><th scope="col">Waktu</th>
        <?php foreach (VolunteerDB::HARI as $h): ?><th scope="col"><?= e($h) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach (VolunteerDB::BLOK as $kode => [$mulai, $selesai]): ?>
        <tr>
          <td class="waktu"><?= ikon('clock') ?><?= e(str_replace(':', '.', $mulai) . '–' . str_replace(':', '.', $selesai)) ?></td>
          <?php foreach (VolunteerDB::HARI as $h): ?>
            <td>
              <label class="blok">
                <input type="checkbox" name="blok[<?= e($h) ?>][]" value="<?= e($kode) ?>"
                  <?= in_array($kode, $mingguan[$h] ?? [], true) ? 'checked' : '' ?>
                  aria-label="<?= e($h . ' ' . $mulai . '–' . $selesai) ?>">
                <span class="tidak"><?= ikon('minus') ?>Tidak tersedia</span>
                <span class="ya"><?= ikon('check') ?>Tersedia</span>
              </label>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="aksi" style="margin-top:16px">
      <?php $flashKelas = 'alert--ringkas aksi__info'; require __DIR__ . '/layout/flash.php'; ?>
      <button type="reset" class="tombol tombol--garis">Batal</button>
      <button type="submit" class="tombol"><?= ikon('check') ?>Simpan Perubahan</button>
    </div>
  </section>
</form>
<template id="templat-tambahan"><?php $nomor = '__I__'; $namaLama = ''; require __DIR__ . '/layout/entri_tambahan.php'; ?></template>
<?php require __DIR__ . '/layout/kaki.php'; ?>
