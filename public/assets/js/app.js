/* Interaksi antarmuka */
(function () {
  'use strict';
  var $ = function (s, el) { return (el || document).querySelector(s); };
  var $$ = function (s, el) { return Array.prototype.slice.call((el || document).querySelectorAll(s)); };
  var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  var HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

  function ukuran(b) { return b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
  function tgl(d) { return d.getDate() + ' ' + BULAN[d.getMonth()] + ' ' + d.getFullYear(); }

  /* Notifikasi & menu akun */
  var tombolNotif = $('[data-buka-notif]');
  var panelNotif = $('#panel-notif');
  if (tombolNotif && panelNotif) {
    tombolNotif.addEventListener('click', function (e) {
      e.stopPropagation();
      var buka = panelNotif.hidden;
      panelNotif.hidden = !buka;
      tombolNotif.setAttribute('aria-expanded', String(buka));
      var jumlah = $('.lonceng__jumlah');
      if (buka && jumlah) {
        var data = new FormData();
        data.append('_csrf', tombolNotif.getAttribute('data-csrf'));
        fetch('index.php?r=notifikasi/baca', { method: 'POST', body: data, credentials: 'same-origin' })
          .then(function () { jumlah.remove(); });
      }
    });
  }
  var tombolAkun = $('[data-buka-akun]');
  var menuAkun = $('#menu-akun');
  if (tombolAkun && menuAkun) {
    tombolAkun.addEventListener('click', function (e) {
      e.stopPropagation();
      menuAkun.hidden = !menuAkun.hidden;
      tombolAkun.setAttribute('aria-expanded', String(!menuAkun.hidden));
    });
  }
  document.addEventListener('click', function (e) {
    if (panelNotif && !panelNotif.hidden && !panelNotif.contains(e.target)) { panelNotif.hidden = true; }
    if (menuAkun && !menuAkun.hidden && !menuAkun.contains(e.target)) { menuAkun.hidden = true; }
  });

  /* Filter otomatis */
  $$('[data-kirim-otomatis]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  /* Tautan nonaktif */
  $$('a[aria-disabled="true"]').forEach(function (a) {
    a.addEventListener('click', function (e) { e.preventDefault(); });
  });

  /* Baris tabel terpilih */
  $$('tr[data-href]').forEach(function (tr) {
    tr.tabIndex = 0;
    tr.addEventListener('click', function (e) { if (!e.target.closest('a, button')) { location.href = tr.getAttribute('data-href'); } });
    tr.addEventListener('keydown', function (e) { if (e.key === 'Enter') { location.href = tr.getAttribute('data-href'); } });
  });

  /* Penghitung karakter */
  $$('[data-maks]').forEach(function (el) {
    var target = document.getElementById(el.getAttribute('data-hitung'));
    var perbarui = function () { if (target) { target.textContent = el.value.length + '/' + el.getAttribute('data-maks'); } };
    el.addEventListener('input', perbarui);
    var form = el.form;
    if (form) { form.addEventListener('reset', function () { setTimeout(perbarui, 0); }); }
    perbarui();
  });

  /* Dropzone berkas */
  function pasangDropzone(zona) {
    var input = $('input[type=file]', zona);
    var daftar = document.getElementById(zona.getAttribute('data-daftar'));
    var jumlah = document.getElementById(zona.getAttribute('data-jumlah'));
    if (!input) { return; }
    var tampilkan = function () {
      if (!daftar) { return; }
      daftar.innerHTML = '';
      Array.prototype.forEach.call(input.files, function (f, i) {
        var el = document.createElement('div');
        el.className = 'berkas';
        el.innerHTML = '<span class="berkas__ikon">' + ikonDok + '</span><div><div class="berkas__nama"></div><small></small></div>' +
          '<div class="berkas__aksi"><button type="button" class="tautan tautan--bahaya">' + ikonHapus + 'Hapus</button></div>';
        $('.berkas__nama', el).textContent = f.name;
        $('small', el).textContent = (f.name.split('.').pop() || '').toUpperCase() + ' · ' + ukuran(f.size);
        $('button', el).addEventListener('click', function () { hapusBerkas(i); });
        daftar.appendChild(el);
      });
      if (jumlah) { jumlah.textContent = input.files.length; }
      zona.dispatchEvent(new CustomEvent('berkas-berubah', { bubbles: true }));
    };
    var hapusBerkas = function (indeks) {
      var dt = new DataTransfer();
      Array.prototype.forEach.call(input.files, function (f, i) { if (i !== indeks) { dt.items.add(f); } });
      input.files = dt.files;
      tampilkan();
    };
    input.addEventListener('change', tampilkan);
    ['dragenter', 'dragover'].forEach(function (ev) {
      zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.add('aktif'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.remove('aktif'); });
    });
    zona.addEventListener('drop', function (e) {
      var dt = new DataTransfer();
      if (input.multiple) { Array.prototype.forEach.call(input.files, function (f) { dt.items.add(f); }); }
      Array.prototype.forEach.call(e.dataTransfer.files, function (f, i) { if (input.multiple || i === 0) { dt.items.add(f); } });
      input.files = dt.files;
      tampilkan();
    });
  }
  var ikonDok = '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>';
  var ikonHapus = '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
  $$('[data-dropzone]').forEach(pasangDropzone);

  /* Tombol unggah bukti */
  $$('[data-wajib-berkas]').forEach(function (tombol) {
    var input = document.getElementById(tombol.getAttribute('data-wajib-berkas'));
    var cek = function () { tombol.disabled = !input || input.files.length === 0; };
    if (input) { input.addEventListener('change', cek); input.closest('[data-dropzone]').addEventListener('berkas-berubah', cek); }
    cek();
  });

  /* Keahlian tambahan */
  var wadah = $('#daftar-tambahan');
  var templat = $('#templat-tambahan');
  var tombolTambah = $('[data-tambah-keahlian]');
  if (wadah && templat && tombolTambah) {
    var urut = $$('.entri-tambahan', wadah).length;
    var nomori = function () {
      $$('.entri-tambahan', wadah).forEach(function (el, i) { $('.nomor-entri', el).textContent = i + 1; });
    };
    var pasangEntri = function (el) {
      $('[data-hapus-entri]', el).addEventListener('click', function () { el.remove(); nomori(); });
      pasangDropzone($('[data-dropzone]', el));
    };
    $$('.entri-tambahan', wadah).forEach(pasangEntri);
    tombolTambah.addEventListener('click', function () {
      var html = templat.innerHTML.replace(/__I__/g, String(urut++));
      var div = document.createElement('div');
      div.innerHTML = html.trim();
      var el = div.firstChild;
      wadah.appendChild(el);
      pasangEntri(el);
      nomori();
      var masukan = $('input[type=text]', el);
      if (masukan) { masukan.focus(); }
    });
  }

  /* Pengajuan berulang */
  var cekBerulang = $('#opsiBerulang');
  if (cekBerulang) {
    var panel = $('#panel-berulang');
    var ringkas = $('#ringkasan-berulang');
    var hitung = function () {
      panel.hidden = !cekBerulang.checked;
      $$('input', panel).forEach(function (i) { i.required = cekBerulang.checked; });
      var t = $('#tanggal').value, m = $('#periodeMulai').value, a = $('#periodeAkhir').value;
      if (!t || !m || !a) { ringkas.textContent = 'Isi tanggal sesi dan periode untuk melihat jumlah tiket.'; return; }
      var hari = new Date(t + 'T00:00:00').getDay();
      var d = new Date(m + 'T00:00:00'), akhir = new Date(a + 'T00:00:00'), n = 0;
      for (; d <= akhir; d.setDate(d.getDate() + 1)) { if (d.getDay() === hari) { n++; } }
      var wm = $('#waktuMulai').value.replace(':', '.'), ws = $('#waktuSelesai').value.replace(':', '.');
      ringkas.textContent = n + ' tiket akan dibuat: setiap ' + HARI[hari] + ', ' + wm + '–' + ws +
        ', periode ' + tgl(new Date(m + 'T00:00:00')).replace(/ \d{4}$/, '') + ' – ' + tgl(akhir) + '.';
    };
    ['change', 'input'].forEach(function (ev) {
      ['#opsiBerulang', '#tanggal', '#periodeMulai', '#periodeAkhir', '#waktuMulai', '#waktuSelesai'].forEach(function (s) {
        var el = $(s); if (el) { el.addEventListener(ev, hitung); }
      });
    });
    hitung();
  }

  /* Ringkasan kalender ketersediaan */
  var kalender = $('[data-kalender]');
  if (kalender) {
    var ringkasBlok = $('#ringkasan-blok');
    var perbaruiBlok = function () {
      var n = $$('input[type=checkbox]:checked', kalender).length;
      ringkasBlok.textContent = n + ' blok · ' + (n * 2) + ' jam/minggu';
    };
    kalender.addEventListener('change', perbaruiBlok);
    var f = kalender.closest('form');
    if (f) { f.addEventListener('reset', function () { setTimeout(perbaruiBlok, 0); }); }
    perbaruiBlok();
  }

  /* Keputusan tindak lanjut laporan */
  var pilihKeputusan = $('#keputusan');
  if (pilihKeputusan && $('#durasiBaru')) {
    var cekJam = $('#penyesuaianJam'), durasi = $('#durasiBaru');
    var sinkron = function () {
      var v = pilihKeputusan.value;
      if (v.indexOf('jam disesuaikan') > -1) { cekJam.checked = true; }
      if (v.indexOf('tidak sah') > -1) { cekJam.checked = true; durasi.value = '0'; }
      if (v.indexOf('tanpa perubahan') > -1) { cekJam.checked = false; }
      durasi.disabled = !cekJam.checked;
    };
    pilihKeputusan.addEventListener('change', sinkron);
    cekJam.addEventListener('change', function () { durasi.disabled = !cekJam.checked; });
    durasi.disabled = !cekJam.checked;
  }

  /* Dialog konfirmasi */
  var dialog = $('#dialog-konfirmasi');
  if (dialog) {
    var formTertunda = null, tombolTertunda = null;
    $$('[data-konfirmasi]').forEach(function (tombol) {
      tombol.addEventListener('click', function (e) {
        var form = tombol.form;
        $$('[data-wajib]', form).forEach(function (t) {
          var f = document.getElementById(t.getAttribute('data-wajib'));
          if (f) { f.setCustomValidity(''); }
        });
        if (tombol.hasAttribute('data-wajib')) {
          var wajib = document.getElementById(tombol.getAttribute('data-wajib'));
          if (wajib && wajib.value.trim() === '') {
            e.preventDefault();
            wajib.setCustomValidity(tombol.getAttribute('data-pesan-wajib') || 'Wajib diisi.');
            wajib.reportValidity();
            wajib.addEventListener('input', function () { wajib.setCustomValidity(''); }, { once: true });
            return;
          }
        }
        if (form && !form.reportValidity()) { e.preventDefault(); return; }
        e.preventDefault();
        formTertunda = form; tombolTertunda = tombol;
        $('#dialog-judul').textContent = tombol.getAttribute('data-konfirmasi');
        $('#dialog-pesan').textContent = tombol.getAttribute('data-pesan') || '';
        var bidang = $('#dialog-catatan-bidang');
        bidang.hidden = !tombol.hasAttribute('data-catatan');
        $('#dialog-ya').textContent = tombol.getAttribute('data-ya') || 'Ya, lanjutkan';
        $('#dialog-ya').className = 'tombol' + (tombol.classList.contains('tombol--bahaya') ? ' tombol--bahaya' : '');
        dialog.showModal();
      });
    });
    $('#dialog-ya').addEventListener('click', function () {
      if (!formTertunda) { return; }
      var bidang = $('#dialog-catatan-bidang');
      if (!bidang.hidden) {
        var tersembunyi = $('input[name=catatan]', formTertunda) || document.createElement('input');
        tersembunyi.type = 'hidden'; tersembunyi.name = 'catatan'; tersembunyi.value = $('#dialog-catatan').value;
        formTertunda.appendChild(tersembunyi);
      }
      if (tombolTertunda.name) {
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = tombolTertunda.name; h.value = tombolTertunda.value;
        formTertunda.appendChild(h);
      }
      if (tombolTertunda.hasAttribute('formaction')) { formTertunda.action = tombolTertunda.getAttribute('formaction'); }
      dialog.close();
      HTMLFormElement.prototype.submit.call(formTertunda);
    });
    $('#dialog-batal').addEventListener('click', function () { dialog.close(); });
  }
})();
