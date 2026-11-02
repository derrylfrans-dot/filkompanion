<?php
// Layanan notifikasi
class NotificationService
{
    private string $channel = 'in-app';
    private string $berkas;
    private UserDB $userDB;

    public function __construct(UserDB $userDB)
    {
        $this->userDB = $userDB;
        $this->berkas = __DIR__ . '/../../storage/notifications.json';
    }

    // Notifikasi relawan

    // Pendaftaran masuk
    public function kirimNotifikasiPendaftaran(string $idPendaftaran, string $userIdPemohon): void
    {
        $kode = VolunteerDB::idPendaftaran($idPendaftaran);
        $this->kirim($userIdPemohon, 'Pendaftaran relawan terkirim',
            "Pendaftaran $kode berstatus Menunggu Validasi Admin FILKOM.", url('relawan/daftar'));
        foreach ($this->userDB->ambilAdmin() as $a) {
            $this->kirim($a['userId'], 'Pendaftaran relawan baru',
                "Pendaftaran $kode menunggu validasi.", url('admin/relawan', ['id' => $idPendaftaran]));
        }
    }

    // Hasil validasi
    public function kirimHasilValidasi(string $statusBaru, string $userIdRelawan): void
    {
        $this->kirim($userIdRelawan, 'Pendaftaran relawan disetujui',
            'Selamat, status kamu kini ' . $statusBaru . '. Lengkapi profil dan ketersediaan.', url('relawan/profil'));
    }

    // Alasan penolakan
    public function kirimInformasiPenolakan(string $alasan, string $userIdRelawan): void
    {
        $this->kirim($userIdRelawan, 'Pendaftaran relawan ditolak', 'Alasan: ' . $alasan, url('relawan/daftar'));
    }

    // Notifikasi tiket

    // Perubahan status tiket
    public function kirimNotifikasiStatus(string $idTiket, string $status, string $userIdMahasiswa, string $catatan = ''): void
    {
        $this->kirim($userIdMahasiswa, "Tiket $idTiket: $status", $catatan ?: 'Status tiket pendampinganmu diperbarui.',
            url('tiket', ['id' => $idTiket]));
    }

    // Info pembatalan
    public function sampaikanPembatalanJikaPerlu(array $tiket): void
    {
        if (!empty($tiket['userIdRelawan'])) {
            $this->kirim($tiket['userIdRelawan'], "Tiket {$tiket['idTiket']} dibatalkan",
                'Mahasiswa membatalkan tiket. Blok jadwalmu telah dibuka kembali.', url('penugasan'));
        }
    }

    // Notifikasi penugasan

    // Penugasan baru
    public function kirimNotifikasiPenugasan(string $userIdRelawan, string $idTiket): void
    {
        $this->kirim($userIdRelawan, 'Penugasan baru menunggu responsmu',
            "Tiket $idTiket cocok dengan keahlian dan ketersediaanmu.", url('penugasan', ['id' => $idTiket]));
    }

    // Konfirmasi jadwal
    public function kirimKonfirmasiPenjadwalan(string $idMahasiswa, string $idTiket, string $namaRelawan): void
    {
        $this->kirim($idMahasiswa, "Relawan ditugaskan untuk $idTiket",
            "$namaRelawan menerima penugasan. Status tiket: Teralokasi.", url('tiket', ['id' => $idTiket]));
    }

    // Notifikasi kendala

    // Laporan ke Admin
    public function kirimNotifikasiAdmin(string $idLaporan, string $idTiket): void
    {
        foreach ($this->userDB->ambilAdmin() as $a) {
            $this->kirim($a['userId'], 'Laporan kendala baru', "$idLaporan untuk tiket $idTiket menunggu tindak lanjut.",
                url('admin/kendala', ['id' => $idLaporan]));
        }
    }

    // Status pengiriman
    public function notifikasiTerkirim(string $status): string
    {
        return 'Notifikasi ' . strtolower($status) . ' melalui kanal ' . $this->channel;
    }

    // Penyimpanan

    public function ambilNotifikasi(string $userId, int $batas = 8): array
    {
        $semua = array_values(array_filter($this->baca(), fn ($n) => $n['userId'] === $userId));
        usort($semua, fn ($a, $b) => strcmp($b['waktu'], $a['waktu']));
        return [
            'belumDibaca' => count(array_filter($semua, fn ($n) => !$n['dibaca'])),
            'daftar'      => array_slice($semua, 0, $batas),
        ];
    }

    public function tandaiDibaca(string $userId): void
    {
        $this->ubah(function (array $data) use ($userId) {
            foreach ($data as &$n) {
                if ($n['userId'] === $userId) {
                    $n['dibaca'] = true;
                }
            }
            return $data;
        });
    }

    private function kirim(string $userId, string $judul, string $pesan, string $tautan): void
    {
        $this->ubah(function (array $data) use ($userId, $judul, $pesan, $tautan) {
            $data[] = [
                'id'     => bin2hex(random_bytes(6)),
                'userId' => $userId,
                'judul'  => $judul,
                'pesan'  => $pesan,
                'tautan' => $tautan,
                'waktu'  => date('Y-m-d H:i:s'),
                'dibaca' => false,
            ];
            return array_slice($data, -500);
        });
    }

    private function baca(): array
    {
        if (!is_file($this->berkas)) {
            return [];
        }
        return json_decode((string) file_get_contents($this->berkas), true) ?: [];
    }

    // Baca-tulis dengan kunci
    private function ubah(callable $fungsi): void
    {
        $fp = fopen($this->berkas, 'c+');
        if (!$fp) {
            return;
        }
        flock($fp, LOCK_EX);
        $isi = stream_get_contents($fp);
        $data = json_decode($isi ?: '[]', true) ?: [];
        $data = $fungsi($data);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
