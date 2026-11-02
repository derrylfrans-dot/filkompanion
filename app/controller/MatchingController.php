<?php
// Pencocokan relawan
class MatchingController extends Controller
{
    private TicketDB $ticketDB;
    private VolunteerDB $volunteerDB;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->ticketDB = new TicketDB();
        $this->volunteerDB = new VolunteerDB();
        $this->notificationService = new NotificationService(new UserDB());
    }

    // Proses antrean tiket
    public function prosesAntreanPencarian(): void
    {
        foreach ($this->ticketDB->ambilAntreanPencarian() as $tiket) {
            $this->mencariKecocokan($tiket);
        }
    }

    // Cari relawan cocok
    public function mencariKecocokan(array $tiket): array
    {
        if (empty($tiket) || $tiket['status'] !== 'Tervalidasi') {
            return ['status' => 'Gagal', 'pesan' => 'Hanya tiket tervalidasi yang dapat dicocokkan'];
        }
        $kandidatRelawan = $this->memeriksaKeahlianDanKetersediaan($tiket['idTiket']);
        $daftarSesuai = [];
        foreach ($kandidatRelawan as $relawan) {
            if ($relawan['statusRelawan'] === 'Aktif'
                && $this->volunteerDB->cekKetersediaan($relawan['idRelawan'], $tiket) === true) {
                $daftarSesuai[] = $relawan;
            }
        }
        if (count($daftarSesuai) === 0) {
            return ['status' => 'Belum Ada Kandidat', 'idTiket' => $tiket['idTiket']];
        }
        // Beban paling sedikit dulu
        usort($daftarSesuai, function ($a, $b) {
            $bebanA = count($this->ticketDB->ambilPenugasanRelawan($a['idRelawan']));
            $bebanB = count($this->ticketDB->ambilPenugasanRelawan($b['idRelawan']));
            return [$bebanA, count($a['keahlianList']), $a['idRelawan']]
                <=> [$bebanB, count($b['keahlianList']), $b['idRelawan']];
        });
        $calonRelawan = $daftarSesuai[0];
        if (!$this->ticketDB->tetapkanKandidat($tiket['idTiket'], $calonRelawan['idRelawan'])) {
            return ['status' => 'Gagal', 'pesan' => 'Kandidat tidak dapat ditetapkan'];
        }
        $this->kirimNotifikasiPenugasan($calonRelawan['userId'], $tiket['idTiket']);
        return ['status' => 'Notifikasi Terkirim', 'idTiket' => $tiket['idTiket'], 'calonRelawan' => $calonRelawan];
    }

    // Kandidat sesuai keahlian
    public function memeriksaKeahlianDanKetersediaan(string $idTiket): array
    {
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        if (!$tiket) {
            return [];
        }
        $dikecualikan = $this->ticketDB->relawanTidakTersedia($idTiket);
        return array_values(array_filter(
            $this->volunteerDB->cariRelawanSesuaiSkill($tiket),
            fn ($r) => !in_array($r['idRelawan'], $dikecualikan, true)
        ));
    }

    // Kirim notifikasi
    public function kirimNotifikasiPenugasan(string $userIdRelawan, string $idTiket): void
    {
        $this->notificationService->kirimNotifikasiPenugasan($userIdRelawan, $idTiket);
    }

    // Tolak penugasan
    public function menolakPenugasan(): void
    {
        $p = $this->wajibPeran(['Relawan Pendamping']);
        $this->wajibPost();
        $relawan = $this->volunteerDB->ambilBerdasarkanUser($p['userId']);
        $idTiket = (string) $this->input('idTiket');
        $hasil = $relawan ? $this->catatPenolakan($idTiket, $relawan['idRelawan']) : ['status' => 'Gagal'];
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('info', 'Penugasan ' . $idTiket . ' ditolak', 'Tiket dikembalikan ke antrean pencarian relawan.');
        } else {
            $this->setFlash('error', 'Penolakan tidak dapat diproses', $hasil['pesan'] ?? '');
        }
        $this->redirect('penugasan');
    }

    // Catat penolakan
    public function catatPenolakan(string $idTiket, string $idRelawan): array
    {
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        if (!$tiket || $tiket['idRelawan'] !== $idRelawan || $tiket['status'] !== 'Menunggu Respons') {
            return ['status' => 'Gagal', 'pesan' => 'Penugasan tidak lagi menunggu responsmu'];
        }
        $this->ticketDB->catatRiwayat($idTiket, 'Penugasan Ditolak', $idRelawan);
        $this->ticketDB->kembalikanKeStatusTervalidasi($idTiket, 'SISTEM', 'Kembali ke antrean pencarian');
        $this->mencariKecocokan($this->ticketDB->ambilDetail($idTiket));
        return ['status' => 'Berhasil', 'idTiket' => $idTiket];
    }
}
