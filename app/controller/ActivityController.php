<?php
// Bukti dan penyelesaian
class ActivityController extends Controller
{
    private const FORMAT_FOTO = ['jpg', 'jpeg', 'png'];

    private TicketDB $ticketDB;
    private VolunteerHoursDB $volunteerHoursDB;

    public function __construct()
    {
        $this->ticketDB = new TicketDB();
        $this->volunteerHoursDB = new VolunteerHoursDB();
    }

    // Buka tiket
    public function membukaTiketYangTelahDilaksanakan(): void
    {
        $relawan = $this->relawanAktif();
        $this->ticketDB->sinkronStatusPelaksanaan();
        $idTiket = (string) $this->input('id');
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        if (!$tiket || $tiket['idRelawan'] !== $relawan['idRelawan']) {
            $this->setFlash('error', 'Tiket tidak ditemukan pada penugasanmu');
            $this->redirect('penugasan');
        }
        $this->render('PLS-UI01_ProofUI', [
            'judulHalaman'     => 'Selesaikan Tiket Pendampingan',
            'menuAktif'        => 'penugasan',
            'tiket'            => $tiket,
            'statusPelaksanaan'=> $this->memintaFiturUnggahBukti($idTiket),
            'kesiapan'         => $this->cekKesiapanTiket($idTiket),
            'durasi'           => round((strtotime($tiket['waktuSelesai']) - strtotime($tiket['waktuMulai'])) / 3600, 2),
        ]);
    }

    // Cek status
    public function memintaFiturUnggahBukti(string $idTiket): array
    {
        $statusPelaksanaan = $this->ticketDB->cekStatusTiket($idTiket);
        return ['statusPelaksanaan' => $statusPelaksanaan, 'formBukti' => $statusPelaksanaan === 'Dilaksanakan'];
    }

    // Unggah bukti
    public function mengunggahFotoBukti(): void
    {
        $relawan = $this->relawanAktif();
        $this->wajibPost();
        $idTiket = (string) $this->input('idTiket');
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        if (!$tiket || $tiket['idRelawan'] !== $relawan['idRelawan']) {
            $this->setFlash('error', 'Tiket tidak ditemukan pada penugasanmu');
            $this->redirect('penugasan');
        }
        $fileFoto = Upload::daftarBerkas($_FILES['fileFoto'] ?? null)[0] ?? null;
        $hasil = $tiket['buktiFoto']
            ? $this->submitUlangBukti($fileFoto, $idTiket)
            : $this->submitBuktiKegiatan($fileFoto, $idTiket);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Bukti berhasil diunggah', 'Pilih Selesaikan Tiket untuk mencatat durasi pengabdian.');
        } else {
            $this->setFlash('error', $hasil['pesan']);
        }
        $this->redirect('bukti', ['id' => $idTiket]);
    }

    // Proses unggah
    public function submitBuktiKegiatan(?array $fileFoto, string $idTiket): array
    {
        $statusPelaksanaan = $this->ticketDB->cekStatusTiket($idTiket);
        if ($statusPelaksanaan !== 'Dilaksanakan') {
            return ['status' => 'Gagal', 'pesan' => 'Tiket belum dilaksanakan'];
        }
        if ($fileFoto === null) {
            return ['status' => 'Gagal', 'pesan' => 'Pilih foto bukti kegiatan terlebih dahulu'];
        }
        $memenuhi = $this->periksaUnggahan($fileFoto);
        if ($memenuhi !== true) {
            return ['status' => 'Gagal', 'pesan' => 'Unggahan tidak memenuhi ketentuan, unggah ulang bukti yang sesuai. ' . $memenuhi];
        }
        $nama = Upload::simpan($fileFoto, 'bukti', $idTiket . '_' . date('YmdHis'));
        $buktiTersimpan = $this->ticketDB->simpanBuktiFoto($nama, $idTiket);
        if ($buktiTersimpan === null) {
            return ['status' => 'Gagal', 'pesan' => 'Bukti tidak berhasil disimpan'];
        }
        return ['status' => 'Berhasil', 'buktiTersimpan' => $buktiTersimpan];
    }

    // Ganti bukti
    public function submitUlangBukti(?array $fileFoto, string $idTiket): array
    {
        $lama = $this->ticketDB->ambilDetail($idTiket)['buktiFoto'] ?? null;
        $hasil = $this->submitBuktiKegiatan($fileFoto, $idTiket);
        if ($hasil['status'] === 'Berhasil') {
            Upload::hapus('bukti', $lama);
        }
        return $hasil;
    }

    // Cek format foto
    public function periksaUnggahan(array $fileFoto)
    {
        $pesan = Upload::periksa($fileFoto, self::FORMAT_FOTO);
        return $pesan === null ? true : $pesan;
    }

    // Hapus bukti
    public function hapusBukti(): void
    {
        $relawan = $this->relawanAktif();
        $this->wajibPost();
        $idTiket = (string) $this->input('idTiket');
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        if ($tiket && $tiket['idRelawan'] === $relawan['idRelawan'] && $this->ticketDB->hapusBuktiFoto($idTiket)) {
            Upload::hapus('bukti', $tiket['buktiFoto']);
            $this->setFlash('info', 'Bukti dihapus', 'Unggah foto bukti yang sesuai sebelum menyelesaikan tiket.');
        }
        $this->redirect('bukti', ['id' => $idTiket]);
    }

    // Cek kesiapan
    public function cekKesiapanTiket(string $idTiket): array
    {
        $statusBukti = $this->ticketDB->cekKeberadaanBukti($idTiket);
        $status = $this->ticketDB->cekStatusTiket($idTiket);
        return ['statusBukti' => $statusBukti, 'siap' => $status === 'Dilaksanakan' && $statusBukti === 'Ada'];
    }

    // Selesaikan tiket
    public function memilihTombolSelesai(): void
    {
        $relawan = $this->relawanAktif();
        $this->wajibPost();
        $idTiket = (string) $this->input('idTiket');
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        if (!$tiket || $tiket['idRelawan'] !== $relawan['idRelawan']) {
            $this->setFlash('error', 'Tiket tidak ditemukan pada penugasanmu');
            $this->redirect('penugasan');
        }
        $hasil = $this->submitPenyelesaian($idTiket);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Tiket ' . $idTiket . ' selesai',
                'Durasi ' . angka($hasil['durasiTercatat']) . ' jam tercatat dengan status Menunggu Verifikasi.');
        } else {
            $this->setFlash('error', $hasil['pesan']);
        }
        $this->redirect('bukti', ['id' => $idTiket]);
    }

    // Proses penyelesaian
    public function submitPenyelesaian(string $idTiket): array
    {
        $statusPelaksanaan = $this->ticketDB->cekStatusTiket($idTiket);
        if ($statusPelaksanaan !== 'Dilaksanakan') {
            return ['status' => 'Gagal', 'pesan' => 'Tiket belum dilaksanakan'];
        }
        $statusBukti = $this->ticketDB->cekKeberadaanBukti($idTiket);
        if ($statusBukti === 'Tidak Ada') {
            return ['status' => 'Gagal', 'pesan' => 'Bukti kegiatan belum tersedia, unggah bukti terlebih dahulu'];
        }
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        $statusSelesai = $this->ticketDB->ubahStatusSelesai($idTiket, $tiket['idRelawan']);
        if ($statusSelesai !== 'Selesai') {
            return ['status' => 'Gagal', 'pesan' => 'Status tiket tidak berhasil diubah'];
        }
        $durasiTercatat = $this->volunteerHoursDB->catatDurasi($idTiket);
        (new NotificationService(new UserDB()))->kirimNotifikasiStatus($idTiket, 'Selesai', $tiket['idMahasiswa'],
            'Relawan menyelesaikan pendampingan. Mohon konfirmasi kehadiran.');
        return ['status' => 'Berhasil', 'statusSelesai' => $statusSelesai, 'durasiTercatat' => $durasiTercatat];
    }

    // Tampilkan foto bukti
    public function lihatBukti(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas', 'Relawan Pendamping', 'Admin FILKOM']);
        $tiket = $this->ticketDB->ambilDetail((string) $this->input('id'));
        $boleh = $tiket && $tiket['buktiFoto'] && ($p['rolePengguna'] === 'Admin FILKOM'
            || $tiket['idMahasiswa'] === $p['userId'] || $tiket['userIdRelawan'] === $p['userId']);
        if (!$boleh) {
            http_response_code(404);
            exit('Bukti tidak ditemukan.');
        }
        Upload::kirim('bukti', $tiket['buktiFoto'], 'bukti_' . $tiket['idTiket'] . '.' . pathinfo($tiket['buktiFoto'], PATHINFO_EXTENSION));
    }

    private function relawanAktif(): array
    {
        $p = $this->wajibPeran(['Relawan Pendamping']);
        $relawan = (new VolunteerDB())->ambilBerdasarkanUser($p['userId']);
        if (!$relawan || $relawan['statusPendaftaran'] !== 'Aktif') {
            $this->redirect('relawan/daftar');
        }
        return $relawan;
    }
}
