<?php
// Riwayat pengabdian
class HistoryController extends Controller
{
    private TicketDB $ticketDB;
    private VolunteerHoursDB $volunteerHoursDB;

    public function __construct()
    {
        $this->ticketDB = new TicketDB();
        $this->volunteerHoursDB = new VolunteerHoursDB();
    }

    // Halaman riwayat
    public function membukaMenuRiwayatPengabdian(): void
    {
        $p = $this->wajibPeran(['Relawan Pendamping']);
        $relawan = (new VolunteerDB())->ambilBerdasarkanUser($p['userId']);
        if (!$relawan || $relawan['statusPendaftaran'] !== 'Aktif') {
            $this->redirect('relawan/daftar');
        }
        $daftarPeriode = $this->volunteerHoursDB->ambilDaftarPeriode($relawan['idRelawan']);
        $periode = (string) $this->input('periode', $daftarPeriode[0]);
        if (!in_array($periode, $daftarPeriode, true)) {
            $periode = $daftarPeriode[0];
        }
        $data = $this->mintaDataRiwayat($relawan['idRelawan'], $periode);
        $this->render('SKM-UI01_HistoryUI', [
            'judulHalaman'  => 'Riwayat dan Total Jam Pengabdian',
            'menuAktif'     => 'riwayat',
            'riwayatTiket'  => $data['riwayatTiket'],
            'totalJam'      => $data['totalJam'],
            'periode'       => $periode,
            'daftarPeriode' => $daftarPeriode,
            'jumlahSemua'   => count($this->ticketDB->ambilRiwayatPenugasan($relawan['idRelawan'])),
        ]);
    }

    // Ambil riwayat
    public function mintaDataRiwayat(string $idRelawan, string $periode = ''): array
    {
        if ($idRelawan === '') {
            return ['status' => 'Gagal', 'pesan' => 'ID relawan tidak valid'];
        }
        $riwayatTiket = $this->ticketDB->ambilRiwayatPenugasan($idRelawan, $periode);
        $totalJam = $this->volunteerHoursDB->ambilTotalJamPengabdian($idRelawan, $periode);
        if ($riwayatTiket === null) {
            $riwayatTiket = [];
        }
        if ($totalJam === null) {
            $totalJam = ['total' => 0, 'terverifikasi' => 0, 'menunggu' => 0, 'perluDitinjau' => 0];
        }
        return ['status' => 'Berhasil', 'riwayatTiket' => $riwayatTiket, 'totalJam' => $totalJam];
    }
}
