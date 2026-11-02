<?php
// Footer halaman
?>
      </main>
      <footer class="footer">
        <span>© 2026 FILKOMpanion · Fakultas Ilmu Komputer, Universitas Brawijaya</span>
        <nav aria-label="Tautan pendukung">
          <a href="<?= url('bantuan') ?>#bantuan">Bantuan</a> ·
          <a href="<?= url('bantuan') ?>#privasi">Kebijakan Privasi</a> ·
          <a href="<?= url('bantuan') ?>#aksesibilitas">Aksesibilitas</a>
        </nav>
      </footer>
    </div>
  </div>
</div>

<dialog id="dialog-konfirmasi" class="dialog" aria-labelledby="dialog-judul">
  <h2 id="dialog-judul">Konfirmasi</h2>
  <p id="dialog-pesan"></p>
  <div class="field" id="dialog-catatan-bidang" hidden>
    <label for="dialog-catatan">Catatan peninjauan <small>(opsional)</small></label>
    <textarea id="dialog-catatan" class="textarea" maxlength="255" placeholder="Contoh: Durasi TKT-0126 perlu dicek ulang."></textarea>
  </div>
  <div class="aksi">
    <button type="button" class="tombol tombol--garis" id="dialog-batal">Batal</button>
    <button type="button" class="tombol" id="dialog-ya">Ya, lanjutkan</button>
  </div>
</dialog>
<script src="<?= aset('js/app.js') ?>"></script>
</body>
</html>
