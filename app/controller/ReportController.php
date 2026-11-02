<?php
// Laporan rekapitulasi
class ReportController extends Controller
{
    private TicketDB $ticketDB;
    private VolunteerDB $volunteerDB;
    private ReportService $reportService;

    public function __construct()
    {
        $this->ticketDB = new TicketDB();
        $this->volunteerDB = new VolunteerDB();
        $this->reportService = new ReportService();
    }

    // Halaman laporan
    public function membukaMenuLaporanRekapitulasi(): void
    {
        $this->wajibPeran(['Admin FILKOM']);
        $periode = $this->bacaPeriode();
        $hasil = isset($_GET['awal'])
            ? $this->perbaruiPeriodeLaporan($periode)
            : $this->mintaDataRekapitulasi($periode);
        $this->render('SKM-UI04_ReportUI', [
            'judulHalaman' => 'Laporan Rekapitulasi Layanan',
            'menuAktif'    => 'admin-laporan',
            'periode'      => $periode,
            'hasil'        => $hasil,
        ]);
    }

    // Ambil data rekap
    public function mintaDataRekapitulasi(array $periode): array
    {
        if (empty($periode['awal']) || empty($periode['akhir'])) {
            return ['status' => 'Gagal', 'pesan' => 'Periode laporan wajib dipilih'];
        }
        if ($periode['akhir'] < $periode['awal']) {
            return ['status' => 'Gagal', 'pesan' => 'Tanggal akhir harus sama atau setelah tanggal awal'];
        }
        $jumlahTiket = $this->ticketDB->ambilJumlahTiket($periode);
        $dataPerforma = $this->volunteerDB->ambilPerformaRelawan($periode);
        if ((int) $jumlahTiket['total'] === 0 && count($dataPerforma) === 0) {
            return ['status' => 'Tidak Tersedia', 'pesan' => 'Data pada periode yang dipilih belum tersedia'];
        }
        $rekapitulasi = $this->susunRekap($periode, $jumlahTiket, $dataPerforma);
        return ['status' => 'Berhasil', 'rekapitulasi' => $rekapitulasi];
    }

    // Ganti periode
    public function perbaruiPeriodeLaporan(array $periode): array
    {
        if (empty($periode['awal']) || empty($periode['akhir']) || $periode['akhir'] < $periode['awal']) {
            return ['status' => 'Gagal', 'pesan' => 'Periode laporan tidak valid'];
        }
        $jumlahTiketBaru = $this->ticketDB->ambilDataPeriodeBaru($periode);
        $performaBaru = $this->volunteerDB->ambilPerformaPeriodeBaru($periode);
        if ((int) $jumlahTiketBaru['total'] === 0 && count($performaBaru) === 0) {
            return ['status' => 'Tidak Tersedia', 'pesan' => 'Data pada periode yang dipilih belum tersedia'];
        }
        return ['status' => 'Berhasil', 'rekapitulasi' => $this->susunRekap($periode, $jumlahTiketBaru, $performaBaru)];
    }

    // Unduh laporan
    public function memilihCetakAtauUnduh(): void
    {
        $this->wajibPeran(['Admin FILKOM']);
        $periode = $this->bacaPeriode();
        $formatOutput = strtoupper((string) $this->input('format'));
        $hasil = $this->hasilkanLaporan($periode, $formatOutput);
        if ($hasil['status'] !== 'Berhasil') {
            $this->setFlash('error', 'Laporan tidak dapat diunduh', $hasil['pesan']);
            $this->redirect('admin/laporan', ['awal' => $periode['awal'], 'akhir' => $periode['akhir']]);
        }
        $dokumen = $hasil['dokumenLaporan'];
        header('Content-Type: ' . $dokumen['mime']);
        header('Content-Disposition: attachment; filename="' . $dokumen['nama'] . '"');
        header('Content-Length: ' . strlen($dokumen['isi']));
        echo $dokumen['isi'];
    }

    // Buat dokumen
    public function hasilkanLaporan(array $periode, string $formatOutput): array
    {
        $data = $this->mintaDataRekapitulasi($periode);
        $rekapitulasi = $data['rekapitulasi'] ?? null;
        if ($formatOutput === '' || $rekapitulasi === null) {
            return ['status' => 'Gagal', 'pesan' => 'Format dan data rekapitulasi wajib tersedia'];
        }
        if (!in_array($formatOutput, ['PDF', 'XLSX'], true)) {
            return ['status' => 'Gagal', 'pesan' => 'Format laporan tidak didukung'];
        }
        $dokumenLaporan = $this->reportService->generateDokumenLaporan($rekapitulasi, $formatOutput);
        if ($dokumenLaporan === null) {
            return ['status' => 'Gagal', 'pesan' => 'Dokumen laporan tidak berhasil dibuat'];
        }
        return ['status' => 'Berhasil', 'dokumenLaporan' => $dokumenLaporan, 'format' => $formatOutput];
    }

    private function susunRekap(array $periode, array $jumlahTiket, array $dataPerforma): array
    {
        return [
            'periode'          => $periode,
            'jumlahTiket'      => $jumlahTiket,
            'dataPerforma'     => $dataPerforma,
            'jamTerverifikasi' => array_sum(array_column($dataPerforma, 'jamTerverifikasi')),
            'laporanKendala'   => array_sum(array_column($dataPerforma, 'laporanKendala')),
            'dibuat'           => date('Y-m-d H:i:s'),
            'dibuatOleh'       => Auth::pengguna()['nama'] ?? 'Admin FILKOM',
        ];
    }

    // Baca periode
    private function bacaPeriode(): array
    {
        $awal = (string) $this->input('awal', date('Y-m-01'));
        $akhir = (string) $this->input('akhir', date('Y-m-t'));
        $valid = fn ($t) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $t);
        $awal = $valid($awal) ? $awal : date('Y-m-01');
        $akhir = $valid($akhir) ? $akhir : date('Y-m-t');
        $label = tglIndo($awal, 'tanpaTahun') . ' – ' . tglIndo($akhir);
        if (substr($awal, 0, 7) === substr($akhir, 0, 7)) {
            $label = date('j', strtotime($awal)) . '–' . date('j', strtotime($akhir)) . ' '
                . explode(' ', tglIndo($awal, 'panjang'))[1] . ' ' . date('Y', strtotime($awal));
        }
        return ['awal' => $awal, 'akhir' => $akhir, 'label' => $label];
    }
}
