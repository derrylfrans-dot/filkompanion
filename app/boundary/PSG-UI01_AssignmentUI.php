<?php
// Daftar penugasan relawan
require __DIR__ . '/layout/kepala.php';
$blokTeks = function (array $t) {
    $blok = VolunteerDB::blokTerkait($t['waktuMulai'], $t['waktuSelesai']);
    $teks = array_map(fn ($b) => str_replace(':', '.', VolunteerDB::BLOK[$b][0]) . '–' . str_replace(':', '.', VolunteerDB::BLOK[$b][1]), $blok);
    return tglIndo($t['jadwalKelas'], 'namaHari') . ' ' . implode(' & ', $teks);
};
$kartu = function (array $t, bool $menunggu) use ($detail) { ?>
  <a class="kartu-tugas <?= $detail && $detail['idTiket'] === $t['idTiket'] ? 'dipilih' : '' ?>" href="<?= url('penugasan', ['id' => $t['idTiket']]) ?>">
    <div class="kartu-tugas__atas">
      <span class="kartu-tugas__id"><?= e($t['idTiket']) ?></span>
      <span class="tag <?= $t['jenisLayanan'] === 'Note-taker' ? 'tag--kuning' : '' ?>"><?= e($t['jenisLayanan']) ?></span>
      <?= $menunggu ? badge('Menunggu Respons') : ($t['status'] === 'Teralokasi' ? badge('Jadwal Terkunci') : badge('Dilaksanakan')) ?>
    </div>
    <div class="kartu-tugas__info">
      <span><?= ikon('calendar') ?><?= e(tglIndo($t['jadwalKelas'], 'hari')) ?> · <?= e(rentangJam($t['waktuMulai'], $t['waktuSelesai'])) ?></span>
      <span><?= ikon('map-pin') ?><?= e(str_replace(' FILKOM', '', $t['lokasi'])) ?></span>
    </div>
    <div class="kartu-tugas__bawah">
      <?php if ($menunggu): ?>
        <span class="cocok"><?= ikon('check-circle') ?>Keahlian cocok</span>
        <span class="cocok"><?= ikon('check-circle') ?>Jadwal tersedia</span>
      <?php else: ?>
        <span>Status tiket: <?= e($t['status']) ?></span>
        <span class="tautan <?= $t['status'] === 'Teralokasi' ? 'tautan--bahaya' : '' ?>" style="margin-left:auto">
          <?= $t['status'] === 'Teralokasi' ? ikon('alert') . 'Pembatalan darurat' : ikon('upload') . 'Unggah bukti &amp; selesaikan' ?></span>
      <?php endif; ?>
    </div>
  </a>
<?php };
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Penugasan</span>
</nav>
<div class="judul-halaman"><div>
  <h1>Penugasan</h1>
  <p>Tinjau penugasan hasil pencocokan keahlian dan ketersediaanmu, lalu terima atau tolak.</p>
</div></div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<?php if ($menunggu): ?>
  <div class="alert" role="status"><?= ikon('info') ?><div>
    <strong><?= count($menunggu) ?> penugasan baru menunggu responsmu</strong>
    Tiket berikut cocok dengan keahlian dan blok ketersediaanmu.</div></div>
<?php endif; ?>

<div class="grid grid--daftar-detail">
  <div>
    <p class="label-seksi">Menunggu respons (<?= count($menunggu) ?>)</p>
    <?php foreach ($menunggu as $t) { $kartu($t, true); } ?>
    <?php if (!$menunggu): ?><p class="kartu kosong" style="margin-bottom:12px">Belum ada penugasan baru.</p><?php endif; ?>
    <p class="label-seksi" style="margin-top:20px">Penugasan aktif (<?= count($aktif) ?>)</p>
    <?php foreach ($aktif as $t) { $kartu($t, false); } ?>
    <?php if (!$aktif): ?><p class="kartu kosong">Belum ada penugasan aktif.</p><?php endif; ?>
  </div>

  <section class="kartu" aria-label="Detail penugasan">
    <?php if (!$detail): ?>
      <p class="kosong"><?= ikon('clipboard') ?><br>Pilih penugasan untuk melihat detail.</p>
    <?php else: ?>
      <div class="kartu__kepala">
        <span class="ikon-tile ikon-tile--kuning"><?= ikon('clipboard') ?></span>
        <div><h2>Detail <?= e($detail['idTiket']) ?></h2><p>Hasil pencocokan keahlian &amp; ketersediaan</p></div>
      </div>
      <div class="detail-list" style="margin-bottom:20px">
        <div class="detail-item"><?= ikon('users') ?><div><small>Jenis kebutuhan pendampingan</small><strong><?= e($detail['jenisLayanan']) ?></strong></div></div>
        <div class="detail-item"><?= ikon('calendar') ?><div><small>Waktu</small>
          <strong><?= e(tglIndo($detail['jadwalKelas'], 'hari')) ?> · <?= e(rentangJam($detail['waktuMulai'], $detail['waktuSelesai'])) ?></strong></div></div>
        <div class="detail-item"><?= ikon('map-pin') ?><div><small>Lokasi</small><strong><?= e($detail['lokasi']) ?></strong></div></div>
        <div class="detail-item"><?= ikon('user') ?><div><small>Mahasiswa</small><strong><?= e($detail['namaMahasiswa']) ?></strong></div></div>
        <?php if ($detail['catatanKebutuhan']): ?>
          <div class="detail-item"><?= ikon('message') ?><div><small>Catatan</small><strong><?= e($detail['catatanKebutuhan']) ?></strong></div></div>
        <?php endif; ?>
      </div>

      <?php if ($detail['status'] === 'Menunggu Respons'): ?>
        <div class="alert alert--tanpa-garis"><?= ikon('lock') ?>
          <div>Jika diterima, blok <?= e($blokTeks($detail)) ?> di kalender ketersediaanmu akan dikunci untuk penugasan ini.</div></div>
        <form method="post" action="<?= url('penugasan/terima') ?>" class="aksi aksi--antara">
          <?= csrf_field() ?>
          <input type="hidden" name="idTiket" value="<?= e($detail['idTiket']) ?>">
          <button type="submit" class="tombol tombol--bahaya" formaction="<?= url('penugasan/tolak') ?>"
                  data-konfirmasi="Tolak penugasan <?= e($detail['idTiket']) ?>?" data-pesan="Tiket dikembalikan ke antrean pencarian relawan."
                  data-ya="Ya, tolak"><?= ikon('x-circle') ?>Tolak Penugasan</button>
          <button type="submit" class="tombol"><?= ikon('check-circle') ?>Terima Penugasan</button>
        </form>
      <?php elseif ($detail['status'] === 'Teralokasi'): ?>
        <div class="alert alert--sukses alert--tanpa-garis"><?= ikon('lock') ?>
          <div><strong>Jadwal terkunci</strong>Blok <?= e($blokTeks($detail)) ?> dikunci untuk penugasan ini.</div></div>
        <?php if ($modeBatal): ?>
          <form method="post" action="<?= url('penugasan/batal') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="idTiket" value="<?= e($detail['idTiket']) ?>">
            <div class="field">
              <label for="alasan">Alasan pembatalan darurat *</label>
              <textarea class="textarea" id="alasan" name="alasan" required maxlength="255" data-maks="255" data-hitung="hitung-alasan"
                        placeholder="Jelaskan kondisi darurat yang membuatmu tidak dapat hadir."></textarea>
              <span class="bantuan-field"><span>Tiket akan dikembalikan ke pencarian relawan pengganti.</span><span id="hitung-alasan">0/255</span></span>
            </div>
            <div class="aksi">
              <a class="tombol tombol--garis" href="<?= url('penugasan', ['id' => $detail['idTiket']]) ?>">Batal</a>
              <button type="submit" class="tombol tombol--bahaya" data-konfirmasi="Kirim pembatalan darurat?"
                      data-pesan="Kunci jadwalmu dilepas dan tiket dicarikan relawan pengganti." data-ya="Ya, kirim"><?= ikon('alert') ?>Kirim Pembatalan</button>
            </div>
          </form>
        <?php else: ?>
          <a class="tombol tombol--bahaya tombol--penuh" href="<?= url('penugasan', ['id' => $detail['idTiket'], 'aksi' => 'batal']) ?>"><?= ikon('alert') ?>Pembatalan Darurat</a>
        <?php endif; ?>
      <?php else: ?>
        <div class="alert alert--tanpa-garis"><?= ikon('info') ?><div>Sesi sudah dimulai. Unggah foto bukti kegiatan lalu selesaikan tiket.</div></div>
        <a class="tombol tombol--penuh" href="<?= url('bukti', ['id' => $detail['idTiket']]) ?>"><?= ikon('upload') ?>Unggah Bukti &amp; Selesaikan</a>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
