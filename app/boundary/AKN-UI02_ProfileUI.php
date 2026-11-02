<?php
// Profil kebutuhan mahasiswa
require __DIR__ . '/layout/kepala.php';
$catatan = (string) ($profil['catatanKebutuhan'] ?? '');
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Profil Kebutuhan</span>
</nav>
<div class="judul-halaman">
  <div>
    <h1>Profil Kebutuhan Disabilitas</h1>
    <p>Lengkapi kategori dan kebutuhan pendampingan agar layanan dan relawan sesuai kebutuhanmu.</p>
  </div>
  <span class="meta-kanan"><?= ikon('clock') ?>Terakhir diperbarui
    <?= $profil['updatedAt'] ? e(tglIndo($profil['updatedAt'], 'lengkap')) : 'belum pernah' ?></span>
</div>

<div class="grid grid--profil">
  <section class="kartu" aria-labelledby="judul-akun">
    <div class="kartu__kepala">
      <span class="ikon-tile"><?= ikon('user') ?></span>
      <div><h2 id="judul-akun">Informasi Akun</h2><p>Data diambil dari akun UB.</p></div>
    </div>
    <div class="field">
      <label for="nama">Nama mahasiswa</label>
      <div class="input-ikon"><?= ikon('user') ?>
        <input class="input" id="nama" value="<?= e($profil['nama']) ?>" readonly>
        <?= ikon('lock', 'ikon-kanan') ?></div>
    </div>
    <div class="field" style="margin-bottom:0">
      <label for="email">Email UB</label>
      <div class="input-ikon"><?= ikon('mail') ?>
        <input class="input" id="email" value="<?= e($profil['emailUB']) ?>" readonly aria-describedby="bantuan-identitas">
        <?= ikon('lock', 'ikon-kanan') ?></div>
      <span class="bantuan-field" id="bantuan-identitas">Perubahan identitas dilakukan melalui akun UB.</span>
    </div>
  </section>

  <section class="kartu" aria-labelledby="judul-kebutuhan">
    <div class="kartu__kepala">
      <span class="ikon-tile ikon-tile--kuning"><?= ikon('clipboard') ?></span>
      <div><h2 id="judul-kebutuhan">Kebutuhan Pendampingan</h2>
        <p>Kolom bertanda * wajib diisi. Tidak ada pertanyaan medis yang diminta.</p></div>
    </div>
    <form method="post" action="<?= url('profil/simpan') ?>">
      <?= csrf_field() ?>
      <fieldset>
        <legend class="label">Kategori disabilitas *</legend>
        <div class="opsi-grid" style="margin-top:8px">
          <?php foreach ($opsiKategori as $k): ?>
            <label class="opsi">
              <input type="radio" name="kategoriDisabilitas" value="<?= e($k) ?>" required
                <?= ($profil['kategoriDisabilitas'] ?? '') === $k ? 'checked' : '' ?>><?= e($k) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <div class="field">
        <label for="kebutuhanPendampingan">Kebutuhan utama *</label>
        <select class="select" id="kebutuhanPendampingan" name="kebutuhanPendampingan" required aria-describedby="bantuan-kebutuhan">
          <option value="">Pilih kebutuhan utama</option>
          <?php foreach ($opsiKebutuhan as $k): ?>
            <option value="<?= e($k) ?>" <?= ($profil['kebutuhanPendampingan'] ?? '') === $k ? 'selected' : '' ?>><?= e($k) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="bantuan-field" id="bantuan-kebutuhan">Pilihan: JBI, Note-taker, Pemandu Mobilitas, atau kebutuhan lain yang tersedia.</span>
      </div>
      <div class="field">
        <label for="catatanKebutuhan">Catatan kebutuhan (opsional)</label>
        <textarea class="textarea" id="catatanKebutuhan" name="catatanKebutuhan" maxlength="255"
                  data-maks="255" data-hitung="hitung-catatan"><?= e($catatan) ?></textarea>
        <span class="bantuan-field"><span>Membantu pencocokan relawan. Tulis seperlunya.</span>
          <span id="hitung-catatan"><?= mb_strlen($catatan) ?>/255</span></span>
      </div>
      <div class="aksi">
        <?php $flashKelas = 'alert--ringkas aksi__info'; require __DIR__ . '/layout/flash.php'; ?>
        <button type="reset" class="tombol tombol--garis">Batal</button>
        <button type="submit" class="tombol"><?= ikon('check') ?>Simpan Profil</button>
      </div>
    </form>
  </section>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
