<?php
// Validasi jam
class HoursController extends Controller
{
    private VolunteerHoursDB $volunteerHoursDB;
    private IssueDB $issueDB;

    public function __construct()
    {
        $this->volunteerHoursDB = new VolunteerHoursDB();
        $this->issueDB = new IssueDB();
    }

    // Halaman validasi jam
    public function membukaMenuValidasiJam(): void
    {
        $this->wajibPeran(['Admin FILKOM']);
        $daftarPeriode = $this->volunteerHoursDB->ambilDaftarPeriode();
        $periode = (string) $this->input('periode', $daftarPeriode[0]);
        $daftarJam = $this->mintaDaftarJamPengabdian($periode);
        $idDipilih = (string) $this->input('id', $daftarJam[0]['idRelawan'] ?? '');
        $detail = null;
        foreach ($daftarJam as $r) {
            if ($r['idRelawan'] === $idDipilih) {
                $detail = $r;
                $detail['laporanTerbuka'] = $this->issueDB->hitungLaporanTerbuka($r['idRelawan'], $periode);
            }
        }
        $this->render('SKM-UI03_HoursValidationUI', [
            'judulHalaman'  => 'Validasi Akumulasi Jam Pengabdian',
            'menuAktif'     => 'admin-jam',
            'daftarJam'     => $daftarJam,
            'detail'        => $detail,
            'periode'       => $periode,
            'daftarPeriode' => $daftarPeriode,
        ]);
    }

    // Akumulasi jam
    public function mintaDaftarJamPengabdian(string $periode): array
    {
        return $this->volunteerHoursDB->ambilAkumulasiJamPerRelawan($periode);
    }

    // Konfirmasi validasi
    public function mengonfirmasiValidasi(): void
    {
        $admin = $this->wajibPeran(['Admin FILKOM']);
        $this->wajibPost();
        $idRelawan = (string) $this->input('idRelawan');
        $periode = (string) $this->input('periode');
        $hasil = $this->submitValidasiJam($idRelawan, $admin['userId'], $periode);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Jam pengabdian terverifikasi',
                angka($hasil['dataSKM']['jamTerverifikasi']) . ' jam milik ' . $idRelawan . ' siap untuk proses konversi SKM.');
        } else {
            $this->setFlash('error', 'Validasi belum dapat dilakukan', $hasil['pesan']);
        }
        $this->redirect('admin/jam', ['periode' => $periode, 'id' => $idRelawan]);
    }

    // Proses validasi
    public function submitValidasiJam(string $idRelawan, string $idAdmin, string $periode): array
    {
        if ($idRelawan === '') {
            return ['status' => 'Gagal', 'pesan' => 'ID relawan tidak valid'];
        }
        $totalJam = $this->volunteerHoursDB->ambilTotalJamPengabdian($idRelawan, $periode);
        if ((int) $totalJam['jumlahTiket'] === 0) {
            return ['status' => 'Tidak Tersedia', 'pesan' => 'Data jam pengabdian belum tersedia'];
        }
        if ($this->issueDB->hitungLaporanTerbuka($idRelawan, $periode) > 0) {
            return ['status' => 'Ditunda', 'pesan' => 'Selesaikan peninjauan kendala sebelum validasi jam'];
        }
        $statusTerverifikasi = $this->volunteerHoursDB->simpanStatusTerverifikasi($idRelawan, $periode, $idAdmin);
        if ($statusTerverifikasi !== 'Terverifikasi') {
            return ['status' => 'Gagal', 'pesan' => 'Status jam tidak berhasil diverifikasi'];
        }
        $dataTerverifikasi = $this->sediakanDataUntukKonversiSKM($idRelawan, (float) $totalJam['total']);
        return ['status' => 'Berhasil', 'totalJam' => $totalJam['total'], 'dataSKM' => $dataTerverifikasi];
    }

    // Data konversi SKM
    public function sediakanDataUntukKonversiSKM(string $idRelawan, float $totalJam): array
    {
        return $this->volunteerHoursDB->sediakanDataUntukKonversiSKM($idRelawan) + ['totalJamPeriode' => $totalJam];
    }

    // Tandai perlu ditinjau
    public function menandaiDataDitinjauKembali(): void
    {
        $this->wajibPeran(['Admin FILKOM']);
        $this->wajibPost();
        $idRelawan = (string) $this->input('idRelawan');
        $periode = (string) $this->input('periode');
        $catatan = trim((string) $this->input('catatan')) ?: 'Data jam perlu diperiksa kembali oleh Admin FILKOM.';
        $status = $this->simpanStatusPerluDitinjau($idRelawan, mb_substr($catatan, 0, 255), $periode);
        $this->setFlash('info', 'Ditandai ' . $status, 'Pengesahan jam ' . $idRelawan . ' ditahan sampai data diperiksa.');
        $this->redirect('admin/jam', ['periode' => $periode, 'id' => $idRelawan]);
    }

    // Simpan catatan
    public function simpanStatusPerluDitinjau(string $idRelawan, string $catatan, string $periode): string
    {
        return $this->volunteerHoursDB->catatStatusPerluDitinjau($idRelawan, $catatan, $periode);
    }
}
