# FILKOMpanion — Panduan Menjalankan Secara Lokal

Sistem pendampingan mahasiswa disabilitas FILKOM UB (Kelompok 7, Rekayasa Sistem Informasi SI-D).
Implementasi Modul 3 dari rancangan Modul 1 (SRS) dan Modul 2 (use case → sketsa UI).

- Bahasa & arsitektur: PHP 8 native berorientasi objek (pola boundary–control–entity sesuai class diagram).
- Basis data: MySQL / MariaDB (bawaan Laragon / Homebrew).
- Tanpa framework dan tanpa `composer install`. Semua kode, termasuk pembuat PDF/XLSX, ada di paket ini.
- Aplikasi ini hanya untuk dijalankan di komputer sendiri (localhost) dan tidak perlu dipublikasikan.

---

## 1. Yang perlu disiapkan

| Sistem operasi | Yang diinstal |
|---|---|
| **Windows** | **Laragon** (berisi Apache, PHP 8, MySQL, dan HeidiSQL). Unduh di https://laragon.org/download |
| **macOS** | **Homebrew**, lalu `php` dan `mysql` (lihat Cara B) |
| Semua | Browser (Chrome/Edge/Firefox) dan folder `filkompanion` hasil ekstrak zip |

> Opsional: koneksi internet hanya dipakai untuk memuat font Inter dari Google Fonts.
> Tanpa internet, aplikasi tetap berjalan dengan font sistem.

---

## 2. Cara A — Windows dengan Laragon (disarankan)

1. Instal Laragon (versi Full), lalu buka aplikasinya dan klik **Start All**. Tunggu sampai Apache dan MySQL berjalan.
2. Salin folder `filkompanion` ke `C:\laragon\www\`.
   Hasilnya: `C:\laragon\www\filkompanion\public\index.php`.
3. Klik tombol **Terminal** di Laragon, lalu jalankan:
   ```bat
   cd C:\laragon\www\filkompanion
   mysql -u root < database\schema.sql
   mysql -u root < database\seed.sql
   ```
   Alternatif tanpa terminal: klik **Database** di Laragon (HeidiSQL), sambungkan ke server lokal,
   lalu **File → Run SQL file** untuk `schema.sql`, kemudian `seed.sql`.
4. Klik **Reload** (atau Stop lalu Start All) agar Laragon membuat alamat otomatis untuk folder baru.
5. Buka **http://filkompanion.test** (atau **http://localhost/filkompanion/**). Halaman login FILKOMpanion akan tampil.

> Laragon gratis untuk penggunaan non-komersial. Sesekali muncul pengingat lisensi; tutup saja.

---

## 3. Cara B — macOS (Homebrew, tanpa Apache)

1. Instal Homebrew (https://brew.sh), lalu di Terminal:
   ```bash
   brew install php mysql
   brew services start mysql
   ```
2. Masuk ke folder proyek, impor database, lalu jalankan server bawaan PHP:
   ```bash
   cd ~/Downloads/filkompanion        # sesuaikan lokasi folder
   mysql -u root < database/schema.sql
   mysql -u root < database/seed.sql
   php -d upload_max_filesize=10M -d post_max_size=40M -S localhost:8000 -t public
   ```
3. Buka **http://localhost:8000**. Biarkan Terminal tetap terbuka selama aplikasi dipakai (Ctrl + C untuk berhenti).

Cara B ini juga bisa dipakai di Windows bila PHP dan MySQL sudah terpasang sendiri
(misalnya memakai Terminal Laragon tanpa Apache).

---

## 4. Pengaturan koneksi database

Ubah file `config/config.php` hanya jika pengaturan MySQL di komputermu berbeda dari bawaan (user `root` tanpa password):

```php
'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,          // ganti jika MySQL memakai port lain, mis. 3307
    'name' => 'filkompanion',
    'user' => 'root',
    'pass' => '',            // isi jika user root memakai password
],
```

---

## 5. Akun demo

Login memakai simulasi **Masuk dengan Akun UB**. Cukup ketik email UB lalu tekan tombolnya, tanpa kata sandi.
Peran pengguna ditentukan otomatis oleh sistem.

| Email UB | Peran | Yang bisa dicoba |
|---|---|---|
| `nadia.putri@ub.ac.id` | Mahasiswa Disabilitas (Tunarungu) | Profil kebutuhan, ajukan tiket (termasuk berulang), Tiket Saya, batalkan tiket, konfirmasi kehadiran (TKT-0120), laporkan kendala |
| `putra.wijaya@student.ub.ac.id` | Mahasiswa Disabilitas (profil belum diisi) | Mencoba alur melengkapi profil dari awal |
| `raka.pratama@ub.ac.id`, `salsa.amelia@ub.ac.id`, `yoga.saputra@ub.ac.id` | Mahasiswa Disabilitas | Pemilik tiket yang menunggu verifikasi |
| `sinta.maharani@ub.ac.id` | Relawan Pendamping aktif (RLW001) | Profil & ketersediaan, penugasan (terima/tolak/batal darurat), unggah bukti & selesaikan TKT-0126, riwayat & jam |
| `laras.wulandari@ub.ac.id`, `dimas.aryanto@ub.ac.id`, `fajar.ramadhan@ub.ac.id` | Relawan Pendamping aktif | Menerima penugasan hasil pencocokan |
| `rizky.aditya@ub.ac.id` | Calon relawan (belum mendaftar) | Pendaftaran relawan + keahlian tambahan dengan dokumen bukti |
| `dewi.lestari@ub.ac.id` | Admin FILKOM | Validasi relawan, verifikasi tiket & KRS, tiket manual, tindak lanjut kendala, validasi jam, laporan rekap PDF/XLSX |

> Tanggal tiket mendatang pada data contoh dihitung otomatis dari tanggal saat `seed.sql` diimpor
> (Senin minggu berikutnya, dan seterusnya). Untuk mengembalikan data ke kondisi awal, impor ulang `seed.sql`.

---

## 6. Skenario uji lengkap (urut dari awal sampai akhir)

1. **AKN-UI01**: login sebagai `rizky.aditya@ub.ac.id`. Coba juga email non-UB, misalnya `a@gmail.com`; sistem akan menolaknya.
2. **AKN-UI03**: pilih keahlian dan unggah dokumen (PDF/JPG/PNG ≤ 5 MB). Klik **+ Tambah Keahlian Lain**, isi nama keahlian beserta dokumen buktinya, lalu kirim. Status berubah menjadi *Menunggu Validasi*.
3. **AKN-UI05**: login sebagai Admin, buka **Validasi Relawan**, pilih REG-021, lalu klik **Setujui Pendaftaran**. Menolak pendaftaran wajib disertai alasan.
4. **AKN-UI04**: login lagi sebagai Rizky. Atur blok kalender, bisa dengan klik atau tombol **Spasi**, lalu simpan.
5. **AKN-UI02 → TKT-UI01A**: login sebagai Nadia, cek Profil Kebutuhan, lalu ajukan tiket. Coba opsi *Ajukan berulang sesuai jadwal kuliah*. Jadwal yang sesuai KRS Nadia antara lain Senin 08.00–10.00 (F2.3), Rabu 13.00–15.00 (F1.1), dan Jumat 09.00–10.40 (G1.4).
6. **TKT-UI01B**: lihat timeline tiket dan coba **Batalkan Tiket**. Pembatalan hanya bisa dilakukan paling lambat 24 jam sebelum sesi.
7. **TKT-UI02**: sebagai Admin, buka **Verifikasi Tiket**. Tiket TKT-0158 sengaja tidak sesuai KRS, jadi hanya bisa ditolak. Setujui tiket lain; tiket yang disetujui langsung masuk ke pencocokan relawan.
8. **TKT-UI03**: sebagai Admin, buka **Tiket Manual**, cari `MHS027`, lalu simpan tiket.
9. **PSG-UI01**: login sebagai relawan yang mendapat penugasan, terima atau tolak. Penugasan yang diterima mengunci jadwal relawan. Coba juga **Pembatalan darurat** (Sinta, TKT-0138).
10. **PLS-UI01**: login sebagai Sinta, buka TKT-0126, unggah foto JPG/PNG, lalu klik **Selesaikan Tiket**. Durasi 2 jam tercatat dengan status *Menunggu Verifikasi*.
11. **PLS-UI02 / PLS-UI03**: login sebagai Nadia, konfirmasi kehadiran atau laporkan kendala.
12. **SKM-UI02**: sebagai Admin, buka **Laporan Kendala** dan tetapkan keputusan; durasi jam bisa disesuaikan, misalnya 1,5 jam.
13. **SKM-UI03**: buka **Validasi Jam**. Validasi baru bisa dilakukan setelah semua laporan kendala relawan tersebut ditindaklanjuti.
14. **SKM-UI01**: login sebagai Sinta, buka **Riwayat & Jam**.
15. **SKM-UI04**: sebagai Admin, buka **Laporan Rekap**, pilih periode, lalu klik **Unduh PDF** atau **Unduh XLSX**.

---

## 7. Struktur folder dan peta traceability

```
filkompanion/
├── index.php                 → mengarahkan ke public/
├── config/config.php         → pengaturan database & aplikasi
├── database/
│   ├── schema.sql            → DDL 7 tabel (Modul 3 · 4.1)
│   └── seed.sql              → akun demo & data contoh
├── app/
│   ├── core/                 → Database (PDO), Controller induk, Auth/CSRF, Upload, helpers
│   ├── controller/           → kontrol kelas (class diagram)
│   ├── entity/               → kelas entitas …DB (class diagram)
│   ├── service/              → UBAuthService, NotificationService, ReportService
│   ├── lib/                  → PdfSederhana, XlsxSederhana (pembuat file laporan)
│   └── boundary/             → 17 UI sesuai UI-ID + layout/ (template header, sidebar, footer)
├── public/
│   ├── index.php             → front controller & tabel rute
│   └── assets/               → css/app.css, js/app.js, img/logo.svg
└── storage/
    ├── uploads/              → dokumen relawan, foto bukti, lampiran kendala
    └── notifications.json    → notifikasi dalam aplikasi
```

| Kode (Modul 3) | Kelas / file | Class diagram | Pseudocode |
|---|---|---|---|
| DF-SC01 | `app/controller/AuthController.php` | CD-01 | AKN-PC01 |
| DF-SC02 | `app/service/UBAuthService.php` | CD-01 | AKN-PC01 |
| DF-SC03 | `app/controller/ProfileController.php` | CD-01 | AKN-PC02, AKN-PC03 |
| DF-SC04 | `app/controller/VolunteerController.php` | CD-01 | AKN-PC04, AKN-PC05, AKN-PC06 |
| DF-SC05 | `app/service/NotificationService.php` | CD-01, CD-02, CD-03 | AKN-PC04, AKN-PC06, TKT-PC01, TKT-PC03, TKT-PC04 |
| DF-SC06 | `app/entity/UserDB.php` | CD-01 | AKN-PC01–PC03 |
| DF-SC07 | `app/entity/VolunteerDB.php` | CD-01, CD-02, CD-05 | AKN-PC04–PC06, PSG-PC01–PC02, SKM-PC04 |
| SS-SC08 | `app/controller/TicketController.php` | CD-03 | TKT-PC01–PC05 |
| SS-SC09 | `app/entity/TicketDB.php` | CD-02, CD-03, CD-04, CD-05 | TKT, PSG, PLS, SKM |
| SS-SC10 | `app/entity/KRSData.php` | CD-03 | TKT-PC04 |
| AL-SC11 | `app/controller/MatchingController.php` | CD-02 | PSG-PC01 |
| AL-SC12 | `app/controller/AssignmentController.php` | CD-02 | PSG-PC02, PSG-PC03 |
| TR-SC13 | `app/controller/ActivityController.php` | CD-04 | PLS-PC01, PLS-PC02 |
| TR-SC14 | `app/controller/VerificationController.php` | CD-04 | PLS-PC03 |
| TR-SC15 | `app/controller/IssueController.php` | CD-04, CD-05 | PLS-PC04, SKM-PC02 |
| TR-SC16 | `app/entity/IssueDB.php` | CD-04, CD-05 | PLS-PC04, SKM-PC02, SKM-PC03 |
| TR-SC17 | `app/entity/VolunteerHoursDB.php` | CD-04, CD-05 | PLS-PC02, SKM-PC01–PC03 |
| AL-SC18 | `app/controller/HistoryController.php` | CD-05 | SKM-PC01 |
| AL-SC19 | `app/controller/HoursController.php` | CD-05 | SKM-PC03 |
| AL-SC20 | `app/controller/ReportController.php` | CD-05 | SKM-PC04, SKM-PC05 |
| AL-SC21 | `app/service/ReportService.php` | CD-05 | SKM-PC05 |

| UI-ID | Boundary | File | Kode (Modul 3) |
|---|---|---|---|
| AKN-UI01 | LoginUI | `app/boundary/AKN-UI01_LoginUI.php` | DF-IM-001 |
| AKN-UI02 | ProfileUI | `app/boundary/AKN-UI02_ProfileUI.php` | DF-IM-002 |
| AKN-UI03 | VolunteerRegistrationUI | `app/boundary/AKN-UI03_VolunteerRegistrationUI.php` | DF-IM-003 |
| AKN-UI04 | VolunteerProfileUI | `app/boundary/AKN-UI04_VolunteerProfileUI.php` | DF-IM-004 |
| AKN-UI05 | VolunteerValidationUI | `app/boundary/AKN-UI05_VolunteerValidationUI.php` | DF-IM-005 |
| TKT-UI01A | TicketUI (form) | `app/boundary/TKT-UI01A_TicketUI.php` | SS-IM-006 |
| TKT-UI01B | TicketUI (daftar & pembatalan) | `app/boundary/TKT-UI01B_TicketUI.php` | SS-IM-007 |
| TKT-UI02 | TicketValidationUI | `app/boundary/TKT-UI02_TicketValidationUI.php` | SS-IM-008 |
| TKT-UI03 | ManualTicketUI | `app/boundary/TKT-UI03_ManualTicketUI.php` | SS-IM-009 |
| PSG-UI01 | AssignmentUI | `app/boundary/PSG-UI01_AssignmentUI.php` | AL-IM-010 |
| PLS-UI01 | ProofUI + CompletionUI | `app/boundary/PLS-UI01_ProofUI.php` | TR-IM-011 |
| PLS-UI02 | VerificationUI | `app/boundary/PLS-UI02_VerificationUI.php` | TR-IM-012 |
| PLS-UI03 | IssueReportUI | `app/boundary/PLS-UI03_IssueReportUI.php` | TR-IM-013 |
| SKM-UI01 | HistoryUI | `app/boundary/SKM-UI01_HistoryUI.php` | AL-IM-014 |
| SKM-UI02 | IssueManagementUI | `app/boundary/SKM-UI02_IssueManagementUI.php` | AL-IM-015 |
| SKM-UI03 | HoursValidationUI | `app/boundary/SKM-UI03_HoursValidationUI.php` | AL-IM-016 |
| SKM-UI04 | ReportUI | `app/boundary/SKM-UI04_ReportUI.php` | AL-IM-017 |
| Template | Header, sidebar, footer, CSS, JS | `app/boundary/layout/*`, `public/assets/*` | DF-IM-018 – DF-IM-020 |

Daftar rute ada di `public/index.php`. Contoh: `index.php?r=tiket/ajukan` menampilkan TKT-UI01A.

---

## 8. Catatan teknis

- **Login UB (AKN-SQ01)**: proses OAuth Google Workspace UB disimulasikan oleh `UBAuthService`. Layanan ini memeriksa format email, domain `@ub.ac.id` / `@student.ub.ac.id`, dan token sesi. Akun harus sudah ada di tabel `user`. Tidak ada kata sandi yang diketik atau disimpan.
- **Notifikasi (SYS-SR-03)**: notifikasi dalam aplikasi tersimpan di `storage/notifications.json` dan tampil pada ikon lonceng.
- **Pencocokan relawan (SYS-SR-02)**: tiket *Tervalidasi* dicocokkan dengan relawan aktif yang memiliki keahlian sesuai dan blok kalender yang tersedia serta belum terkunci. Relawan dengan beban penugasan paling ringan diprioritaskan. Antrean diproses saat tiket disetujui, saat ada penolakan atau pembatalan, dan saat halaman Penugasan dibuka.
- **Status pelaksanaan**: tiket *Teralokasi* otomatis menjadi *Dilaksanakan* saat waktu mulai sesi tiba. Setelah itu fitur unggah bukti aktif.
- **Akumulasi jam (SYS-SR-04)**: durasi dicatat saat tiket diselesaikan dengan status *Menunggu Verifikasi*. Hanya jam *Terverifikasi* yang dihitung untuk SKM.
- **Keamanan dasar**: semua query memakai PDO prepared statement, setiap form POST memakai token CSRF, output di-escape, dan akses dibatasi per peran. Berkas unggahan disimpan di luar folder `public` dan hanya bisa dibuka lewat controller dengan pemeriksaan hak akses.
- **Laporan PDF/XLSX** dibuat tanpa pustaka tambahan, menggunakan `app/lib/PdfSederhana.php` dan `app/lib/XlsxSederhana.php`.

---

## 9. Jika ada kendala

| Gejala | Solusi |
|---|---|
| Muncul halaman **"Database belum siap"** | Pastikan MySQL sudah berjalan (Laragon: **Start All**; macOS: `brew services start mysql`), `schema.sql` lalu `seed.sql` sudah diimpor, dan isi `config/config.php` sesuai. |
| Apache tidak mau start (port 80 dipakai) | Tutup aplikasi lain yang memakai port 80 (Skype/IIS/XAMPP lama), atau di Laragon buka **Menu → Preferences → Services & Ports** dan ganti port Apache, misalnya 8080. |
| MySQL tidak mau start (port 3306 dipakai) | Hentikan layanan MySQL lain, atau ganti port MySQL (Laragon: Services & Ports) lalu samakan di `config/config.php`. |
| Muncul "melebihi batas upload_max_filesize" | Laragon: **Menu → PHP → php.ini**, set `upload_max_filesize=10M` dan `post_max_size=40M`, lalu Reload. macOS: jalankan `php -S` dengan opsi `-d` seperti di Cara B. |
| Muncul "Permintaan tidak valid" | Sesi kedaluwarsa. Muat ulang halaman lalu kirim lagi. |
| Unggahan gagal disimpan (macOS/Linux) | Jalankan `chmod -R 777 storage` dari dalam folder `filkompanion`. |
| Ingin mengulang data dari awal | Impor ulang `database/seed.sql`, lalu kosongkan `storage/notifications.json` menjadi `[]`. |

---

## 10. Hosting online (opsional, untuk demo)

Ringkas: buat akun hosting PHP + MySQL gratis (mis. InfinityFree), buat database, impor
`database/hosting/schema_hosting.sql` lalu `database/hosting/seed_hosting.sql` (versi tanpa `CREATE DATABASE/USE`),
unggah seluruh isi folder `filkompanion` ke `htdocs`, lalu sesuaikan `config/config.php` dengan
host/nama database/user/password dari panel hosting. Situs dibuka di `https://namasubdomain…/`
(otomatis diarahkan ke `public/`).

> Peringatan: login UB di aplikasi ini masih simulasi (tanpa password). Jika di-hosting publik, siapa pun
> yang tahu link bisa masuk sebagai akun demo, termasuk Admin. Bagikan link hanya ke asisten/dosen, jangan isi data asli,
> dan impor ulang `seed_hosting.sql` bila data demo berantakan.
