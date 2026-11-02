<?php
// Tindak lanjut kendala
require __DIR__ . '/layout/kepala.php';
$terbuka = count(array_filter($daftar, fn ($l) => $l['statusTindakLanjut'] === 'Belum Ditindaklanjuti'));
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="<?= url('beranda') ?>"><?= e($akarBreadcrumb) ?></a><?= ikon('chevron-right') ?>
  <span aria-current="page">Laporan Kendala</span>
</nav>
<div class="judul-halaman"><div>
  <h1>Tindak Lanjut Laporan Kendala</h1>
  <p>Tinjau laporan kendala dari mahasiswa, tetapkan tindak lanjut, dan sesuaikan pencatatan jam bila diperlukan.</p>
</div></div>
<?php require __DIR__ . '/layout/flash.php'; ?>

<div class="grid grid--daftar-detail">
  <section class="kartu kartu--tanpa-padding" aria-labelledby="judul-daftar">
    <div class="kartu__kepala">
      <span class="ikon-tile"><?= ikon('alert') ?></span>
      <div><h2 id="judul-daftar">Daftar Laporan</h2><p><?= $terbuka ?> laporan menunggu tindak lanjut.</p></div>
    </div>
    <div class="tabel-wrap">
      <table class="tabel">
        <thead><tr><th>ID laporan</th><th>Tiket</th><th>Jenis kendala</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($daftar as $l): ?>
          <tr data-href="<?= e(url('admin/kendala', ['id' => $l['idLaporan']])) ?>"
              class="<?= $detail && $detail['idLaporan'] === $l['idLaporan'] ? 'dipilih' : '' ?>">
            <td><?= e($l['idLaporan']) ?></td>
            <td><?= e($l['idTiket']) ?></td>
            <td><?= e($l['jenisKendala']) ?></td>
            <td><?= badge($l['statusTindakLanjut']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$daftar): ?><tr><td colspan="4" class="tabel-kosong">Belum ada laporan kendala.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="kartu" aria-label="Detail laporan">
    <?php if (!$detail): ?>
      <p class="kosong"><?= ikon('check-circle') ?><br>Tidak ada laporan untuk ditinjau.</p>
    <?php else: ?>
      <div class="kartu__kepala">
        <span class="ikon-tile ikon-tile--kuning"><?= ikon('file-text') ?></span>
        <div><h2>Detail <?= e($detail['idLaporan']) ?></h2>
          <p><?= e($detail['idTiket']) ?> · <?= e(layananSingkat($detail['jenisLayanan'])) ?> ·
            <?= e(tglIndo($detail['waktuMulai'])) ?>, <?= e(rentangJam($detail['waktuMulai'], $detail['waktuSelesai'])) ?></p></div>
      </div>
      <p style="font-size:13px;margin-bottom:12px">Pelapor: <b><?= e($detail['namaPelapor']) ?></b>
        &nbsp;&nbsp; Relawan: <b><?= e($detail['namaRelawan'] ?? '-') ?></b></p>
      <div class="panel-abu">
        <p class="label-seksi">Deskripsi kendala · <?= e($detail['jenisKendala']) ?></p>
        <p><?= nl2br(e($detail['deskripsi'])) ?></p>
        <?php if ($detail['lampiran']): ?>
          <p style="margin-top:10px;display:flex;gap:16px;flex-wrap:wrap">
            <?php foreach (explode(',', $detail['lampiran']) as $i => $f): ?>
              <a class="tautan" target="_blank" href="<?= url('kendala/lampiran', ['id' => $detail['idLaporan'], 'file' => $f]) ?>"><?= ikon('image') ?>Lampiran <?= $i + 1 ?></a>
            <?php endforeach; ?>
          </p>
        <?php endif; ?>
      </div>

      <?php if ($detail['statusTindakLanjut'] === 'Belum Ditindaklanjuti'): ?>
        <form method="post" action="<?= url('admin/kendala/simpan') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="idLaporan" value="<?= e($detail['idLaporan']) ?>">
          <div class="field">
            <label for="keputusan">Keputusan tindak lanjut *</label>
            <select class="select" id="keputusan" name="keputusan" required>
              <option value="">Pilih keputusan</option>
              <?php foreach ($opsiKeputusan as $o): ?><option value="<?= e($o) ?>"><?= e($o) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="catatanAdmin">Catatan Admin</label>
            <textarea class="textarea" id="catatanAdmin" name="catatanAdmin" maxlength="255" style="min-height:72px"
                      placeholder="Ringkasan hasil tindak lanjut, misalnya hasil konfirmasi ke relawan."></textarea>
          </div>
          <div class="panel-abu">
            <label class="cek"><input type="checkbox" id="penyesuaianJam" name="penyesuaianJam" value="1"
              <?= $detail['durasiTercatat'] === null ? 'disabled' : '' ?>>Penyesuaian jam pengabdian diperlukan</label>
            <div class="info-baris" style="margin-top:12px">
              <?php if ($detail['durasiTercatat'] !== null): ?>
                Durasi tercatat <b style="color:var(--teks)"><?= angka($detail['durasiTercatat']) ?> jam</b><?= ikon('arrow-right') ?>
                <label class="sr-only" for="durasiBaru">Durasi baru (jam)</label>
                <input class="input" type="number" id="durasiBaru" name="durasiBaru" min="0" max="12" step="0.25"
                       value="<?= e((float) $detail['durasiTercatat']) ?>" style="width:120px;min-height:40px"> jam
              <?php else: ?>
                Durasi belum tercatat karena tiket belum diselesaikan relawan.
              <?php endif; ?>
            </div>
          </div>
          <div class="aksi">
            <button type="submit" class="tombol" data-konfirmasi="Simpan keputusan tindak lanjut?"
                    data-pesan="Status laporan menjadi Ditindaklanjuti dan catatan tiket diperbarui." data-ya="Ya, simpan"><?= ikon('check-circle') ?>Simpan &amp; Tandai Selesai</button>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert--sukses"><?= ikon('check-circle') ?><div>
          <strong><?= e($detail['keputusan']) ?></strong>
          <?= e($detail['catatanAdmin'] ?? '') ?><br><small>Ditindaklanjuti <?= e(tglIndo($detail['tanggalTindakLanjut'], 'lengkap')) ?></small></div></div>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/layout/kaki.php'; ?>
