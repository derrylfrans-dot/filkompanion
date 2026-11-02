<?php
// Tabel volunteer
class VolunteerDB
{
    // Blok waktu kalender
    public const BLOK = [
        '08-10' => ['08:00', '10:00'],
        '10-12' => ['10:00', '12:00'],
        '13-15' => ['13:00', '15:00'],
        '15-17' => ['15:00', '17:00'],
    ];
    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    private ?string $idRelawan = null;
    private ?array $dataRelawan = null;
    private ?string $keahlian = null;
    private ?array $ketersediaan = null;
    private ?string $statusPendaftaran = null;
    private ?string $dokumenKeahlian = null;
    private ?string $statusRelawan = null;
    private ?array $dataPerforma = null;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::koneksi();
    }

    // Pendaftaran

    // ID relawan baru
    public function buatIdRelawan(): string
    {
        $maks = $this->db->query("SELECT MAX(CAST(SUBSTRING(idRelawan, 4) AS UNSIGNED)) FROM volunteer")->fetchColumn();
        return sprintf('RLW%03d', ((int) $maks) + 1);
    }

    // ID pendaftaran
    public static function idPendaftaran(string $idRelawan): string
    {
        return 'REG-' . substr($idRelawan, 3);
    }

    // Cek kelengkapan
    public function validasiKelengkapan(array $dataRelawan, array $dokumen): array
    {
        $bagian = [];
        if (empty($dataRelawan['keahlian'])) {
            $bagian[] = 'Pilih minimal satu keahlian pendampingan.';
        }
        if (count($dokumen['utama'] ?? []) === 0) {
            $bagian[] = 'Unggah minimal satu dokumen bukti keahlian.';
        }
        foreach ($dataRelawan['keahlianTambahan'] ?? [] as $i => $kt) {
            if (trim($kt['nama'] ?? '') === '') {
                $bagian[] = 'Nama keahlian tambahan ' . ($i + 1) . ' wajib diisi.';
            }
            if (empty($dokumen['tambahan'][$i])) {
                $bagian[] = 'Dokumen bukti untuk keahlian tambahan ' . ($i + 1) . ' wajib diunggah.';
            }
        }
        return ['valid' => count($bagian) === 0, 'bagian' => $bagian];
    }

    // Simpan pendaftaran
    public function simpanPendaftaran(array $dataRelawan, array $dokumen): ?string
    {
        $tambahan = [];
        foreach ($dataRelawan['keahlianTambahan'] ?? [] as $i => $kt) {
            $tambahan[] = [
                'nama'     => trim($kt['nama']),
                'file'     => $dokumen['tambahan'][$i]['file'],
                'namaAsli' => $dokumen['tambahan'][$i]['nama'],
                'ukuran'   => $dokumen['tambahan'][$i]['ukuran'],
            ];
        }
        $data = ['dokumen' => $dokumen['utama'], 'keahlianTambahan' => $tambahan];
        $semuaFile = array_merge(array_column($dokumen['utama'], 'file'), array_column($tambahan, 'file'));

        $st = $this->db->prepare(
            "INSERT INTO volunteer (idRelawan, userId, dataRelawan, keahlian, ketersediaan,
                                    statusPendaftaran, statusRelawan, dokumenKeahlian, tanggalDaftar)
             VALUES (?, ?, ?, ?, ?, 'Menunggu Validasi', 'Nonaktif', ?, NOW())
             ON DUPLICATE KEY UPDATE dataRelawan = VALUES(dataRelawan), keahlian = VALUES(keahlian),
                 statusPendaftaran = 'Menunggu Validasi', dokumenKeahlian = VALUES(dokumenKeahlian),
                 alasanPenolakan = NULL, tanggalDaftar = NOW()"
        );
        $st->execute([
            $dataRelawan['idRelawan'],
            $dataRelawan['userId'],
            json_encode($data, JSON_UNESCAPED_UNICODE),
            implode(',', $dataRelawan['keahlian']),
            json_encode(['mingguan' => new stdClass(), 'terkunci' => []]),
            implode(',', $semuaFile),
        ]);
        return $dataRelawan['idRelawan'];
    }

    public function ambilBerdasarkanUser(string $userId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM volunteer WHERE userId = ?');
        $st->execute([$userId]);
        $row = $st->fetch();
        return $row ? $this->uraikan($row) : null;
    }

    // Profil dan ketersediaan

    // Ambil keahlian
    public function ambilKeahlianDanKetersediaan(string $idRelawan): ?array
    {
        $st = $this->db->prepare(
            'SELECT v.*, u.nama, u.emailUB FROM volunteer v JOIN user u ON u.userId = v.userId WHERE v.idRelawan = ?'
        );
        $st->execute([$idRelawan]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $row = $this->uraikan($row);
        $this->idRelawan = $row['idRelawan'];
        $this->keahlian = $row['keahlian'];
        $this->ketersediaan = $row['ketersediaanData'];
        $this->dataRelawan = $row['dataRelawanData'];
        $this->statusPendaftaran = $row['statusPendaftaran'];
        $this->statusRelawan = $row['statusRelawan'];
        $this->dokumenKeahlian = $row['dokumenKeahlian'];
        return $row;
    }

    // Simpan perubahan
    public function simpanPerubahan(array $dataPerubahan): bool
    {
        $lama = $this->ambilKeahlianDanKetersediaan($dataPerubahan['idRelawan']);
        $ketersediaan = [
            'mingguan' => (object) $dataPerubahan['ketersediaan'],
            'terkunci' => $lama['ketersediaanData']['terkunci'] ?? [],
        ];
        $data = $lama['dataRelawanData'];
        $data['keahlianTambahan'] = array_values($dataPerubahan['keahlianTambahan']);
        $semuaFile = array_merge(
            array_column($data['dokumen'] ?? [], 'file'),
            array_column($data['keahlianTambahan'], 'file')
        );
        $st = $this->db->prepare(
            'UPDATE volunteer SET keahlian = ?, ketersediaan = ?, dataRelawan = ?, dokumenKeahlian = ?, updatedAt = NOW()
             WHERE idRelawan = ?'
        );
        return $st->execute([
            implode(',', $dataPerubahan['keahlian']),
            json_encode($ketersediaan),
            json_encode($data, JSON_UNESCAPED_UNICODE),
            implode(',', $semuaFile),
            $dataPerubahan['idRelawan'],
        ]);
    }

    // Validasi pendaftaran

    // Pendaftaran menunggu
    public function ambilPendaftaranMenunggu(): array
    {
        $st = $this->db->query(
            "SELECT v.*, u.nama, u.emailUB FROM volunteer v JOIN user u ON u.userId = v.userId
             WHERE v.statusPendaftaran = 'Menunggu Validasi' ORDER BY v.tanggalDaftar"
        );
        return array_map([$this, 'uraikan'], $st->fetchAll());
    }

    // Semua pendaftaran
    public function ambilSemuaPendaftaran(string $cari = ''): array
    {
        $st = $this->db->prepare(
            "SELECT v.*, u.nama, u.emailUB FROM volunteer v JOIN user u ON u.userId = v.userId
             WHERE (? = '' OR u.nama LIKE ? OR u.emailUB LIKE ? OR v.idRelawan LIKE ? OR ? LIKE CONCAT('%', SUBSTRING(v.idRelawan, 4)))
             ORDER BY FIELD(v.statusPendaftaran, 'Menunggu Validasi', 'Aktif', 'Ditolak'), v.idRelawan DESC"
        );
        $pola = '%' . $cari . '%';
        $st->execute([$cari, $pola, $pola, $pola, $cari]);
        return array_map([$this, 'uraikan'], $st->fetchAll());
    }

    public function hitungStatusPendaftaran(): array
    {
        $hasil = ['Menunggu Validasi' => 0, 'Aktif' => 0, 'Ditolak' => 0];
        foreach ($this->db->query('SELECT statusPendaftaran s, COUNT(*) n FROM volunteer GROUP BY s') as $r) {
            $hasil[$r['s']] = (int) $r['n'];
        }
        return $hasil;
    }

    // Perbarui status
    public function perbaruiStatusPendaftaran(array $keputusan): ?string
    {
        $aktif = $keputusan['statusBaru'] === 'Aktif';
        $st = $this->db->prepare(
            "UPDATE volunteer
             SET statusPendaftaran = ?, statusRelawan = ?, alasanPenolakan = ?, tanggalValidasi = NOW()
             WHERE idRelawan = ? AND statusPendaftaran = 'Menunggu Validasi'"
        );
        $st->execute([
            $keputusan['statusBaru'],
            $aktif ? 'Aktif' : 'Nonaktif',
            $aktif ? null : $keputusan['alasan'],
            $keputusan['idPendaftaran'],
        ]);
        if ($st->rowCount() === 1 && $aktif) {
            $this->db->prepare('INSERT IGNORE INTO volunteer_hours (idRelawan) VALUES (?)')
                ->execute([$keputusan['idPendaftaran']]);
        }
        return $st->rowCount() === 1 ? $keputusan['statusBaru'] : null;
    }

    // Pencocokan jadwal

    // Relawan sesuai keahlian
    public function cariRelawanSesuaiSkill(array $tiket): array
    {
        $kode = layananSingkat($tiket['jenisLayanan']);
        $st = $this->db->prepare(
            "SELECT v.*, u.nama FROM volunteer v JOIN user u ON u.userId = v.userId
             WHERE v.statusRelawan = 'Aktif' AND v.statusPendaftaran = 'Aktif'
               AND FIND_IN_SET(?, v.keahlian) > 0"
        );
        $st->execute([$kode]);
        return array_map([$this, 'uraikan'], $st->fetchAll());
    }

    // Blok yang beririsan
    public static function blokTerkait(string $mulai, string $selesai): array
    {
        $jamMulai = date('H:i', strtotime($mulai));
        $jamSelesai = date('H:i', strtotime($selesai));
        $hasil = [];
        foreach (self::BLOK as $kode => [$a, $b]) {
            if ($jamMulai < $b && $jamSelesai > $a) {
                $hasil[] = $kode;
            }
        }
        return $hasil;
    }

    public static function namaHari(string $tanggal): ?string
    {
        $i = (int) date('N', strtotime($tanggal)); // 1 = Senin
        return self::HARI[$i - 1] ?? null;
    }

    // Cek ketersediaan
    public function cekKetersediaan(string $idRelawan, array $jadwal): bool
    {
        $relawan = $this->ambilKeahlianDanKetersediaan($idRelawan);
        if (!$relawan) {
            return false;
        }
        $hari = self::namaHari($jadwal['waktuMulai']);
        $blok = self::blokTerkait($jadwal['waktuMulai'], $jadwal['waktuSelesai']);
        if ($hari === null || count($blok) === 0) {
            return false;
        }
        $mingguan = $relawan['ketersediaanData']['mingguan'][$hari] ?? [];
        $tanggal = date('Y-m-d', strtotime($jadwal['waktuMulai']));
        foreach ($blok as $b) {
            if (!in_array($b, $mingguan, true)) {
                return false;
            }
            foreach ($relawan['ketersediaanData']['terkunci'] as $k) {
                if ($k['tanggal'] === $tanggal && $k['blok'] === $b
                    && ($jadwal['idTiket'] ?? null) !== $k['idTiket']) {
                    return false;
                }
            }
        }
        return true;
    }

    // Kunci jadwal
    public function kunciJadwal(string $idRelawan, array $jadwal): bool
    {
        $relawan = $this->ambilKeahlianDanKetersediaan($idRelawan);
        if (!$relawan) {
            return false;
        }
        $data = $relawan['ketersediaanData'];
        $tanggal = date('Y-m-d', strtotime($jadwal['waktuMulai']));
        foreach (self::blokTerkait($jadwal['waktuMulai'], $jadwal['waktuSelesai']) as $b) {
            $data['terkunci'][] = ['tanggal' => $tanggal, 'blok' => $b, 'idTiket' => $jadwal['idTiket']];
        }
        return $this->simpanKetersediaan($idRelawan, $data);
    }

    // Lepas kunci jadwal
    public function lepasKunciJadwal(string $idRelawan, string $idTiket): bool
    {
        $relawan = $this->ambilKeahlianDanKetersediaan($idRelawan);
        if (!$relawan) {
            return false;
        }
        $data = $relawan['ketersediaanData'];
        $data['terkunci'] = array_values(array_filter(
            $data['terkunci'],
            fn ($k) => $k['idTiket'] !== $idTiket
        ));
        return $this->simpanKetersediaan($idRelawan, $data);
    }

    private function simpanKetersediaan(string $idRelawan, array $data): bool
    {
        $data['mingguan'] = (object) $data['mingguan'];
        $st = $this->db->prepare('UPDATE volunteer SET ketersediaan = ? WHERE idRelawan = ?');
        return $st->execute([json_encode($data), $idRelawan]);
    }

    // Performa

    // Performa per periode
    public function ambilPerformaRelawan(array $periode): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM (
               SELECT v.idRelawan, u.nama,
                 (SELECT COUNT(*) FROM ticket t
                   WHERE t.idRelawan = v.idRelawan AND t.status = 'Selesai'
                     AND DATE(t.jadwalKelas) BETWEEN :a1 AND :b1) AS tiketSelesai,
                 (SELECT COALESCE(SUM(j.durasiTercatat), 0) FROM jam_pengabdian j
                   JOIN ticket t ON t.idTiket = j.idTiket
                   WHERE j.idRelawan = v.idRelawan AND j.statusJam = 'Terverifikasi'
                     AND DATE(t.jadwalKelas) BETWEEN :a2 AND :b2) AS jamTerverifikasi,
                 (SELECT COUNT(*) FROM issue i JOIN ticket t ON t.idTiket = i.idTiket
                   WHERE t.idRelawan = v.idRelawan
                     AND DATE(t.jadwalKelas) BETWEEN :a3 AND :b3) AS laporanKendala
               FROM volunteer v JOIN user u ON u.userId = v.userId
               WHERE v.statusPendaftaran = 'Aktif'
             ) rekap
             WHERE tiketSelesai > 0 OR jamTerverifikasi > 0 OR laporanKendala > 0
             ORDER BY tiketSelesai DESC, jamTerverifikasi DESC"
        );
        $st->execute([
            'a1' => $periode['awal'], 'b1' => $periode['akhir'],
            'a2' => $periode['awal'], 'b2' => $periode['akhir'],
            'a3' => $periode['awal'], 'b3' => $periode['akhir'],
        ]);
        $hasil = $st->fetchAll();
        foreach ($hasil as $r) {
            $this->db->prepare('UPDATE volunteer SET dataPerforma = ? WHERE idRelawan = ?')->execute([
                json_encode(['periode' => $periode, 'tiketSelesai' => (int) $r['tiketSelesai'],
                    'jamTerverifikasi' => (float) $r['jamTerverifikasi'], 'laporanKendala' => (int) $r['laporanKendala']]),
                $r['idRelawan'],
            ]);
        }
        return $hasil;
    }

    // Performa periode baru
    public function ambilPerformaPeriodeBaru(array $periode): array
    {
        return $this->ambilPerformaRelawan($periode);
    }

    // Urai kolom JSON
    private function uraikan(array $row): array
    {
        $row['keahlianList'] = array_values(array_filter(explode(',', (string) $row['keahlian'])));
        $row['dataRelawanData'] = json_decode((string) $row['dataRelawan'], true) ?: ['dokumen' => [], 'keahlianTambahan' => []];
        $row['dataRelawanData'] += ['dokumen' => [], 'keahlianTambahan' => []];
        $k = json_decode((string) $row['ketersediaan'], true) ?: [];
        $row['ketersediaanData'] = ['mingguan' => $k['mingguan'] ?? [], 'terkunci' => $k['terkunci'] ?? []];
        return $row;
    }
}
