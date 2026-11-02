<?php
// Tabel user
class UserDB
{
    private ?string $userId = null;
    private ?string $emailUB = null;
    private ?string $rolePengguna = null;
    private ?string $kategoriDisabilitas = null;
    private ?string $kebutuhanPendampingan = null;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::koneksi();
    }

    // Ambil peran
    public function ambilPeranPengguna(string $emailUB): ?string
    {
        $user = $this->ambilBerdasarkanEmail($emailUB);
        return $user['rolePengguna'] ?? null;
    }

    public function ambilBerdasarkanEmail(string $emailUB): ?array
    {
        $st = $this->db->prepare('SELECT * FROM user WHERE emailUB = ?');
        $st->execute([strtolower($emailUB)]);
        $row = $st->fetch();
        if ($row) {
            $this->isiAtribut($row);
        }
        return $row ?: null;
    }

    public function ambilBerdasarkanId(string $userId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM user WHERE userId = ?');
        $st->execute([$userId]);
        return $st->fetch() ?: null;
    }

    // Ambil profil
    public function ambilProfilMahasiswa(string $idMahasiswa): ?array
    {
        $st = $this->db->prepare(
            "SELECT userId, nama, emailUB, kategoriDisabilitas, kebutuhanPendampingan,
                    catatanKebutuhan, updatedAt
             FROM user WHERE userId = ? AND rolePengguna = 'Mahasiswa Disabilitas'"
        );
        $st->execute([$idMahasiswa]);
        $row = $st->fetch();
        if ($row) {
            $this->isiAtribut($row);
        }
        return $row ?: null;
    }

    // Simpan profil
    public function validasiDanSimpanProfil(array $dataProfilBaru): string
    {
        $st = $this->db->prepare(
            'UPDATE user
             SET kategoriDisabilitas = ?, kebutuhanPendampingan = ?, catatanKebutuhan = ?, updatedAt = NOW()
             WHERE userId = ?'
        );
        $berhasil = $st->execute([
            $dataProfilBaru['kategoriDisabilitas'],
            $dataProfilBaru['kebutuhanPendampingan'],
            $dataProfilBaru['catatanKebutuhan'] !== '' ? $dataProfilBaru['catatanKebutuhan'] : null,
            $dataProfilBaru['idMahasiswa'],
        ]);
        return $berhasil ? 'SUCCESS' : 'GAGAL';
    }

    // Cari mahasiswa
    public function cariMahasiswa(string $kata): array
    {
        $st = $this->db->prepare(
            "SELECT userId, nama, emailUB, kategoriDisabilitas, kebutuhanPendampingan
             FROM user
             WHERE rolePengguna = 'Mahasiswa Disabilitas'
               AND (userId LIKE ? OR emailUB LIKE ? OR nama LIKE ?)
             ORDER BY nama LIMIT 5"
        );
        $pola = '%' . $kata . '%';
        $st->execute([$pola, $pola, $pola]);
        return $st->fetchAll();
    }

    // Daftar Admin
    public function ambilAdmin(): array
    {
        return $this->db->query("SELECT userId, nama FROM user WHERE rolePengguna = 'Admin FILKOM'")->fetchAll();
    }

    private function isiAtribut(array $row): void
    {
        $this->userId = $row['userId'];
        $this->emailUB = $row['emailUB'];
        $this->rolePengguna = $row['rolePengguna'] ?? null;
        $this->kategoriDisabilitas = $row['kategoriDisabilitas'];
        $this->kebutuhanPendampingan = $row['kebutuhanPendampingan'];
    }
}
