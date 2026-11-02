<?php
// Pengelolaan tiket
class TicketController extends Controller
{
    private const MAKS_TIKET_BERULANG = 20;

    private TicketDB $ticketDB;
    private KRSData $krsData;
    private NotificationService $notificationService;
    private int $batasBatalJam;

    public function __construct()
    {
        $this->ticketDB = new TicketDB();
        $this->krsData = new KRSData();
        $this->notificationService = new NotificationService(new UserDB());
        $cfg = require __DIR__ . '/../../config/config.php';
        $this->batasBatalJam = (int) $cfg['app']['batasBatalJam'];
    }

    // Pengajuan

    // Form pengajuan
    public function membukaMenuPengajuanTiket(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas']);
        $this->render('TKT-UI01A_TicketUI', [
            'judulHalaman' => 'Ajukan Tiket Pendampingan',
            'menuAktif'    => 'ajukan',
            'profil'       => (new UserDB())->ambilProfilMahasiswa($p['userId']),
            'isian'        => $this->ambilIsianLama(),
            'batasBatalJam'=> $this->batasBatalJam,
        ]);
    }

    // Kirim pengajuan
    public function mengirimPengajuan(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas']);
        $this->wajibPost();
        $dataTiket = $this->bacaFormTiket($p['userId']);
        $dataTiket['opsiBerulang'] = $this->input('opsiBerulang') === '1';
        $dataTiket['periode'] = [
            'mulai' => (string) $this->input('periodeMulai'),
            'akhir' => (string) $this->input('periodeAkhir'),
        ];
        $dataTiket['oleh'] = $p['userId'];

        $hasil = $this->submitTiket($dataTiket);
        if ($hasil['status'] === 'Berhasil') {
            $id = $hasil['idTiket'] ?? $hasil['daftarTiket'][0];
            $pesan = isset($hasil['daftarTiket'])
                ? count($hasil['daftarTiket']) . ' tiket berulang dibuat (' . $hasil['daftarTiket'][0] . ' – ' . end($hasil['daftarTiket']) . ').'
                : 'ID tiket ' . $hasil['idTiket'] . '.';
            $this->setFlash('sukses', 'Pengajuan terkirim · status Menunggu Verifikasi', $pesan);
            $this->redirect('tiket', ['id' => $id]);
        }
        $this->simpanIsianLama($_POST);
        $this->setFlash('error', $hasil['pesan'], implode(' ', $hasil['detail'] ?? []));
        $this->redirect('tiket/ajukan');
    }

    // Proses pengajuan
    public function submitTiket(array $dataTiket): array
    {
        if ($dataTiket['idMahasiswa'] === '' || $dataTiket['jenisLayanan'] === ''
            || $dataTiket['lokasi'] === '' || $dataTiket['waktuMulai'] === '') {
            return ['status' => 'Gagal', 'pesan' => 'Data pengajuan tiket belum lengkap'];
        }
        $statusValidasi = $this->validasiKelengkapanData($dataTiket);
        if (!$statusValidasi['valid']) {
            return ['status' => 'Tidak Valid', 'pesan' => 'Data pengajuan perlu diperbaiki', 'detail' => $statusValidasi['bagian']];
        }
        if ($dataTiket['opsiBerulang']) {
            $daftarTiket = $this->buatTiketBerulang($dataTiket['periode'], $dataTiket);
            if (count($daftarTiket) === 0) {
                return ['status' => 'Gagal', 'pesan' => 'Tiket berulang tidak berhasil dibuat',
                    'detail' => ['Periksa periode mulai/akhir: harus mencakup hari yang sama dengan tanggal sesi (maks. '
                        . self::MAKS_TIKET_BERULANG . ' tiket).']];
            }
            $this->notificationService->kirimNotifikasiStatus($daftarTiket[0], 'Menunggu Verifikasi', $dataTiket['idMahasiswa'],
                count($daftarTiket) . ' tiket berulang menunggu verifikasi Admin FILKOM.');
            return ['status' => 'Berhasil', 'daftarTiket' => $daftarTiket, 'statusTiket' => 'Menunggu Verifikasi'];
        }
        $idTiket = $this->simpanTiket($dataTiket);
        if ($idTiket === null) {
            return ['status' => 'Gagal', 'pesan' => 'Tiket tidak berhasil disimpan'];
        }
        $this->notificationService->kirimNotifikasiStatus($idTiket, 'Menunggu Verifikasi', $dataTiket['idMahasiswa'],
            'Tiket menunggu verifikasi Admin FILKOM.');
        return ['status' => 'Berhasil', 'idTiket' => $idTiket, 'statusTiket' => 'Menunggu Verifikasi'];
    }

    // Validasi data tiket
    public function validasiKelengkapanData(array $dataTiket): array
    {
        $bagian = [];
        if (!in_array($dataTiket['jenisLayanan'], daftarLayanan(), true)) {
            $bagian[] = 'Pilih jenis layanan yang tersedia.';
        }
        if ($dataTiket['lokasi'] === '' || mb_strlen($dataTiket['lokasi']) > 100) {
            $bagian[] = 'Lokasi/ruang wajib diisi (maks. 100 karakter).';
        }
        $mulai = strtotime($dataTiket['waktuMulai']);
        $selesai = strtotime($dataTiket['waktuSelesai']);
        if (!$mulai || !$selesai) {
            $bagian[] = 'Tanggal, waktu mulai, dan waktu selesai wajib diisi.';
        } else {
            if ($selesai <= $mulai) {
                $bagian[] = 'Waktu selesai harus setelah waktu mulai.';
            }
            if ($mulai <= time()) {
                $bagian[] = 'Jadwal sesi harus setelah waktu saat ini.';
            }
            if (VolunteerDB::namaHari($dataTiket['waktuMulai']) === null) {
                $bagian[] = 'Layanan pendampingan tersedia Senin–Jumat.';
            }
        }
        if (mb_strlen($dataTiket['catatanKebutuhan'] ?? '') > 255) {
            $bagian[] = 'Catatan maksimal 255 karakter.';
        }
        return ['valid' => count($bagian) === 0, 'bagian' => $bagian];
    }

    // Simpan tiket
    public function simpanTiket(array $dataTiket): ?string
    {
        return $this->ticketDB->simpanTiket($dataTiket);
    }

    // Buat tiket berulang
    public function buatTiketBerulang(array $periode, array $jadwal): array
    {
        if (empty($periode['mulai']) || empty($periode['akhir']) || empty($jadwal)) {
            return [];
        }
        $awal = strtotime($periode['mulai']);
        $akhir = strtotime($periode['akhir']);
        $hariSesi = (int) date('N', strtotime($jadwal['waktuMulai']));
        if (!$awal || !$akhir || $akhir < $awal) {
            return [];
        }
        // Tanggal dalam periode
        $daftarJadwal = [];
        for ($t = $awal; $t <= $akhir; $t = strtotime('+1 day', $t)) {
            if ((int) date('N', $t) === $hariSesi) {
                $daftarJadwal[] = date('Y-m-d', $t);
            }
        }
        if (count($daftarJadwal) === 0 || count($daftarJadwal) > self::MAKS_TIKET_BERULANG) {
            return [];
        }
        $jamMulai = date('H:i:s', strtotime($jadwal['waktuMulai']));
        $jamSelesai = date('H:i:s', strtotime($jadwal['waktuSelesai']));
        $daftarTiketSementara = [];
        foreach ($daftarJadwal as $tanggal) {
            $dataTiket = array_merge($jadwal, [
                'waktuMulai'     => $tanggal . ' ' . $jamMulai,
                'waktuSelesai'   => $tanggal . ' ' . $jamSelesai,
                'catatanRiwayat' => 'Pengajuan berulang (' . count($daftarJadwal) . ' tiket)',
            ]);
            $statusValidasi = $this->validasiKelengkapanData($dataTiket);
            if (!$statusValidasi['valid']) {
                return [];
            }
            $daftarTiketSementara[] = $dataTiket;
        }
        return $this->ticketDB->simpanTiketBerulang($daftarTiketSementara);
    }

    // Pembatalan

    // Daftar tiket
    public function membukaDaftarTiket(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas']);
        $this->ticketDB->sinkronStatusPelaksanaan();
        $daftarTiket = $this->ticketDB->ambilTiketMahasiswa($p['userId']);
        $idDipilih = (string) $this->input('id', $daftarTiket[0]['idTiket'] ?? '');
        $detail = $idDipilih ? $this->ticketDB->ambilDetail($idDipilih) : null;
        if ($detail && $detail['idMahasiswa'] !== $p['userId']) {
            $detail = null;
        }
        $this->render('TKT-UI01B_TicketUI', [
            'judulHalaman' => 'Tiket Saya',
            'menuAktif'    => 'tiket',
            'daftarTiket'  => $daftarTiket,
            'detail'       => $detail,
            'statusBatas'  => $detail ? $this->periksaBatasPembatalan($detail['idTiket']) : null,
            'batasBatalJam'=> $this->batasBatalJam,
        ]);
    }

    // Konfirmasi pembatalan
    public function konfirmasiPembatalan(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas']);
        $this->wajibPost();
        $idTiket = (string) $this->input('idTiket');
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        if (!$tiket || $tiket['idMahasiswa'] !== $p['userId']) {
            $this->setFlash('error', 'Tiket tidak ditemukan');
            $this->redirect('tiket');
        }
        $hasil = $this->batalkanTiket($idTiket, $this->input('konfirmasi') === '1');
        $this->setFlash($hasil['status'] === 'Berhasil' ? 'sukses' : 'error',
            $hasil['status'] === 'Berhasil' ? 'Tiket ' . $idTiket . ' dibatalkan' : $hasil['pesan'],
            $hasil['status'] === 'Berhasil' ? 'Relawan terkait (bila ada) telah diberi informasi.' : '');
        $this->redirect('tiket', ['id' => $idTiket]);
    }

    // Cek batas waktu
    public function periksaBatasPembatalan(string $idTiket): string
    {
        return $this->ticketDB->cekKelayakanPembatalan($idTiket, $this->batasBatalJam);
    }

    // Proses pembatalan
    public function batalkanTiket(string $idTiket, bool $konfirmasiPembatalan): array
    {
        if ($idTiket === '') {
            return ['status' => 'Gagal', 'pesan' => 'ID tiket tidak valid'];
        }
        if (!$konfirmasiPembatalan) {
            return ['status' => 'Dibatalkan oleh pengguna', 'pesan' => 'Pembatalan tidak dikonfirmasi', 'statusTiket' => 'Tidak Berubah'];
        }
        $statusBatas = $this->periksaBatasPembatalan($idTiket);
        if ($statusBatas !== 'Boleh Batal') {
            return ['status' => 'Ditolak', 'pesan' => $statusBatas === 'Lewat Batas'
                ? 'Batas waktu pembatalan telah lewat; hubungi Admin FILKOM'
                : 'Tiket dengan status ini tidak dapat dibatalkan'];
        }
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        $statusDibatalkan = $this->ticketDB->ubahStatusDibatalkan($idTiket, $tiket['idMahasiswa']);
        if ($statusDibatalkan !== 'Dibatalkan') {
            return ['status' => 'Gagal', 'pesan' => 'Status tiket tidak berhasil diubah'];
        }
        if ($tiket['idRelawan']) {
            (new VolunteerDB())->lepasKunciJadwal($tiket['idRelawan'], $idTiket);
        }
        $this->notificationService->sampaikanPembatalanJikaPerlu($tiket);
        return ['status' => 'Berhasil', 'idTiket' => $idTiket, 'statusTiket' => 'Dibatalkan'];
    }

    // Verifikasi

    // Halaman verifikasi
    public function membukaMenuVerifikasiTiket(): void
    {
        $this->wajibPeran(['Admin FILKOM']);
        $cari = (string) $this->input('cari');
        $daftar = $this->kelompokkanSeri($this->ambilTiketMenungguVerifikasi($cari));
        $idDipilih = (string) $this->input('id', $daftar[0]['idTiket'] ?? '');
        $detail = null;
        $perbandingan = null;
        foreach ($daftar as $baris) {
            if ($baris['idTiket'] === $idDipilih) {
                $detail = $baris;
            }
        }
        if ($detail) {
            $dataKRS = $this->ambilDataKRS($detail['idMahasiswa']);
            $perbandingan = $dataKRS === null ? null : $this->krsData->periksaKesesuaian($detail);
        }
        $this->render('TKT-UI02_TicketValidationUI', [
            'judulHalaman' => 'Verifikasi Tiket dan KRS',
            'menuAktif'    => 'admin-tiket',
            'daftar'       => $daftar,
            'detail'       => $detail,
            'perbandingan' => $perbandingan,
            'cari'         => $cari,
        ]);
    }

    // Tiket menunggu
    public function ambilTiketMenungguVerifikasi(string $cari = ''): array
    {
        return $this->ticketDB->ambilTiketMenungguVerifikasi($cari);
    }

    // Ambil KRS
    public function ambilDataKRS(string $idMahasiswa): ?array
    {
        return $this->krsData->ambilJadwalMahasiswa($idMahasiswa);
    }

    // Kirim keputusan
    public function simpanKeputusanTiket(): void
    {
        $admin = $this->wajibPeran(['Admin FILKOM']);
        $this->wajibPost();
        $keputusan = [
            'idTiket' => (string) $this->input('idTiket'),
            'hasil'   => (string) $this->input('hasil'),
            'alasan'  => mb_substr((string) $this->input('alasan'), 0, 255),
            'oleh'    => $admin['userId'],
        ];
        $hasil = $this->simpanKeputusanValidasi($keputusan);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Keputusan tersimpan',
                count($hasil['daftarTiket']) . ' tiket berstatus ' . $hasil['statusTiket']
                . ($hasil['statusTiket'] === 'Tervalidasi' ? ' dan masuk antrean pencarian relawan.' : '.'));
            $this->redirect('admin/tiket');
        }
        $this->setFlash('error', 'Keputusan belum tersimpan', $hasil['pesan']);
        $this->redirect('admin/tiket', ['id' => $keputusan['idTiket']]);
    }

    // Simpan keputusan
    public function simpanKeputusanValidasi(array $keputusan): array
    {
        $daftarTiket = $this->ambilTiketMenungguVerifikasi();
        $tiket = null;
        foreach ($daftarTiket as $t) {
            if ($t['idTiket'] === $keputusan['idTiket']) {
                $tiket = $t;
            }
        }
        if ($tiket === null) {
            return ['status' => 'Gagal', 'pesan' => 'Tiket menunggu verifikasi tidak ditemukan'];
        }
        // Satu seri sekaligus
        $target = $tiket['idSeri']
            ? array_values(array_filter($this->ticketDB->ambilTiketSeri($tiket['idSeri']), fn ($t) => $t['status'] === 'Menunggu Verifikasi'))
            : [$tiket];
        $dataKRS = $this->ambilDataKRS($tiket['idMahasiswa']);
        if ($dataKRS === null) {
            return ['status' => 'Ditunda', 'pesan' => 'Data KRS mahasiswa belum tersedia'];
        }
        if ($keputusan['hasil'] === 'Setujui') {
            foreach ($target as $t) {
                if ($this->krsData->periksaKesesuaian($t)['hasil'] !== 'SESUAI') {
                    return ['status' => 'Tidak Dapat Disetujui', 'pesan' => 'Jadwal tiket ' . $t['idTiket'] . ' tidak sesuai dengan data KRS'];
                }
            }
            $statusTiket = 'Tervalidasi';
        } elseif ($keputusan['hasil'] === 'Tolak') {
            if (trim($keputusan['alasan']) === '') {
                return ['status' => 'Gagal', 'pesan' => 'Alasan penolakan wajib diisi'];
            }
            $statusTiket = 'Ditolak';
        } else {
            return ['status' => 'Gagal', 'pesan' => 'Keputusan validasi tidak dikenali'];
        }
        foreach ($target as $t) {
            $catatan = $statusTiket === 'Tervalidasi' ? 'Sesuai KRS' : 'Alasan: ' . $keputusan['alasan'];
            $this->ticketDB->ubahStatusTiket($t['idTiket'], $statusTiket, $keputusan['oleh'], $catatan);
            if ($statusTiket === 'Tervalidasi') {
                $this->masukkanKeAntreanPencarian($t['idTiket']);
            }
        }
        $this->notificationService->kirimNotifikasiStatus($tiket['idTiket'], $statusTiket, $tiket['idMahasiswa'],
            $statusTiket === 'Ditolak' ? 'Alasan penolakan: ' . $keputusan['alasan']
                : count($target) . ' tiket tervalidasi dan sedang dicarikan relawan.');
        return ['status' => 'Berhasil', 'statusTiket' => $statusTiket, 'daftarTiket' => array_column($target, 'idTiket')];
    }

    // Masuk antrean
    public function masukkanKeAntreanPencarian(string $idTiket): void
    {
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        if ($tiket) {
            (new MatchingController())->mencariKecocokan($tiket);
        }
    }

    // Tiket manual

    // Form tiket manual
    public function membukaMenuTiketManual(): void
    {
        $this->wajibPeran(['Admin FILKOM']);
        $userDB = new UserDB();
        $cari = (string) $this->input('cari');
        $isian = $this->ambilIsianLama();
        $idMahasiswa = (string) $this->input('idMahasiswa', $isian['idMahasiswa'] ?? '');
        $hasilCari = $cari !== '' ? $userDB->cariMahasiswa($cari) : [];
        if ($idMahasiswa === '' && count($hasilCari) === 1) {
            $idMahasiswa = $hasilCari[0]['userId'];
        }
        $this->render('TKT-UI03_ManualTicketUI', [
            'judulHalaman' => 'Buat Tiket Manual',
            'menuAktif'    => 'admin-manual',
            'cari'         => $cari,
            'hasilCari'    => $hasilCari,
            'mahasiswa'    => $idMahasiswa !== '' ? $userDB->ambilProfilMahasiswa($idMahasiswa) : null,
            'isian'        => $isian,
        ]);
    }

    // Kirim tiket manual
    public function submitTiketManual(): void
    {
        $admin = $this->wajibPeran(['Admin FILKOM']);
        $this->wajibPost();
        $dataTiket = $this->bacaFormTiket((string) $this->input('idMahasiswa'));
        $dataTiket['catatanTiket'] = $dataTiket['catatanKebutuhan'] !== '' ? 'Catatan admin: ' . $dataTiket['catatanKebutuhan'] : '';
        $dataTiket['catatanKebutuhan'] = '';
        $dataTiket['dibuatOleh'] = 'Admin';
        $dataTiket['oleh'] = $admin['userId'];
        $dataTiket['catatanRiwayat'] = 'Tiket manual oleh Admin FILKOM';

        $hasil = $this->buatTiketManual($dataTiket);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Tiket manual tersimpan',
                $hasil['idTiket'] . ' berstatus Menunggu Verifikasi dan diteruskan ke menu Verifikasi Tiket.');
            $this->redirect('admin/tiket', ['id' => $hasil['idTiket']]);
        }
        $this->simpanIsianLama($_POST);
        $this->setFlash('error', $hasil['pesan'], implode(' ', $hasil['detail'] ?? []));
        $this->redirect('admin/tiket-manual', ['idMahasiswa' => $dataTiket['idMahasiswa']]);
    }

    // Proses tiket manual
    public function buatTiketManual(array $dataTiket): array
    {
        if ($dataTiket['idMahasiswa'] === '' || $dataTiket['jenisLayanan'] === ''
            || $dataTiket['lokasi'] === '' || $dataTiket['waktuMulai'] === '') {
            return ['status' => 'Gagal', 'pesan' => 'Data tiket manual belum lengkap'];
        }
        if ((new UserDB())->ambilProfilMahasiswa($dataTiket['idMahasiswa']) === null) {
            return ['status' => 'Gagal', 'pesan' => 'Mahasiswa tidak ditemukan'];
        }
        $statusValidasi = $this->validasiKelengkapanData($dataTiket);
        if (!$statusValidasi['valid']) {
            return ['status' => 'Tidak Valid', 'pesan' => 'Data tiket manual perlu dilengkapi', 'detail' => $statusValidasi['bagian']];
        }
        $idTiket = $this->ticketDB->simpanTiket($dataTiket);
        if ($idTiket === null) {
            return ['status' => 'Gagal', 'pesan' => 'Tiket manual tidak berhasil disimpan'];
        }
        $this->notificationService->kirimNotifikasiStatus($idTiket, 'Menunggu Verifikasi', $dataTiket['idMahasiswa'],
            'Admin FILKOM membuat tiket manual atas namamu.');
        return ['status' => 'Berhasil', 'idTiket' => $idTiket, 'statusTiket' => 'Menunggu Verifikasi'];
    }

    // Bantuan

    // Baca isian form
    private function bacaFormTiket(string $idMahasiswa): array
    {
        $tanggal = (string) $this->input('tanggal');
        $mulai = (string) $this->input('waktuMulai');
        $selesai = (string) $this->input('waktuSelesai');
        $layanan = (string) $this->input('jenisLayanan');
        return [
            'idMahasiswa'      => $idMahasiswa,
            'jenisLayanan'     => daftarLayanan()[$layanan] ?? $layanan,
            'lokasi'           => (string) $this->input('lokasi'),
            'waktuMulai'       => ($tanggal && $mulai) ? $tanggal . ' ' . $mulai . ':00' : '',
            'waktuSelesai'     => ($tanggal && $selesai) ? $tanggal . ' ' . $selesai . ':00' : '',
            'catatanKebutuhan' => (string) $this->input('catatan'),
        ];
    }

    // Gabung tiket berulang
    private function kelompokkanSeri(array $daftar): array
    {
        $hasil = [];
        foreach ($daftar as $t) {
            $kunci = $t['idSeri'] ?: $t['idTiket'];
            if (!isset($hasil[$kunci])) {
                $t['jumlahSeri'] = 1;
                $t['akhirSeri'] = $t['jadwalKelas'];
                $hasil[$kunci] = $t;
            } else {
                $hasil[$kunci]['jumlahSeri']++;
                $hasil[$kunci]['akhirSeri'] = max($hasil[$kunci]['akhirSeri'], $t['jadwalKelas']);
            }
        }
        return array_values($hasil);
    }
}
