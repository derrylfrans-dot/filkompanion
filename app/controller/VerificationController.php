<?php
// Konfirmasi kehadiran
class VerificationController extends Controller
{
    private TicketDB $ticketDB;

    public function __construct()
    {
        $this->ticketDB = new TicketDB();
    }

    // Buka detail tiket
    public function membukaDetailTiketSelesai(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas']);
        $detailBukti = $this->memintaDetailDanBukti((string) $this->input('id'), $p['userId']);
        if ($detailBukti === null) {
            $this->setFlash('error', 'Tiket tidak ditemukan');
            $this->redirect('tiket');
        }
        $this->render('PLS-UI02_VerificationUI', [
            'judulHalaman' => 'Konfirmasi Pendampingan',
            'menuAktif'    => 'tiket',
            'tiket'        => $detailBukti,
            'waktuBukti'   => $this->waktuUnggahBukti($detailBukti),
        ]);
    }

    // Ambil detail
    public function memintaDetailDanBukti(string $idTiket, string $idMahasiswa): ?array
    {
        $detail = $this->ticketDB->ambilDetailBukti($idTiket);
        return ($detail && $detail['idMahasiswa'] === $idMahasiswa) ? $detail : null;
    }

    // Kirim konfirmasi
    public function simpanKonfirmasiMahasiswa(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas']);
        $this->wajibPost();
        $idTiket = (string) $this->input('idTiket');
        if ($this->memintaDetailDanBukti($idTiket, $p['userId']) === null) {
            $this->redirect('tiket');
        }
        $hasil = $this->kirimKonfirmasi($idTiket, (string) $this->input('pilihan', 'Konfirmasi'), $p['userId']);
        if ($hasil['status'] === 'Laporkan Kendala') {
            $this->redirect('kendala/lapor', ['id' => $idTiket]);
        }
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Konfirmasi kehadiran tersimpan', 'Status verifikasi penyelesaian tiket diperbarui.');
        } else {
            $this->setFlash('error', $hasil['pesan']);
        }
        $this->redirect('konfirmasi', ['id' => $idTiket]);
    }

    // Proses konfirmasi
    public function kirimKonfirmasi(string $idTiket, string $pilihan, string $idMahasiswa): array
    {
        $statusTiket = $this->ticketDB->cekStatusTiket($idTiket);
        if ($pilihan === 'Laporkan Kendala') {
            return ['status' => 'Laporkan Kendala', 'idTiket' => $idTiket];
        }
        if ($statusTiket !== 'Selesai') {
            return ['status' => 'Gagal', 'pesan' => 'Tiket belum diselesaikan oleh relawan'];
        }
        $detailBukti = $this->ticketDB->ambilDetailBukti($idTiket);
        if ($detailBukti === null) {
            return ['status' => 'Gagal', 'pesan' => 'Detail tiket tidak ditemukan'];
        }
        if ($detailBukti['konfirmasiMahasiswa']) {
            return ['status' => 'Gagal', 'pesan' => 'Kehadiran pada tiket ini sudah dikonfirmasi'];
        }
        $konfirmasiTersimpan = $this->ticketDB->simpanKonfirmasi($idTiket);
        if ($konfirmasiTersimpan === false) {
            return ['status' => 'Gagal', 'pesan' => 'Konfirmasi tidak berhasil disimpan'];
        }
        $statusTerverifikasi = $this->ticketDB->perbaruiVerifikasi($idTiket, $idMahasiswa);
        if ($statusTerverifikasi !== 'Terverifikasi') {
            return ['status' => 'Gagal', 'pesan' => 'Catatan verifikasi tidak berhasil diperbarui'];
        }
        return ['status' => 'Berhasil', 'statusTerverifikasi' => $statusTerverifikasi];
    }

    private function waktuUnggahBukti(array $tiket): ?string
    {
        foreach (array_reverse($tiket['riwayat']) as $r) {
            if ($r['status'] === 'Selesai') {
                return $r['waktu'];
            }
        }
        return null;
    }
}
