<?php
// Laporan kendala
class IssueController extends Controller
{
    public const JENIS_KENDALA = ['Relawan tidak hadir', 'Relawan terlambat', 'Akses/lokasi', 'Lainnya'];
    public const KEPUTUSAN = [
        'Pendampingan sah, tanpa perubahan jam',
        'Pendampingan sah, jam disesuaikan',
        'Pendampingan tidak sah, jam tidak dihitung',
    ];
    private const FORMAT_FOTO = ['jpg', 'jpeg', 'png'];

    private IssueDB $issueDB;
    private TicketDB $ticketDB;
    private VolunteerHoursDB $volunteerHoursDB;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->issueDB = new IssueDB();
        $this->ticketDB = new TicketDB();
        $this->volunteerHoursDB = new VolunteerHoursDB();
        $this->notificationService = new NotificationService(new UserDB());
    }

    // Pelaporan

    // Form laporan
    public function membukaFormLaporanKendala(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas']);
        $formKendala = $this->memintaFormKendala((string) $this->input('id'), $p['userId']);
        if ($formKendala['status'] !== 'Berhasil') {
            $this->setFlash('error', $formKendala['pesan']);
            $this->redirect('tiket');
        }
        $this->render('PLS-UI03_IssueReportUI', [
            'judulHalaman' => 'Laporkan Kendala Pendampingan',
            'menuAktif'    => 'tiket',
            'tiket'        => $formKendala['tiket'],
            'opsiKendala'  => self::JENIS_KENDALA,
            'isian'        => $this->ambilIsianLama(),
        ]);
    }

    // Siapkan form
    public function memintaFormKendala(string $idTiket, string $idMahasiswa): array
    {
        $tiket = $this->ticketDB->ambilDetail($idTiket);
        if (!$tiket || $tiket['idMahasiswa'] !== $idMahasiswa) {
            return ['status' => 'Gagal', 'pesan' => 'Tiket tidak ditemukan'];
        }
        if (!in_array($tiket['status'], ['Dilaksanakan', 'Selesai', 'Bermasalah'], true)) {
            return ['status' => 'Gagal', 'pesan' => 'Kendala dapat dilaporkan untuk tiket yang sedang atau telah dilaksanakan'];
        }
        return ['status' => 'Berhasil', 'tiket' => $tiket];
    }

    // Kirim laporan
    public function mengirimLaporan(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas']);
        $this->wajibPost();
        $idTiket = (string) $this->input('idTiket');
        if ($this->memintaFormKendala($idTiket, $p['userId'])['status'] !== 'Berhasil') {
            $this->redirect('tiket');
        }
        $laporan = [
            'idTiket'      => $idTiket,
            'jenisKendala' => (string) $this->input('jenisKendala'),
            'deskripsi'    => (string) $this->input('deskripsi'),
            'lampiran'     => Upload::daftarBerkas($_FILES['lampiran'] ?? null),
            'oleh'         => $p['userId'],
        ];
        $hasil = $this->submitLaporan($laporan);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Laporan ' . $hasil['idLaporan'] . ' terkirim',
                'Admin FILKOM sudah menerima informasi laporan dan akan menindaklanjutinya.');
            $this->redirect('tiket', ['id' => $idTiket]);
        }
        $this->simpanIsianLama(['jenisKendala' => $laporan['jenisKendala'], 'deskripsi' => $laporan['deskripsi']]);
        $this->setFlash('error', $hasil['pesan']);
        $this->redirect('kendala/lapor', ['id' => $idTiket]);
    }

    // Cek deskripsi
    public function periksaDeskripsi(string $deskripsi): bool
    {
        return trim($deskripsi) !== '';
    }

    // Proses laporan
    public function submitLaporan(array $laporan): array
    {
        if (!$this->periksaDeskripsi($laporan['deskripsi'])) {
            return ['status' => 'Gagal', 'pesan' => 'Deskripsi kendala belum diisi'];
        }
        if (!in_array($laporan['jenisKendala'], self::JENIS_KENDALA, true)) {
            return ['status' => 'Gagal', 'pesan' => 'Jenis kendala wajib dipilih'];
        }
        if (mb_strlen($laporan['deskripsi']) > 255) {
            return ['status' => 'Gagal', 'pesan' => 'Deskripsi kendala maksimal 255 karakter'];
        }
        foreach ($laporan['lampiran'] as $berkas) {
            if ($pesan = Upload::periksa($berkas, self::FORMAT_FOTO)) {
                return ['status' => 'Gagal', 'pesan' => $pesan];
            }
        }
        $idLaporan = $this->issueDB->buatIdLaporan();
        $nama = [];
        foreach (array_slice($laporan['lampiran'], 0, 5) as $i => $berkas) {
            $nama[] = Upload::simpan($berkas, 'kendala', $idLaporan . '_' . ($i + 1));
        }
        $idLaporan = $this->issueDB->simpanLaporan([
            'idLaporan' => $idLaporan, 'idTiket' => $laporan['idTiket'], 'jenisKendala' => $laporan['jenisKendala'],
            'deskripsi' => trim($laporan['deskripsi']), 'lampiran' => implode(',', $nama),
        ]);
        if ($idLaporan === null) {
            return ['status' => 'Gagal', 'pesan' => 'Laporan tidak berhasil disimpan'];
        }
        $statusBermasalah = $this->ticketDB->tandaiBermasalah($laporan['idTiket'], $laporan['oleh'], $idLaporan);
        if ($statusBermasalah !== 'Bermasalah') {
            return ['status' => 'Gagal', 'pesan' => 'Tiket tidak berhasil ditandai bermasalah'];
        }
        $this->sampaikanLaporan($idLaporan, $laporan['idTiket']);
        return ['status' => 'Berhasil', 'idLaporan' => $idLaporan];
    }

    // Kabari Admin
    public function sampaikanLaporan(string $idLaporan, string $idTiket): void
    {
        $this->notificationService->kirimNotifikasiAdmin($idLaporan, $idTiket);
    }

    // Tindak lanjut

    // Daftar laporan
    public function membukaDaftarLaporan(): void
    {
        $this->wajibPeran(['Admin FILKOM']);
        $daftarLaporan = $this->mintaDaftarLaporan();
        $idDipilih = (string) $this->input('id', $daftarLaporan[0]['idLaporan'] ?? '');
        $this->render('SKM-UI02_IssueManagementUI', [
            'judulHalaman' => 'Tindak Lanjut Laporan Kendala',
            'menuAktif'    => 'admin-kendala',
            'daftar'       => $daftarLaporan,
            'detail'       => $idDipilih ? $this->issueDB->ambilLaporan($idDipilih) : null,
            'opsiKeputusan'=> self::KEPUTUSAN,
        ]);
    }

    // Ambil laporan
    public function mintaDaftarLaporan(): array
    {
        return $this->issueDB->ambilLaporanDanTiketTerkait();
    }

    // Kirim keputusan
    public function menentukanTindakLanjut(): void
    {
        $admin = $this->wajibPeran(['Admin FILKOM']);
        $this->wajibPost();
        $penyesuaian = $this->input('penyesuaianJam') === '1';
        $keputusan = [
            'idLaporan'         => (string) $this->input('idLaporan'),
            'hasilTindakLanjut' => (string) $this->input('keputusan'),
            'catatanAdmin'      => mb_substr((string) $this->input('catatanAdmin'), 0, 255),
            'penyesuaianJam'    => $penyesuaian ? str_replace(',', '.', (string) $this->input('durasiBaru')) : '',
            'oleh'              => $admin['userId'],
        ];
        $hasil = $this->simpanKeputusanTindakLanjut($keputusan);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Tindak lanjut ' . $keputusan['idLaporan'] . ' tersimpan', $hasil['statusJam'] . '.');
        } else {
            $this->setFlash('error', 'Keputusan belum tersimpan', $hasil['pesan']);
        }
        $this->redirect('admin/kendala', ['id' => $keputusan['idLaporan']]);
    }

    // Simpan keputusan
    public function simpanKeputusanTindakLanjut(array $keputusan): array
    {
        $daftarLaporan = $this->issueDB->ambilLaporanDanTiketTerkait();
        $laporan = null;
        foreach ($daftarLaporan as $item) {
            if ($item['idLaporan'] === $keputusan['idLaporan']) {
                $laporan = $item;
            }
        }
        if ($laporan === null) {
            return ['status' => 'Gagal', 'pesan' => 'Laporan kendala tidak ditemukan'];
        }
        if ($laporan['statusTindakLanjut'] === 'Ditindaklanjuti') {
            return ['status' => 'Gagal', 'pesan' => 'Laporan ini sudah ditindaklanjuti'];
        }
        if (!in_array($keputusan['hasilTindakLanjut'], self::KEPUTUSAN, true)) {
            return ['status' => 'Gagal', 'pesan' => 'Keputusan tindak lanjut wajib diisi'];
        }
        if ($keputusan['hasilTindakLanjut'] === self::KEPUTUSAN[2]) {
            $keputusan['penyesuaianJam'] = '0'; // Jam tidak dihitung
        }
        if ($keputusan['penyesuaianJam'] !== ''
            && (!is_numeric($keputusan['penyesuaianJam']) || $keputusan['penyesuaianJam'] < 0 || $keputusan['penyesuaianJam'] > 12)) {
            return ['status' => 'Gagal', 'pesan' => 'Durasi penyesuaian harus berupa angka 0–12 jam'];
        }
        $statusTindakLanjut = $this->issueDB->simpanKeputusanLaporan(
            $keputusan['idLaporan'], $keputusan['hasilTindakLanjut'], $keputusan['catatanAdmin']
        );
        if ($statusTindakLanjut === null) {
            return ['status' => 'Gagal', 'pesan' => 'Keputusan tidak berhasil disimpan'];
        }
        $idTiket = $laporan['idTiket'];
        $this->ticketDB->perbaruiCatatanTiket($idTiket,
            'Tindak lanjut ' . $keputusan['idLaporan'] . ': ' . $keputusan['hasilTindakLanjut']);

        $adaJam = $laporan['durasiTercatat'] !== null;
        if ($keputusan['penyesuaianJam'] !== '' && $adaJam) {
            $statusJam = $this->volunteerHoursDB->sesuaikanJamPengabdian($idTiket, (float) $keputusan['penyesuaianJam']);
        } else {
            $statusJam = $this->simpanKeputusanTanpaMengubahJam($keputusan['idLaporan'], $keputusan['hasilTindakLanjut']);
        }
        // Kembalikan status semula
        if (str_starts_with($keputusan['hasilTindakLanjut'], 'Pendampingan sah')
            && $this->ticketDB->cekStatusTiket($idTiket) === 'Bermasalah') {
            $this->ticketDB->ubahStatusTiket($idTiket, $adaJam ? 'Selesai' : 'Dilaksanakan', $keputusan['oleh'],
                'Tindak lanjut ' . $keputusan['idLaporan']);
        }
        return ['status' => 'Berhasil', 'statusTindakLanjut' => $statusTindakLanjut, 'statusJam' => $statusJam];
    }

    // Tanpa perubahan jam
    public function simpanKeputusanTanpaMengubahJam(string $idLaporan, string $keputusan): string
    {
        return $this->issueDB->catatKeputusanTanpaPerubahanJam($idLaporan, $keputusan);
    }

    // Buka lampiran
    public function lihatLampiran(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas', 'Admin FILKOM']);
        $laporan = $this->issueDB->ambilLaporan((string) $this->input('id'));
        $file = (string) $this->input('file');
        $tiket = $laporan ? $this->ticketDB->ambilDetail($laporan['idTiket']) : null;
        if (!$laporan || !in_array($file, explode(',', (string) $laporan['lampiran']), true)
            || ($p['rolePengguna'] !== 'Admin FILKOM' && $tiket['idMahasiswa'] !== $p['userId'])) {
            http_response_code(404);
            exit('Lampiran tidak ditemukan.');
        }
        Upload::kirim('kendala', $file);
    }
}
