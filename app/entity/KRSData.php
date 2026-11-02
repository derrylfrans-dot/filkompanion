<?php
// Tabel krs_data
class KRSData
{
    private ?string $idMahasiswa = null;
    private array $jadwalKRS = [];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::koneksi();
    }

    // Ambil jadwal KRS
    public function ambilJadwalMahasiswa(string $idMahasiswa): ?array
    {
        $st = $this->db->prepare('SELECT jadwalKRS FROM krs_data WHERE idMahasiswa = ?');
        $st->execute([$idMahasiswa]);
        $json = $st->fetchColumn();
        if ($json === false) {
            $this->idMahasiswa = null;
            $this->jadwalKRS = [];
            return null;
        }
        $this->idMahasiswa = $idMahasiswa;
        $this->jadwalKRS = json_decode($json, true) ?: [];
        return $this->jadwalKRS;
    }

    // Cek kesesuaian jadwal
    public function periksaKesesuaian(array $jadwalTiket): array
    {
        $hari = VolunteerDB::namaHari($jadwalTiket['waktuMulai']);
        $mulai = date('H:i', strtotime($jadwalTiket['waktuMulai']));
        $selesai = date('H:i', strtotime($jadwalTiket['waktuSelesai']));
        $kandidat = null;
        foreach ($this->jadwalKRS as $kelas) {
            if ($kelas['hari'] !== $hari) {
                continue;
            }
            $kandidat = $kandidat ?? $kelas;
            if ($mulai >= $kelas['mulai'] && $selesai <= $kelas['selesai']) {
                return ['hasil' => 'SESUAI', 'kelas' => $kelas];
            }
        }
        return ['hasil' => 'TIDAK SESUAI', 'kelas' => $kandidat];
    }
}
