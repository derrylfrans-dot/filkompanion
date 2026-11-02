<?php
// Form tiket manual
require __DIR__ . '/layout/kepala.php';
$v = fn ($k, $d = '') => e($isian[$k] ?? $d);
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Tiket Manual</span>
</nav>
<div class="judul-halaman"><div>
  <h1>Buat Tiket Manual</h1>
  <p>Buat tiket atas nama mahasiswa yang mengalami kendala teknis. Tiket tetap melalui verifikasi seperti tiket biasa.</p>
</div></div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<div class="grid grid--form-samping">
  <section class="kartu" aria-labelledby="judul-mhs">
    <div class="kartu__kepala">
      <span class="ikon-tile"><?= ikon('user') ?></span>
      <div><h2 id="judul-mhs">Data Mahasiswa</h2><p>Cari berdasarkan ID mahasiswa atau email UB.</p></div>
    </div>
    <form method="get" action="index.php" role="search" class="field">
      <input type="hidden" name="r" value="admin/tiket-manual">
      <label for="cari">ID mahasiswa / email UB *</label>
      <div class="input-ikon"><?= ikon('search') ?>
        <input class="input" type="search" id="cari" name="cari" placeholder="Contoh: MHS027 atau yoga.saputra@ub.ac.id"
               value="<?= e($cari !== '' ? $cari : ($mahasiswa ? $mahasiswa['userId'] . ' · ' . $mahasiswa['emailUB'] : '')) ?>"></div>
    </form>
    <?php if ($cari !== '' && count($hasilCari) > 1): ?>
      <div class="daftar-berkas" style="margin-bottom:16px">
        <?php foreach ($hasilCari as $h): ?>
          <a class="berkas" style="text-decoration:none;color:inherit" href="<?= url('admin/tiket-manual', ['idMahasiswa' => $h['userId']]) ?>">
            <span class="avatar"><?= e(inisial($h['nama'])) ?></span>
            <div><div class="berkas__nama"><?= e($h['nama']) ?></div><small><?= e($h['userId']) ?> · <?= e($h['emailUB']) ?></small></div>
            <span class="berkas__aksi tautan">Pilih<?= ikon('chevron-right') ?></span></a>
        <?php endforeach; ?>
      </div>
    <?php elseif ($cari !== '' && !$hasilCari): ?>
      <div class="alert alert--error"><?= ikon('alert-circle') ?><div><strong>Mahasiswa tidak ditemukan</strong>Periksa kembali ID atau email UB.</div></div>
    <?php endif; ?>

    <?php if ($mahasiswa): ?>
      <div class="panel-abu profil-mini" style="justify-content:space-between">
        <div class="profil-mini"><span class="avatar"><?= e(inisial($mahasiswa['nama'])) ?></span>
          <div><strong><?= e($mahasiswa['nama']) ?></strong>
            <span><?= e($mahasiswa['userId']) ?> · Kebutuhan utama: <?= e($mahasiswa['kebutuhanPendampingan'] ?? 'belum diisi') ?></span></div></div>
        <?= badge('Sesuai') ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= url('admin/tiket-manual/kirim') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="idMahasiswa" value="<?= e($mahasiswa['userId'] ?? '') ?>">
      <p class="label-seksi">Detail layanan · kolom bertanda * wajib diisi</p>
      <div class="baris-field">
        <div class="field">
          <label for="jenisLayanan">Jenis layanan *</label>
          <select class="select" id="jenisLayanan" name="jenisLayanan" required>
            <?php foreach (daftarLayanan() as $label): ?>
              <option value="<?= e($label) ?>" <?= ($isian['jenisLayanan'] ?? $mahasiswa['kebutuhanPendampingan'] ?? '') === $label ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="lokasi">Lokasi / ruang *</label>
          <div class="input-ikon"><?= ikon('map-pin') ?>
            <input class="input" id="lokasi" name="lokasi" required maxlength="100" value="<?= $v('lokasi') ?>"
                   placeholder="Contoh: Gedung G FILKOM · Ruang G1.4"></div>
        </div>
      </div>
      <div class="baris-field baris-field--3">
        <div class="field"><label for="tanggal">Tanggal *</label>
          <input class="input" type="date" id="tanggal" name="tanggal" required min="<?= date('Y-m-d') ?>" value="<?= $v('tanggal') ?>"></div>
        <div class="field"><label for="waktuMulai">Waktu mulai *</label>
          <input class="input" type="time" id="waktuMulai" name="waktuMulai" required step="600" value="<?= $v('waktuMulai', '10:00') ?>"></div>
        <div class="field"><label for="waktuSelesai">Waktu selesai *</label>
          <input class="input" type="time" id="waktuSelesai" name="waktuSelesai" required step="600" value="<?= $v('waktuSelesai', '11:40') ?>"></div>
      </div>
      <div class="field">
        <label for="catatan">Catatan admin (opsional)</label>
        <input class="input" id="catatan" name="catatan" maxlength="200" value="<?= $v('catatan') ?>"
               placeholder="Contoh: Permohonan diterima via email karena aplikasi tidak dapat diakses.">
      </div>
      <div class="aksi">
        <span class="info-baris aksi__info"><?= ikon('info') ?>Tiket tersimpan dengan status Menunggu Verifikasi.</span>
        <a class="tombol tombol--garis" href="<?= url('admin/tiket-manual') ?>">Batal</a>
        <button type="submit" class="tombol" <?= $mahasiswa ? '' : 'disabled' ?>><?= ikon('check') ?>Simpan Tiket Manual</button>
      </div>
    </form>
  </section>

  <section class="kartu" aria-labelledby="judul-alur">
    <div class="kartu__kepala">
      <span class="ikon-tile"><?= ikon('info') ?></span>
      <div><h2 id="judul-alur">Alur Tiket Manual</h2></div>
    </div>
    <ol class="langkah">
      <li class="<?= $mahasiswa ? 'selesai' : 'aktif' ?>"><span class="titik"><?= $mahasiswa ? ikon('check') : '' ?></span>
        <div><strong>Isi data mahasiswa &amp; layanan</strong><small>Oleh Admin FILKOM</small></div></li>
      <li class="<?= $mahasiswa ? 'aktif' : 'nanti' ?>"><span class="titik"></span>
        <div><strong>Simpan tiket manual</strong><small>Status: Menunggu Verifikasi</small></div></li>
      <li class="nanti"><span class="titik"></span><div><strong>Verifikasi KRS</strong><small>Melalui menu Verifikasi Tiket</small></div></li>
      <li class="nanti"><span class="titik"></span><div><strong>Pencarian relawan</strong><small>Proses sama dengan tiket biasa</small></div></li>
    </ol>
  </section>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
