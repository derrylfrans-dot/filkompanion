<?php
// Form laporan kendala
require __DIR__ . '/layout/kepala.php';
$deskripsi = (string) ($isian['deskripsi'] ?? '');
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <a href="<?= url('tiket', ['id' => $tiket['idTiket']]) ?>">Tiket Saya</a><?= ikon('chevron-right') ?>
  <span aria-current="page">Laporkan Kendala</span>
</nav>
<div class="judul-halaman"><div>
  <h1>Laporkan Kendala Pendampingan</h1>
  <p>Jelaskan kendala yang terjadi. Laporan diteruskan kepada Admin FILKOM untuk ditindaklanjuti.</p>
</div></div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<div class="grid" style="grid-template-columns: 480px 1fr">
  <section class="kartu" aria-labelledby="judul-ringkasan">
    <div class="kartu__kepala">
      <span class="ikon-tile"><?= ikon('ticket') ?></span>
      <div><h2 id="judul-ringkasan">Ringkasan Tiket</h2><p>Detail layanan pendampingan</p></div>
    </div>
    <div class="tag-baris" style="align-items:center"><?= badge($tiket['status']) ?><span class="kartu-tugas__id"><?= e($tiket['idTiket']) ?></span></div>
    <div class="detail-list">
      <div class="detail-item"><?= ikon('users') ?><div><small>Layanan</small><strong><?= e(layananSingkat($tiket['jenisLayanan'])) ?></strong></div></div>
      <div class="detail-item"><?= ikon('calendar') ?><div><small>Jadwal</small>
        <strong><?= e(tglIndo($tiket['jadwalKelas'], 'hari')) ?> · <?= e(jam($tiket['waktuMulai'])) ?> – <?= e(jam($tiket['waktuSelesai'])) ?></strong></div></div>
      <div class="detail-item"><?= ikon('map-pin') ?><div><small>Lokasi</small><strong><?= e($tiket['lokasi']) ?></strong></div></div>
      <div class="detail-item"><?= ikon('user') ?><div><small>Relawan Pendamping</small><strong><?= e($tiket['namaRelawan'] ?? '-') ?></strong></div></div>
    </div>
  </section>

  <section class="kartu" aria-labelledby="judul-kendala">
    <div class="kartu__kepala">
      <span class="ikon-tile ikon-tile--kuning"><?= ikon('clipboard') ?></span>
      <div><h2 id="judul-kendala">Detail Kendala</h2><p>Kolom bertanda * wajib diisi.</p></div>
    </div>
    <form method="post" action="<?= url('kendala/kirim') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="idTiket" value="<?= e($tiket['idTiket']) ?>">
      <div class="field">
        <label for="jenisKendala">Jenis kendala *</label>
        <select class="select" id="jenisKendala" name="jenisKendala" required aria-describedby="bantuan-jenis">
          <option value="">Pilih jenis kendala</option>
          <?php foreach ($opsiKendala as $o): ?>
            <option value="<?= e($o) ?>" <?= ($isian['jenisKendala'] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="bantuan-field" id="bantuan-jenis">Pilihan: relawan tidak hadir, relawan terlambat, akses/lokasi, atau lainnya.</span>
      </div>
      <div class="field">
        <label for="deskripsi">Detail kendala *</label>
        <textarea class="textarea" id="deskripsi" name="deskripsi" required maxlength="255" data-maks="255" data-hitung="hitung-deskripsi"
                  placeholder="Ceritakan kronologi singkat, misalnya: Pendamping terlambat datang 15 menit."><?= e($deskripsi) ?></textarea>
        <span class="bantuan-field"><span>Tulis kronologi dan konteks kejadian seperlunya.</span><span id="hitung-deskripsi">0/255</span></span>
      </div>
      <div class="field">
        <span class="label">Dokumen bukti pendukung (opsional)</span>
        <div class="baris-field">
          <div class="dropzone" data-dropzone data-daftar="daftar-lampiran" data-jumlah="jumlah-lampiran" style="position:relative">
            <span class="dropzone__ikon"><?= ikon('upload') ?></span>
            <strong>Seret &amp; lepas file di sini</strong>
            <span>JPG atau PNG · maks. 5 MB per file</span>
            <label class="tombol tombol--garis tombol--kecil"><?= ikon('file-plus') ?>Pilih File
              <input type="file" name="lampiran[]" multiple accept="image/jpeg,image/png"></label>
          </div>
          <div>
            <p class="label-seksi" style="text-transform:none;letter-spacing:.2px">File terpilih (<span id="jumlah-lampiran">0</span>)</p>
            <div class="daftar-berkas" id="daftar-lampiran"></div>
          </div>
        </div>
      </div>
      <div class="aksi">
        <a class="tombol tombol--garis" href="<?= url('tiket', ['id' => $tiket['idTiket']]) ?>">Batal</a>
        <button type="submit" class="tombol"><?= ikon('send') ?>Kirim Laporan</button>
      </div>
    </form>
  </section>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
