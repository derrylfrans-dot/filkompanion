<?php
// Induk semua controller
abstract class Controller
{
    // Tampilkan view
    protected function render(string $boundary, array $data = []): void
    {
        $pengguna = Auth::pengguna();
        $data['pengguna'] = $pengguna;
        $data['flash'] = $this->ambilFlash();
        if ($pengguna) {
            $notif = new NotificationService(new UserDB());
            $data['notifikasi'] = $notif->ambilNotifikasi($pengguna['userId']);
            $data['statusRelawanLogin'] = $this->statusRelawanLogin($pengguna);
        }
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../boundary/' . $boundary . '.php';
    }

    protected function redirect(string $rute, array $param = []): void
    {
        header('Location: ' . url($rute, $param));
        exit;
    }

    protected function setFlash(string $tipe, string $judul, string $pesan = ''): void
    {
        $_SESSION['flash'] = ['tipe' => $tipe, 'judul' => $judul, 'pesan' => $pesan];
    }

    private function ambilFlash(): ?array
    {
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }

    // Simpan isian form
    protected function simpanIsianLama(array $isian): void
    {
        $_SESSION['isianLama'] = $isian;
    }

    protected function ambilIsianLama(): array
    {
        $isian = $_SESSION['isianLama'] ?? [];
        unset($_SESSION['isianLama']);
        return $isian;
    }

    // Cek login & peran
    protected function wajibPeran(array $peranDiizinkan): array
    {
        $p = Auth::pengguna();
        if (!$p) {
            $this->setFlash('info', 'Silakan masuk terlebih dahulu', 'Gunakan akun email UB untuk mengakses halaman ini.');
            $this->redirect('login');
        }
        if (!in_array($p['rolePengguna'], $peranDiizinkan, true)) {
            http_response_code(403);
            $this->render('Akses_Ditolak', ['judulHalaman' => 'Akses ditolak']);
            exit;
        }
        return $p;
    }

    // Cek POST & CSRF
    protected function wajibPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $this->setFlash('error', 'Total ukuran berkas terlalu besar',
                'Melebihi batas post_max_size server (' . ini_get('post_max_size') . '). Kurangi jumlah/ukuran berkas.');
            $this->redirect(Auth::pengguna() ? 'beranda' : 'login');
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::cekCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            exit('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
        }
    }

    protected function input(string $kunci, $bawaan = '')
    {
        $nilai = $_POST[$kunci] ?? $_GET[$kunci] ?? $bawaan;
        return is_string($nilai) ? trim($nilai) : $nilai;
    }

    // Status relawan
    private function statusRelawanLogin(array $pengguna): ?string
    {
        if ($pengguna['rolePengguna'] !== 'Relawan Pendamping') {
            return null;
        }
        $relawan = (new VolunteerDB())->ambilBerdasarkanUser($pengguna['userId']);
        return $relawan['statusPendaftaran'] ?? 'Belum Dikirim';
    }
}
