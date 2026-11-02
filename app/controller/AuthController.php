<?php
// Login dan beranda
class AuthController extends Controller
{
    private UserDB $userDB;
    private UBAuthService $ubAuthService;

    public function __construct()
    {
        $this->userDB = new UserDB();
        $this->ubAuthService = new UBAuthService();
    }

    // Halaman login
    public function membukaHalamanLogin(): void
    {
        if (Auth::pengguna()) {
            $this->redirect('beranda');
        }
        $this->render('AKN-UI01_LoginUI', [
            'judulHalaman' => 'Masuk',
            'isian'        => $this->ambilIsianLama(),
        ]);
    }

    // Kirim login
    public function prosesLogin(): void
    {
        $this->wajibPost();
        $emailUB = (string) $this->input('emailUB');
        // Token pengganti sandi
        $kredensial = ['metode' => 'google-workspace-ub', 'token' => $_POST['_csrf'] ?? ''];

        $respons = $this->submitLogin($emailUB, $kredensial);
        if ($respons['status'] !== 'Berhasil') {
            $this->simpanIsianLama(['emailUB' => $emailUB]);
            $this->setFlash('error', 'Autentikasi gagal', $respons['pesan']);
            $this->redirect('login');
        }
        Auth::masuk($this->userDB->ambilBerdasarkanEmail($emailUB));
        $this->arahkanKeDashboard($respons['rolePengguna']);
    }

    // Proses login
    public function submitLogin(string $emailUB, array $kredensial): array
    {
        if (trim($emailUB) === '' || empty($kredensial)) {
            return ['status' => 'Gagal', 'pesan' => 'Email UB dan kredensial wajib diisi.'];
        }
        $hasilAutentikasi = $this->autentikasiAkunUB($emailUB, $kredensial);
        if (!$hasilAutentikasi['valid']) {
            return ['status' => 'Gagal', 'pesan' => 'Autentikasi akun UB gagal. ' . $hasilAutentikasi['pesan']];
        }
        $rolePengguna = $this->ambilPeranPengguna($hasilAutentikasi['emailUB']);
        if ($rolePengguna === null) {
            return ['status' => 'Gagal', 'pesan' => 'Peran pengguna tidak ditemukan. Akun UB ini belum terdaftar di FILKOMpanion.'];
        }
        return ['status' => 'Berhasil', 'rolePengguna' => $rolePengguna, 'dashboard' => 'beranda'];
    }

    // Autentikasi UB
    public function autentikasiAkunUB(string $emailUB, array $kredensial): array
    {
        return $this->ubAuthService->autentikasiAkunUB($emailUB, $kredensial);
    }

    // Ambil peran
    public function ambilPeranPengguna(string $emailUB): ?string
    {
        return $this->userDB->ambilPeranPengguna(strtolower(trim($emailUB)));
    }

    // Arahkan ke beranda
    private function arahkanKeDashboard(string $rolePengguna): void
    {
        $this->setFlash('sukses', 'Berhasil masuk', 'Kamu masuk sebagai ' . $rolePengguna . '.');
        $this->redirect('beranda');
    }

    // Tampilkan beranda
    public function menampilkanDashboard(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas', 'Relawan Pendamping', 'Admin FILKOM']);
        $ticketDB = new TicketDB();
        $ticketDB->sinkronStatusPelaksanaan();
        $data = ['judulHalaman' => 'Beranda', 'menuAktif' => 'beranda'];

        if ($p['rolePengguna'] === 'Mahasiswa Disabilitas') {
            $tiket = $ticketDB->ambilTiketMahasiswa($p['userId']);
            $data['ringkasan'] = $ticketDB->hitungStatus($p['userId']);
            $data['profil'] = $this->userDB->ambilProfilMahasiswa($p['userId']);
            $data['tiketMendatang'] = array_reverse(array_values(array_filter($tiket, fn ($t) =>
                strtotime($t['waktuMulai']) >= time() && in_array($t['status'], TicketDB::STATUS_DAPAT_DIBATALKAN, true))));
            $data['perluKonfirmasi'] = array_values(array_filter($tiket, fn ($t) =>
                $t['status'] === 'Selesai' && !$t['konfirmasiMahasiswa']));
        } elseif ($p['rolePengguna'] === 'Relawan Pendamping') {
            $relawan = (new VolunteerDB())->ambilBerdasarkanUser($p['userId']);
            $data['relawan'] = $relawan;
            if ($relawan && $relawan['statusPendaftaran'] === 'Aktif') {
                (new MatchingController())->prosesAntreanPencarian();
                $data['penugasan'] = $ticketDB->ambilPenugasanRelawan($relawan['idRelawan']);
                $data['jam'] = (new VolunteerHoursDB())->ambilTotalJamPengabdian($relawan['idRelawan']);
            }
        } else {
            $data['ringkasan'] = $ticketDB->hitungStatus();
            $data['pendaftaran'] = (new VolunteerDB())->hitungStatusPendaftaran();
            $data['laporanTerbuka'] = count(array_filter((new IssueDB())->ambilLaporanDanTiketTerkait(),
                fn ($l) => $l['statusTindakLanjut'] === 'Belum Ditindaklanjuti'));
            $data['jamMenunggu'] = count(array_filter(
                (new VolunteerHoursDB())->ambilAkumulasiJamPerRelawan(periodeDariTanggal(date('Y-m-d'))),
                fn ($r) => $r['status'] !== 'Terverifikasi'
            ));
        }
        $this->render('Beranda', $data);
    }

    // Tandai notifikasi dibaca
    public function tandaiNotifikasiDibaca(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas', 'Relawan Pendamping', 'Admin FILKOM']);
        $this->wajibPost();
        (new NotificationService($this->userDB))->tandaiDibaca($p['userId']);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
    }

    public function bantuan(): void
    {
        $this->render('Bantuan', ['judulHalaman' => 'Bantuan & Aksesibilitas', 'menuAktif' => '']);
    }

    public function logout(): void
    {
        $this->wajibPost();
        Auth::keluar();
        Auth::mulaiSesi();
        $this->setFlash('info', 'Kamu sudah keluar', 'Sampai jumpa lagi di FILKOMpanion.');
        $this->redirect('login');
    }
}
