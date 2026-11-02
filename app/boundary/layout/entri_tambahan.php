<?php
// Entri keahlian tambahan
?>
<div class="panel-abu entri-tambahan">
  <div class="aksi aksi--antara" style="margin-bottom:12px">
    <span class="label-seksi" style="margin:0">Keahlian tambahan <span class="nomor-entri"><?= is_int($nomor) ? $nomor + 1 : '' ?></span></span>
    <button type="button" class="tautan tautan--bahaya" data-hapus-entri><?= ikon('trash') ?>Hapus</button>
  </div>
  <div class="field">
    <label for="tambahan-<?= e($nomor) ?>">Nama keahlian *</label>
    <input class="input" id="tambahan-<?= e($nomor) ?>" name="tambahan[<?= e($nomor) ?>][nama]" maxlength="80" required
           placeholder="Contoh: Transkrip Real-time (Live Caption)" value="<?= e($namaLama) ?>">
  </div>
  <div class="field" style="margin-bottom:0">
    <span class="label">Dokumen bukti keahlian *</span>
    <span class="bantuan-field">Wajib untuk setiap keahlian tambahan · PDF, JPG, atau PNG · maks. 5 MB</span>
    <div class="dropzone" data-dropzone data-daftar="daftar-berkas-<?= e($nomor) ?>" style="position:relative;padding:14px">
      <label class="tombol tombol--garis tombol--kecil"><?= ikon('file-plus') ?>Pilih File
        <input type="file" name="tambahanDokumen[<?= e($nomor) ?>]" accept=".pdf,.jpg,.jpeg,.png"></label>
    </div>
    <div class="daftar-berkas" id="daftar-berkas-<?= e($nomor) ?>"></div>
  </div>
</div>
