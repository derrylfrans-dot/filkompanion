<?php
// Front controller
declare(strict_types=1);

$cfg = require __DIR__ . '/../config/config.php';
date_default_timezone_set($cfg['app']['timezone']);

spl_autoload_register(function (string $kelas): void {
    foreach (['core', 'controller', 'entity', 'service', 'lib'] as $folder) {
        $berkas = __DIR__ . '/../app/' . $folder . '/' . $kelas . '.php';
        if (is_file($berkas)) {
            require $berkas;
            return;
        }
    }
});
require __DIR__ . '/../app/core/helpers.php';

Auth::mulaiSesi();
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// Daftar rute
$rute = [
    // Akun dan profil
    'login'                    => ['AuthController', 'membukaHalamanLogin'],
    'login/submit'             => ['AuthController', 'prosesLogin'],
    'logout'                   => ['AuthController', 'logout'],
    'beranda'                  => ['AuthController', 'menampilkanDashboard'],
    'bantuan'                  => ['AuthController', 'bantuan'],
    'notifikasi/baca'          => ['AuthController', 'tandaiNotifikasiDibaca'],
    'profil'                   => ['ProfileController', 'membukaMenuProfil'],
    'profil/simpan'            => ['ProfileController', 'submitPerubahanProfil'],
    'relawan/daftar'           => ['VolunteerController', 'membukaPendaftaranRelawan'],
    'relawan/daftar/kirim'     => ['VolunteerController', 'mengirimPendaftaran'],
    'relawan/profil'           => ['VolunteerController', 'membukaProfilDanKetersediaan'],
    'relawan/profil/simpan'    => ['VolunteerController', 'submitPerubahanRelawan'],
    'relawan/dokumen'          => ['VolunteerController', 'lihatDokumen'],
    'admin/relawan'            => ['VolunteerController', 'membukaMenuValidasiRelawan'],
    'admin/relawan/keputusan'  => ['VolunteerController', 'mengirimKeputusanValidasi'],
    // Tiket
    'tiket/ajukan'             => ['TicketController', 'membukaMenuPengajuanTiket'],
    'tiket/kirim'              => ['TicketController', 'mengirimPengajuan'],
    'tiket'                    => ['TicketController', 'membukaDaftarTiket'],
    'tiket/batal'              => ['TicketController', 'konfirmasiPembatalan'],
    'admin/tiket'              => ['TicketController', 'membukaMenuVerifikasiTiket'],
    'admin/tiket/keputusan'    => ['TicketController', 'simpanKeputusanTiket'],
    'admin/tiket-manual'       => ['TicketController', 'membukaMenuTiketManual'],
    'admin/tiket-manual/kirim' => ['TicketController', 'submitTiketManual'],
    // Penugasan relawan
    'penugasan'                => ['AssignmentController', 'membukaDaftarPenugasan'],
    'penugasan/terima'         => ['AssignmentController', 'konfirmasiPenerimaan'],
    'penugasan/tolak'          => ['MatchingController', 'menolakPenugasan'],
    'penugasan/batal'          => ['AssignmentController', 'submitPembatalanDarurat'],
    // Pelaksanaan pendampingan
    'bukti'                    => ['ActivityController', 'membukaTiketYangTelahDilaksanakan'],
    'bukti/unggah'             => ['ActivityController', 'mengunggahFotoBukti'],
    'bukti/hapus'              => ['ActivityController', 'hapusBukti'],
    'bukti/selesai'            => ['ActivityController', 'memilihTombolSelesai'],
    'bukti/lihat'              => ['ActivityController', 'lihatBukti'],
    'konfirmasi'               => ['VerificationController', 'membukaDetailTiketSelesai'],
    'konfirmasi/simpan'        => ['VerificationController', 'simpanKonfirmasiMahasiswa'],
    'kendala/lapor'            => ['IssueController', 'membukaFormLaporanKendala'],
    'kendala/kirim'            => ['IssueController', 'mengirimLaporan'],
    'kendala/lampiran'         => ['IssueController', 'lihatLampiran'],
    // Jam dan laporan
    'riwayat'                  => ['HistoryController', 'membukaMenuRiwayatPengabdian'],
    'admin/kendala'            => ['IssueController', 'membukaDaftarLaporan'],
    'admin/kendala/simpan'     => ['IssueController', 'menentukanTindakLanjut'],
    'admin/jam'                => ['HoursController', 'membukaMenuValidasiJam'],
    'admin/jam/validasi'       => ['HoursController', 'mengonfirmasiValidasi'],
    'admin/jam/tinjau'         => ['HoursController', 'menandaiDataDitinjauKembali'],
    'admin/laporan'            => ['ReportController', 'membukaMenuLaporanRekapitulasi'],
    'admin/laporan/unduh'      => ['ReportController', 'memilihCetakAtauUnduh'],
];

$r = is_string($_GET['r'] ?? null) ? trim($_GET['r'], '/') : '';
if ($r === '') {
    $r = Auth::pengguna() ? 'beranda' : 'login';
}

try {
    if (!isset($rute[$r])) {
        http_response_code(404);
        require __DIR__ . '/../app/boundary/Halaman_Tidak_Ditemukan.php';
        exit;
    }
    [$kelas, $method] = $rute[$r];
    (new $kelas())->$method();
} catch (PDOException $ex) {
    http_response_code(500);
    $pesanError = $ex->getMessage();
    require __DIR__ . '/../app/boundary/Galat_Database.php';
}
