<?php
// Pendaftaran relawan
require __DIR__ . '/layout/kepala.php';
$status = $pendaftaran['statusPendaftaran'] ?? 'Belum Dikirim';
$terkunci = $status === 'Menunggu Validasi';
$keahlianTerpilih = $isian['keahlian'] ?? ($pendaftaran['keahlianList'] ?? []);
$tambahanLama = $isian['tambahan'] ?? [];
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Pendaftaran Relawan</span>
</nav>
<div class="judul-halaman">
  <div>
    <h1>Pendaftaran Relawan Pendamping</h1>
    <p>Lengkapi keahlian dan unggah dokumen pendukung untuk ditinjau oleh Admin FILKOM.</p>
  </div>
  <?= badge($status, $status === 'Menunggu Validasi' ? 'Menunggu Validasi' : null) ?>
</div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<?php if ($status === 'Ditolak'): ?>
  <div class="alert alert--error"><?= ikon('x-circle') ?><div>
    <strong>Pendaftaran sebelumnya ditolak</strong>Alasan Admin FILKOM: <?= e($pendaftaran['alasanPenolakan']) ?>
    Perbaiki data lalu kirim ulang pendaftaran.</div></div>
<?php endif; ?>

<?php if ($terkunci): ?>
  <section class="kartu" style="max-width:900px">
    <div class="kartu__kepala"><span class="ikon-tile ikon-tile--kuning"><?= ikon('clock') ?></span>
      <div><h2>Pendaftaran <?= e(VolunteerDB::idPendaftaran($pendaftaran['idRelawan'])) ?> sedang ditinjau</h2>
        <p>Dikirim <?= e(tglIndo($pendaftaran['tanggalDaftar'], 'lengkap')) ?>. Menu relawan aktif setelah divalidasi Admin FILKOM.</p></div></div>
    <p class="label-seksi">Keahlian diajukan</p>
    <div class="tag-baris">
      <?php foreach ($pendaftaran['keahlianList'] as $k): ?><span class="tag"><?= e(daftarLayanan()[$k] ?? $k) ?></span><?php endforeach; ?>
      <?php foreach ($pendaftaran['dataRelawanData']['keahlianTambahan'] as $kt): ?><span class="tag tag--kuning"><?= e($kt['nama']) ?></span><?php endforeach; ?>
    </div>
    <p class="label-seksi">Dokumen bukti</p>
    <div class="daftar-berkas">
      <?php foreach (array_merge($pendaftaran['dataRelawanData']['dokumen'], array_map(fn ($t) => ['file' => $t['file'], 'nama' => $t['namaAsli'], 'ukuran' => $t['ukuran']],
          $pendaftaran['dataRelawanData']['keahlianTambahan'])) as $d): ?>
        <div class="berkas"><span class="berkas__ikon"><?= ikon('file-text') ?></span>
          <div><div class="berkas__nama"><?= e($d['nama']) ?></div><small><?= e(strtoupper(pathinfo($d['nama'], PATHINFO_EXTENSION))) ?> · <?= e(ukuranFile((int) $d['ukuran'])) ?></small></div>
          <div class="berkas__aksi"><a class="tautan" target="_blank" href="<?= url('relawan/dokumen', ['id' => $pendaftaran['idRelawan'], 'file' => $d['file']]) ?>"><?= ikon('eye') ?>Lihat</a></div></div>
      <?php endforeach; ?>
    </div>
  </section>
<?php else: ?>
<form method="post" action="<?= url('relawan/daftar/kirim') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="grid grid--pendaftaran">
    <section class="kartu" aria-labelledby="judul-form">
      <div class="kartu__kepala">
        <span class="ikon-tile ikon-tile--kuning"><?= ikon('user-plus') ?></span>
        <div><h2 id="judul-form">Formulir Pendaftaran</h2>
          <p>Kolom bertanda * wajib diisi. Data akun diambil otomatis dari akun UB.</p></div>
      </div>
      <div class="baris-field">
        <div class="field">
          <label for="nama">Nama pemohon</label>
          <div class="input-ikon"><?= ikon('user') ?><input class="input" id="nama" value="<?= e($pengguna['nama']) ?>" readonly><?= ikon('lock', 'ikon-kanan') ?></div>
        </div>
        <div class="field">
          <label for="email">Email UB</label>
          <div class="input-ikon"><?= ikon('mail') ?><input class="input" id="email" value="<?= e($pengguna['emailUB']) ?>" readonly><?= ikon('lock', 'ikon-kanan') ?></div>
        </div>
      </div>
      <fieldset>
        <legend class="label">Keahlian pendampingan * <small>Pilih satu atau lebih · keahlian lain di panel kanan</small></legend>
        <div class="opsi-baris" style="margin-top:8px">
          <?php foreach ($formRelawan['opsiKeahlian'] as $kode => $label): ?>
            <label class="opsi"><input type="checkbox" name="keahlian[]" value="<?= e($kode) ?>"
              <?= in_array($kode, $keahlianTerpilih, true) ? 'checked' : '' ?>><?= e($label) ?></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <div class="field">
        <span class="label">Dokumen bukti keahlian * <small>Sertifikat atau surat keterangan pelatihan</small></span>
        <div class="baris-field">
          <div class="dropzone" data-dropzone data-daftar="daftar-dokumen" data-jumlah="jumlah-dokumen" style="position:relative">
            <span class="dropzone__ikon"><?= ikon('upload') ?></span>
            <strong>Seret &amp; lepas file di sini</strong>
            <span><?= e($formRelawan['format']) ?> · maks. <?= (int) $formRelawan['batasMB'] ?> MB per file</span>
            <label class="tombol tombol--garis tombol--kecil"><?= ikon('file-plus') ?>Pilih File
              <input type="file" name="dokumen[]" multiple accept=".pdf,.jpg,.jpeg,.png"></label>
          </div>
          <div>
            <p class="label-seksi" style="text-transform:none;letter-spacing:.2px">File terpilih (<span id="jumlah-dokumen">0</span>)</p>
            <div class="daftar-berkas" id="daftar-dokumen"></div>
          </div>
        </div>
      </div>
      <div class="aksi">
        <span class="info-baris aksi__info"><?= ikon('info') ?>Kirim Pendaftaran mengirim seluruh data, termasuk keahlian tambahan dan buktinya, untuk ditinjau Admin FILKOM.</span>
        <a class="tombol tombol--garis" href="<?= url('beranda') ?>">Batal</a>
        <button type="submit" class="tombol">Kirim Pendaftaran</button>
      </div>
    </section>

    <section class="kartu" aria-labelledby="judul-tambahan">
      <div class="kartu__kepala">
        <span class="ikon-tile ikon-tile--kuning"><?= ikon('plus') ?></span>
        <div><h2 id="judul-tambahan">Keahlian Tambahan</h2>
          <p>Opsional. Isi keahlian di luar daftar dan lampirkan dokumen bukti untuk setiap keahlian.</p></div>
      </div>
      <div id="daftar-tambahan">
        <?php foreach ($tambahanLama as $i => $t): ?>
          <?php $nomor = $i; $namaLama = $t['nama']; require __DIR__ . '/layout/entri_tambahan.php'; ?>
        <?php endforeach; ?>
      </div>
      <button type="button" class="tombol tombol--garis tombol--penuh" data-tambah-keahlian><?= ikon('plus') ?>Tambah Keahlian Lain</button>
    </section>
  </div>
</form>
<template id="templat-tambahan"><?php $nomor = '__I__'; $namaLama = ''; require __DIR__ . '/layout/entri_tambahan.php'; ?></template>
<?php endif; ?>
<?php require __DIR__ . '/layout/kaki.php'; ?>
