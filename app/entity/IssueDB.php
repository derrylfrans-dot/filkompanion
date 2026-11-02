<?php
// Tabel issue
class IssueDB
{
    private ?string $idLaporan = null;
    private ?string $idTiket = null;
    private ?string $deskripsi = null;
    private ?string $keputusan = null;
    private ?string $statusTindakLanjut = null;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::koneksi();
    }

    public function buatIdLaporan(): string
    {
        $maks = $this->db->query("SELECT MAX(CAST(SUBSTRING(idLaporan, 5) AS UNSIGNED)) FROM issue")->fetchColumn();
        return sprintf('LPR-%04d', ((int) $maks) + 1);
    }

    // Simpan laporan
    public function simpanLaporan(array $laporan): ?string
    {
        $id = $laporan['idLaporan'] ?? $this->buatIdLaporan();
        $st = $this->db->prepare(
            'INSERT INTO issue (idLaporan, idTiket, jenisKendala, deskripsi, lampiran) VALUES (?, ?, ?, ?, ?)'
        );
        $ok = $st->execute([
            $id,
            $laporan['idTiket'],
            $laporan['jenisKendala'],
            $laporan['deskripsi'],
            $laporan['lampiran'] !== '' ? $laporan['lampiran'] : null,
        ]);
        return $ok ? $id : null;
    }

    private const SQL_LAPORAN =
        "SELECT i.*, t.jenisLayanan, t.waktuMulai, t.waktuSelesai, t.idRelawan,
                um.nama AS namaPelapor, ur.nama AS namaRelawan, j.durasiTercatat
         FROM issue i
         JOIN ticket t ON t.idTiket = i.idTiket
         JOIN user um ON um.userId = t.idMahasiswa
         LEFT JOIN volunteer v ON v.idRelawan = t.idRelawan
         LEFT JOIN user ur ON ur.userId = v.userId
         LEFT JOIN jam_pengabdian j ON j.idTiket = t.idTiket";

    // Laporan dan tiket
    public function ambilLaporanDanTiketTerkait(): array
    {
        return $this->db->query(
            self::SQL_LAPORAN . " ORDER BY FIELD(i.statusTindakLanjut, 'Belum Ditindaklanjuti', 'Ditindaklanjuti'), i.idLaporan DESC"
        )->fetchAll();
    }

    // Daftar laporan
    public function ambilDaftarLaporan(): array
    {
        return $this->ambilLaporanDanTiketTerkait();
    }

    public function ambilLaporan(string $idLaporan): ?array
    {
        $st = $this->db->prepare(self::SQL_LAPORAN . ' WHERE i.idLaporan = ?');
        $st->execute([$idLaporan]);
        $l = $st->fetch();
        if (!$l) {
            return null;
        }
        $this->idLaporan = $l['idLaporan'];
        $this->idTiket = $l['idTiket'];
        $this->deskripsi = $l['deskripsi'];
        $this->keputusan = $l['keputusan'];
        $this->statusTindakLanjut = $l['statusTindakLanjut'];
        return $l;
    }

    // Simpan keputusan
    public function simpanKeputusanLaporan(string $idLaporan, string $keputusan, string $catatanAdmin): ?string
    {
        $st = $this->db->prepare(
            "UPDATE issue SET keputusan = ?, catatanAdmin = ?, statusTindakLanjut = 'Ditindaklanjuti',
                    tanggalTindakLanjut = NOW()
             WHERE idLaporan = ? AND statusTindakLanjut = 'Belum Ditindaklanjuti'"
        );
        $st->execute([$keputusan, $catatanAdmin !== '' ? $catatanAdmin : null, $idLaporan]);
        return $st->rowCount() === 1 ? 'Ditindaklanjuti' : null;
    }

    // Keputusan tanpa perubahan
    public function catatKeputusanTanpaPerubahanJam(string $idLaporan, string $keputusan): string
    {
        $st = $this->db->prepare(
            "UPDATE issue SET catatanAdmin = CONCAT(COALESCE(catatanAdmin, ''),
                    IF(catatanAdmin IS NULL OR catatanAdmin = '', '', ' '), '(Jam pengabdian tidak diubah.)')
             WHERE idLaporan = ?"
        );
        $st->execute([$idLaporan]);
        return 'Tercatat tanpa perubahan jam';
    }

    // Laporan belum selesai
    public function hitungLaporanTerbuka(string $idRelawan, string $periode = ''): int
    {
        $st = $this->db->prepare(
            "SELECT COUNT(*) FROM issue i JOIN ticket t ON t.idTiket = i.idTiket
             WHERE t.idRelawan = ? AND i.statusTindakLanjut = 'Belum Ditindaklanjuti'
               AND (? = '' OR t.periode = ?)"
        );
        $st->execute([$idRelawan, $periode, $periode]);
        return (int) $st->fetchColumn();
    }

    // Jumlah laporan
    public function hitungLaporan(array $periode): int
    {
        $st = $this->db->prepare(
            'SELECT COUNT(*) FROM issue i JOIN ticket t ON t.idTiket = i.idTiket
             WHERE DATE(t.jadwalKelas) BETWEEN ? AND ?'
        );
        $st->execute([$periode['awal'], $periode['akhir']]);
        return (int) $st->fetchColumn();
    }
}
