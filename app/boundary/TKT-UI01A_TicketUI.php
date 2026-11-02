<?php
// Form pengajuan tiket
require __DIR__ . '/layout/kepala.php';
$profilLengkap = !empty($profil['kategoriDisabilitas']) && !empty($profil['kebutuhanPendampingan']);
$v = fn ($k, $d = '') => e($isian[$k] ?? $d);
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Ajukan Tiket</span>
</nav>
<div class="judul-halaman"><div>
  <h1>Ajukan Tiket Pendampingan</h1>
  <p>Isi jenis layanan, lokasi, dan jadwal kelas. Tiket diverifikasi Admin FILKOM sebelum dicarikan relawan.</p>
</div></div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<div class="grid grid--form-samping">
  <section class="kartu" aria-labelledby="judul-pengajuan">
    <div class="kartu__kepala">
      <span class="ikon-tile ikon-tile--kuning"><?= ikon('ticket') ?></span>
      <div><h2 id="judul-pengajuan">Detail Pengajuan</h2><p>Kolom bertanda * wajib diisi.</p></div>
    </div>
    <?php if (!$profilLengkap): ?>
      <div class="alert alert--kuning"><?= ikon('alert-circle') ?><div><strong>Profil kebutuhan belum lengkap</strong>
        Lengkapi <a href="<?= url('profil') ?>">Profil Kebutuhan</a> terlebih dahulu agar tiket dapat dicocokkan dengan relawan.</div></div>
    <?php endif; ?>
    <form method="post" action="<?= url('tiket/kirim') ?>">
      <?= csrf_field() ?>
      <div class="baris-field">
        <div class="field">
          <label for="jenisLayanan">Jenis layanan *</label>
          <select class="select" id="jenisLayanan" name="jenisLayanan" required>
            <?php foreach (daftarLayanan() as $label): ?>
              <option value="<?= e($label) ?>" <?= ($isian['jenisLayanan'] ?? $profil['kebutuhanPendampingan'] ?? '') === $label ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="lokasi">Lokasi / ruang *</label>
          <div class="input-ikon"><?= ikon('map-pin') ?>
            <input class="input" id="lokasi" name="lokasi" required maxlength="100" value="<?= $v('lokasi') ?>"
                   placeholder="Contoh: Gedung F FILKOM · Ruang F2.3"></div>
        </div>
      </div>
      <div class="baris-field baris-field--3">
        <div class="field">
          <label for="tanggal">Tanggal *</label>
          <input class="input" type="date" id="tanggal" name="tanggal" required min="<?= date('Y-m-d') ?>" value="<?= $v('tanggal') ?>">
        </div>
        <div class="field">
          <label for="waktuMulai">Waktu mulai *</label>
          <input class="input" type="time" id="waktuMulai" name="waktuMulai" required step="600" value="<?= $v('waktuMulai', '08:00') ?>">
        </div>
        <div class="field">
          <label for="waktuSelesai">Waktu selesai *</label>
          <input class="input" type="time" id="waktuSelesai" name="waktuSelesai" required step="600" value="<?= $v('waktuSelesai', '10:00') ?>">
        </div>
      </div>
      <div class="panel-abu">
        <label class="cek"><input type="checkbox" id="opsiBerulang" name="opsiBerulang" value="1"
          <?= !empty($isian['opsiBerulang']) ? 'checked' : '' ?>>Ajukan berulang sesuai jadwal kuliah</label>
        <div id="panel-berulang" style="margin-top:14px" hidden>
          <div class="baris-field">
            <div class="field">
              <label for="periodeMulai">Periode mulai *</label>
              <input class="input" type="date" id="periodeMulai" name="periodeMulai" value="<?= $v('periodeMulai') ?>">
            </div>
            <div class="field">
              <label for="periodeAkhir">Periode akhir *</label>
              <input class="input" type="date" id="periodeAkhir" name="periodeAkhir" value="<?= $v('periodeAkhir') ?>">
            </div>
          </div>
          <p class="info-baris teks-biru"><?= ikon('repeat') ?><span id="ringkasan-berulang" aria-live="polite"></span></p>
        </div>
      </div>
      <div class="field">
        <label for="catatan">Catatan kebutuhan (opsional)</label>
        <input class="input" id="catatan" name="catatan" maxlength="255" value="<?= $v('catatan') ?>"
               placeholder="Contoh: Mohon relawan duduk di sisi kanan depan agar mudah terlihat.">
      </div>
      <div class="aksi">
        <span class="info-baris aksi__info"><?= ikon('info') ?>Setelah dikirim, tiket berstatus Menunggu Verifikasi.</span>
        <a class="tombol tombol--garis" href="<?= url('tiket') ?>">Batal</a>
        <button type="submit" class="tombol" <?= $profilLengkap ? '' : 'disabled' ?>><?= ikon('send') ?>Kirim Pengajuan</button>
      </div>
    </form>
  </section>

  <div class="tumpuk">
    <section class="kartu" aria-labelledby="judul-profil">
      <div class="kartu__kepala">
        <span class="ikon-tile"><?= ikon('user') ?></span>
        <div><h2 id="judul-profil">Profil Kebutuhan</h2><p>Dipakai untuk pencocokan relawan.</p></div>
      </div>
      <div class="detail-list">
        <div class="detail-item"><?= ikon('user') ?><div><small>Kategori disabilitas</small>
          <strong><?= e($profil['kategoriDisabilitas'] ?? 'Belum diisi') ?></strong></div></div>
        <div class="detail-item"><?= ikon('users') ?><div><small>Kebutuhan utama</small>
          <strong><?= e($profil['kebutuhanPendampingan'] ?? 'Belum diisi') ?></strong></div></div>
        <a class="tautan" href="<?= url('profil') ?>"><u>Ubah di Profil Kebutuhan</u><?= ikon('arrow-right') ?></a>
      </div>
    </section>
    <section class="kartu kartu--kuning" aria-labelledby="judul-batas">
      <h2 id="judul-batas" class="info-baris" style="font-size:16px;color:var(--teks);font-weight:600;margin-bottom:8px">
        <?= ikon('clock') ?>Batas pembatalan</h2>
      <p style="font-size:13px">Tiket dapat dibatalkan paling lambat <?= (int) $batasBatalJam ?> jam sebelum sesi dimulai melalui menu Tiket Saya.</p>
    </section>
  </div>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
