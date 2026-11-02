<?php
// Penugasan relawan
class AssignmentController extends Controller
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

    // Daftar penugasan
    public function membukaDaftarPenugasan(): void
    {
        $relawan = $this->relawanAktif();
        $this->ticketDB->sinkronStatusPelaksanaan();
        (new MatchingController())->prosesAntreanPencarian();
        $daftar = $this->ticketDB->ambilPenugasanRelawan($relawan['idRelawan']);
        $idDipilih = (string) $this->input('id', $daftar[0]['idTiket'] ?? '');
        $detail = $idDipilih ? $this->ambilDetailPenugasan($idDipilih) : null;
        if ($detail && $detail['idRelawan'] !== $relawan['idRelawan']) {
            $detail = null;
        }
        $this->render('PSG-UI01_AssignmentUI', [
            'judulHalaman' => 'Penugasan',
            'menuAktif'    => 'penugasan',
            'menunggu'     => array_values(array_filter($daftar, fn ($t) => $t['status'] === 'Menunggu Respons')),
            'aktif'        => array_values(array_filter($daftar, fn ($t) => $t['status'] !== 'Menunggu Respons')),
            'detail'       => $detail,
            'modeBatal'    => $this->input('aksi') === 'batal',
        ]);
    }

    // Detail penugasan
    public function ambilDetailPenugasan(string $idTiket): ?array
    {
        return $this->ticketDB->ambilDetailPenugasan($idTiket);
    }

    // Terima penugasan
    public function konfirmasiPenerimaan(): void
    {
        $relawan = $this->relawanAktif();
        $this->wajibPost();
        $idTiket = (string) $this->input('idTiket');
        $hasil = $this->terimaPenugasan($idTiket, $relawan['idRelawan']);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Penugasan ' . $idTiket . ' diterima', 'Jadwalmu telah dikunci untuk sesi ini.');
        } else {
            $this->setFlash('error', $hasil['pesan']);
        }
        $this->redirect('penugasan', ['id' => $idTiket]);
    }

    // Proses penerimaan
    public function terimaPenugasan(string $idTiket, string $idRelawan): array
    {
        if ($idTiket === '' || $idRelawan === '') {
            return ['status' => 'Gagal', 'pesan' => 'Data penugasan tidak lengkap'];
        }
        $detailPenugasan = $this->ambilDetailPenugasan($idTiket);
        if ($detailPenugasan === null || !in_array($detailPenugasan['status'], ['Tervalidasi', 'Menunggu Respons'], true)
            || ($detailPenugasan['idRelawan'] !== null && $detailPenugasan['idRelawan'] !== $idRelawan)) {
            return ['status' => 'Tidak Tersedia', 'pesan' => 'Penugasan telah berubah atau sudah diterima relawan lain'];
        }
        if ($this->volunteerDB->cekKetersediaan($idRelawan, $detailPenugasan) === false) {
            return ['status' => 'Gagal', 'pesan' => 'Relawan tidak tersedia pada jadwal tersebut'];
        }
        $statusKunci = $this->kunciJadwal($idRelawan, $detailPenugasan);
        if ($statusKunci === false) {
            return ['status' => 'Gagal', 'pesan' => 'Jadwal tidak dapat dikunci'];
        }
        $this->ticketDB->ubahStatusPenugasan($idTiket, 'Teralokasi', $idRelawan);
        $this->notificationService->kirimKonfirmasiPenjadwalan(
            $detailPenugasan['idMahasiswa'],
            $idTiket,
            $this->volunteerDB->ambilKeahlianDanKetersediaan($idRelawan)['nama']
        );
        return ['status' => 'Berhasil', 'idTiket' => $idTiket, 'idRelawan' => $idRelawan, 'statusTiket' => 'Teralokasi'];
    }

    // Kunci jadwal
    public function kunciJadwal(string $idRelawan, array $jadwal): bool
    {
        return $this->volunteerDB->kunciJadwal($idRelawan, $jadwal);
    }

    // Batal darurat
    public function submitPembatalanDarurat(): void
    {
        $relawan = $this->relawanAktif();
        $this->wajibPost();
        $idTiket = (string) $this->input('idTiket');
        $hasil = $this->batalkanPenugasanDarurat($idTiket, trim((string) $this->input('alasan')), $relawan['idRelawan']);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Pembatalan darurat tercatat', $hasil['pesan']);
            $this->redirect('penugasan');
        }
        $this->setFlash('error', 'Pembatalan belum diproses', $hasil['pesan']);
        $this->redirect('penugasan', ['id' => $idTiket, 'aksi' => 'batal']);
    }

    // Proses pembatalan darurat
    public function batalkanPenugasanDarurat(string $idTiket, string $alasan, string $idRelawan): array
    {
        if ($idTiket === '' || $alasan === '') {
            return ['status' => 'Gagal', 'pesan' => 'ID tiket dan alasan pembatalan wajib diisi'];
        }
        $detailPenugasan = $this->ambilDetailPenugasan($idTiket);
        if ($detailPenugasan === null || $detailPenugasan['status'] !== 'Teralokasi'
            || $detailPenugasan['idRelawan'] !== $idRelawan) {
            return ['status' => 'Ditolak', 'pesan' => 'Tidak terdapat penugasan aktif yang dapat dibatalkan'];
        }
        // Catat alasan
        $this->ticketDB->catatRiwayat($idTiket, 'Pembatalan Darurat', $idRelawan, mb_substr($alasan, 0, 255));
        $this->volunteerDB->lepasKunciJadwal($idRelawan, $idTiket);
        $statusAntrean = $this->kembalikanKeAntrean($idTiket);
        if ($statusAntrean !== 'Tervalidasi') {
            return ['status' => 'Gagal', 'pesan' => 'Tiket perlu ditinjau Admin FILKOM'];
        }
        $this->notificationService->kirimNotifikasiStatus($idTiket, 'Dicarikan relawan pengganti',
            $detailPenugasan['idMahasiswa'], 'Relawan sebelumnya membatalkan karena kondisi darurat.');
        (new MatchingController())->mencariKecocokan($this->ticketDB->ambilDetail($idTiket));
        return ['status' => 'Berhasil', 'idTiket' => $idTiket, 'statusTiket' => 'Tervalidasi',
            'pesan' => 'Tiket kembali ke pencarian relawan'];
    }

    // Kembali ke antrean
    public function kembalikanKeAntrean(string $idTiket): string
    {
        $this->ticketDB->kembalikanKeStatusTervalidasi($idTiket, 'SISTEM', 'Kembali ke antrean pencarian');
        return (string) $this->ticketDB->cekStatusTiket($idTiket);
    }

    private function relawanAktif(): array
    {
        $p = $this->wajibPeran(['Relawan Pendamping']);
        $relawan = $this->volunteerDB->ambilBerdasarkanUser($p['userId']);
        if (!$relawan || $relawan['statusPendaftaran'] !== 'Aktif') {
            $this->setFlash('info', 'Menu aktif setelah validasi', 'Penugasan tersedia setelah pendaftaran divalidasi Admin FILKOM.');
            $this->redirect('relawan/daftar');
        }
        return $relawan;
    }
}
