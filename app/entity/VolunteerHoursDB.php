<?php
// Tabel jam pengabdian
class VolunteerHoursDB
{
    private ?string $idJam = null;
    private ?string $idTiket = null;
    private ?string $idRelawan = null;
    private ?float $durasiTercatat = null;
    private ?float $totalJam = null;
    private ?string $statusVerifikasi = null;
    private ?string $catatanPeninjauan = null;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::koneksi();
    }

    // Catat durasi
    public function catatDurasi(string $idTiket): ?float
    {
        $st = $this->db->prepare('SELECT idRelawan, waktuMulai, waktuSelesai FROM ticket WHERE idTiket = ?');
        $st->execute([$idTiket]);
        $t = $st->fetch();
        if (!$t || !$t['idRelawan']) {
            return null;
        }
        $durasi = round((strtotime($t['waktuSelesai']) - strtotime($t['waktuMulai'])) / 3600, 2);
        $this->db->prepare(
            "INSERT INTO jam_pengabdian (idJam, idTiket, idRelawan, durasiTercatat, statusJam)
             VALUES (?, ?, ?, ?, 'Menunggu Verifikasi')
             ON DUPLICATE KEY UPDATE durasiTercatat = VALUES(durasiTercatat)"
        )->execute(['JAM-' . substr($idTiket, 4), $idTiket, $t['idRelawan'], $durasi]);

        $this->idJam = 'JAM-' . substr($idTiket, 4);
        $this->idTiket = $idTiket;
        $this->idRelawan = $t['idRelawan'];
        $this->durasiTercatat = $durasi;
        $this->perbaruiRekap($t['idRelawan']);
        return $durasi;
    }

    // Total jam
    public function ambilTotalJamPengabdian(string $idRelawan, string $periode = ''): array
    {
        $st = $this->db->prepare(
            "SELECT COALESCE(SUM(j.durasiTercatat), 0) AS total,
                    COALESCE(SUM(CASE WHEN j.statusJam = 'Terverifikasi' THEN j.durasiTercatat END), 0) AS terverifikasi,
                    COALESCE(SUM(CASE WHEN j.statusJam = 'Menunggu Verifikasi' THEN j.durasiTercatat END), 0) AS menunggu,
                    COALESCE(SUM(CASE WHEN j.statusJam = 'Perlu Ditinjau' THEN j.durasiTercatat END), 0) AS perluDitinjau,
                    COUNT(j.idJam) AS jumlahTiket
             FROM jam_pengabdian j JOIN ticket t ON t.idTiket = j.idTiket
             WHERE j.idRelawan = ? AND (? = '' OR t.periode = ?)"
        );
        $st->execute([$idRelawan, $periode, $periode]);
        $hasil = $st->fetch();
        $this->idRelawan = $idRelawan;
        $this->totalJam = (float) $hasil['total'];
        return $hasil;
    }

    // Sesuaikan durasi
    public function sesuaikanJamPengabdian(string $idTiket, float $durasiBaru): string
    {
        $st = $this->db->prepare(
            "UPDATE jam_pengabdian SET durasiTercatat = ?, statusJam = 'Menunggu Verifikasi' WHERE idTiket = ?"
        );
        $st->execute([$durasiBaru, $idTiket]);
        $q = $this->db->prepare('SELECT idRelawan FROM jam_pengabdian WHERE idTiket = ?');
        $q->execute([$idTiket]);
        if ($idRelawan = $q->fetchColumn()) {
            $this->perbaruiRekap($idRelawan);
        }
        return $st->rowCount() === 1 ? 'Jam disesuaikan' : 'Jam tidak berubah';
    }

    // Akumulasi per relawan
    public function ambilAkumulasiJamPerRelawan(string $periode): array
    {
        $st = $this->db->prepare(
            "SELECT j.idRelawan, u.nama,
                    SUM(j.durasiTercatat) AS totalJam,
                    COUNT(*) AS jumlahTiket,
                    COALESCE(SUM(CASE WHEN j.statusJam = 'Terverifikasi' THEN j.durasiTercatat END), 0) AS terverifikasi,
                    COALESCE(SUM(CASE WHEN j.statusJam <> 'Terverifikasi' THEN j.durasiTercatat END), 0) AS menunggu,
                    SUM(j.statusJam = 'Perlu Ditinjau') AS jumlahPerluDitinjau,
                    SUM(j.statusJam = 'Menunggu Verifikasi') AS jumlahMenunggu,
                    h.catatanPeninjauan
             FROM jam_pengabdian j
             JOIN ticket t ON t.idTiket = j.idTiket
             JOIN volunteer v ON v.idRelawan = j.idRelawan
             JOIN user u ON u.userId = v.userId
             LEFT JOIN volunteer_hours h ON h.idRelawan = j.idRelawan
             WHERE t.periode = ?
             GROUP BY j.idRelawan, u.nama, h.catatanPeninjauan
             ORDER BY FIELD(CASE WHEN SUM(j.statusJam = 'Perlu Ditinjau') > 0 THEN 'Perlu Ditinjau'
                                 WHEN SUM(j.statusJam = 'Menunggu Verifikasi') > 0 THEN 'Menunggu'
                                 ELSE 'Terverifikasi' END, 'Menunggu', 'Perlu Ditinjau', 'Terverifikasi'),
                      totalJam DESC"
        );
        $st->execute([$periode]);
        $hasil = $st->fetchAll();
        foreach ($hasil as &$r) {
            $r['status'] = $r['jumlahPerluDitinjau'] > 0 ? 'Perlu Ditinjau'
                : ($r['jumlahMenunggu'] > 0 ? 'Menunggu' : 'Terverifikasi');
        }
        return $hasil;
    }

    // Sahkan jam
    public function simpanStatusTerverifikasi(string $idRelawan, string $periode, string $idAdmin): string
    {
        $this->db->prepare(
            "UPDATE jam_pengabdian j JOIN ticket t ON t.idTiket = j.idTiket
             SET j.statusJam = 'Terverifikasi'
             WHERE j.idRelawan = ? AND t.periode = ? AND j.statusJam <> 'Terverifikasi'"
        )->execute([$idRelawan, $periode]);
        $this->db->prepare(
            'UPDATE volunteer_hours SET diverifikasiOleh = ?, catatanPeninjauan = NULL WHERE idRelawan = ?'
        )->execute([$idAdmin, $idRelawan]);
        $this->perbaruiRekap($idRelawan);
        return 'Terverifikasi';
    }

    // Tandai perlu ditinjau
    public function catatStatusPerluDitinjau(string $idRelawan, string $catatan, string $periode): string
    {
        $this->db->prepare(
            "UPDATE jam_pengabdian j JOIN ticket t ON t.idTiket = j.idTiket
             SET j.statusJam = 'Perlu Ditinjau'
             WHERE j.idRelawan = ? AND t.periode = ? AND j.statusJam = 'Menunggu Verifikasi'"
        )->execute([$idRelawan, $periode]);
        $this->db->prepare('UPDATE volunteer_hours SET catatanPeninjauan = ? WHERE idRelawan = ?')
            ->execute([$catatan, $idRelawan]);
        $this->perbaruiRekap($idRelawan);
        $this->catatanPeninjauan = $catatan;
        return 'Perlu Ditinjau';
    }

    // Data konversi SKM
    public function sediakanDataUntukKonversiSKM(string $idRelawan): array
    {
        $total = $this->ambilTotalJamPengabdian($idRelawan);
        return [
            'idRelawan'          => $idRelawan,
            'jamTerverifikasi'   => (float) $total['terverifikasi'],
            'tanggalPengesahan'  => date('Y-m-d H:i:s'),
        ];
    }

    // Daftar periode
    public function ambilDaftarPeriode(?string $idRelawan = null): array
    {
        $sql = 'SELECT DISTINCT t.periode FROM jam_pengabdian j JOIN ticket t ON t.idTiket = j.idTiket';
        $p = [];
        if ($idRelawan) {
            $sql .= ' WHERE j.idRelawan = ?';
            $p[] = $idRelawan;
        }
        $st = $this->db->prepare($sql . ' ORDER BY t.periode DESC');
        $st->execute($p);
        $daftar = $st->fetchAll(PDO::FETCH_COLUMN);
        $sekarang = periodeDariTanggal(date('Y-m-d'));
        if (!in_array($sekarang, $daftar, true)) {
            array_unshift($daftar, $sekarang);
        }
        return $daftar;
    }

    // Hitung ulang rekap
    public function perbaruiRekap(string $idRelawan): void
    {
        $st = $this->db->prepare(
            "SELECT COALESCE(SUM(durasiTercatat), 0) AS total,
                    SUM(statusJam = 'Perlu Ditinjau') AS tinjau,
                    SUM(statusJam = 'Menunggu Verifikasi') AS menunggu
             FROM jam_pengabdian WHERE idRelawan = ?"
        );
        $st->execute([$idRelawan]);
        $r = $st->fetch();
        $status = $r['tinjau'] > 0 ? 'Perlu Ditinjau' : ($r['menunggu'] > 0 ? 'Menunggu' : 'Terverifikasi');
        $this->db->prepare(
            'INSERT INTO volunteer_hours (idRelawan, totalJam, statusVerifikasi, updatedAt) VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE totalJam = VALUES(totalJam), statusVerifikasi = VALUES(statusVerifikasi), updatedAt = NOW()'
        )->execute([$idRelawan, $r['total'], $status]);
        $this->totalJam = (float) $r['total'];
        $this->statusVerifikasi = $status;
    }
}
