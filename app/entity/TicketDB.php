<?php
// Tabel ticket
class TicketDB
{
    // Status yang bisa dibatalkan
    public const STATUS_DAPAT_DIBATALKAN = ['Menunggu Verifikasi', 'Tervalidasi', 'Menunggu Respons', 'Teralokasi'];

    private ?string $idTiket = null;
    private ?string $idMahasiswa = null;
    private ?string $idRelawan = null;
    private ?string $jenisLayanan = null;
    private ?string $lokasi = null;
    private ?string $jadwalKelas = null;
    private ?string $status = null;
    private ?string $buktiFoto = null;
    private ?bool $konfirmasiMahasiswa = null;
    private ?string $waktuMulai = null;
    private ?string $waktuSelesai = null;
    private ?string $periode = null;
    private ?string $catatanTiket = null;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::koneksi();
    }

    // Pengajuan

    public function buatIdTiket(): string
    {
        $maks = $this->db->query("SELECT MAX(CAST(SUBSTRING(idTiket, 5) AS UNSIGNED)) FROM ticket")->fetchColumn();
        return sprintf('TKT-%04d', ((int) $maks) + 1);
    }

    // Simpan tiket
    public function simpanTiket(array $dataTiket): ?string
    {
        $idTiket = $this->buatIdTiket();
        $riwayat = [[
            'status'  => 'Menunggu Verifikasi',
            'waktu'   => date('Y-m-d H:i:s'),
            'oleh'    => $dataTiket['oleh'],
            'catatan' => $dataTiket['catatanRiwayat'] ?? '',
        ]];
        $st = $this->db->prepare(
            'INSERT INTO ticket (idTiket, idMahasiswa, idSeri, jenisLayanan, lokasi, jadwalKelas, waktuMulai,
                                 waktuSelesai, status, periode, catatanKebutuhan, catatanTiket, dibuatOleh, riwayatStatus)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'Menunggu Verifikasi\', ?, ?, ?, ?, ?)'
        );
        $ok = $st->execute([
            $idTiket,
            $dataTiket['idMahasiswa'],
            $dataTiket['idSeri'] ?? null,
            $dataTiket['jenisLayanan'],
            $dataTiket['lokasi'],
            $dataTiket['waktuMulai'],
            $dataTiket['waktuMulai'],
            $dataTiket['waktuSelesai'],
            periodeDariTanggal($dataTiket['waktuMulai']),
            ($dataTiket['catatanKebutuhan'] ?? '') !== '' ? $dataTiket['catatanKebutuhan'] : null,
            ($dataTiket['catatanTiket'] ?? '') !== '' ? $dataTiket['catatanTiket'] : null,
            $dataTiket['dibuatOleh'] ?? 'Mahasiswa',
            json_encode($riwayat, JSON_UNESCAPED_UNICODE),
        ]);
        return $ok ? $idTiket : null;
    }

    // Simpan tiket berulang
    public function simpanTiketBerulang(array $daftarTiket): array
    {
        $id = [];
        $this->db->beginTransaction();
        try {
            $idSeri = 'SRI-' . substr($this->buatIdTiket(), 4);
            foreach ($daftarTiket as $t) {
                $t['idSeri'] = $idSeri;
                $id[] = $this->simpanTiket($t);
            }
            $this->db->commit();
        } catch (Throwable $ex) {
            $this->db->rollBack();
            return [];
        }
        return $id;
    }

    // Tiket milik mahasiswa
    public function ambilTiketMahasiswa(string $idMahasiswa): array
    {
        $st = $this->db->prepare(
            "SELECT t.*, ur.nama AS namaRelawan
             FROM ticket t
             LEFT JOIN volunteer v ON v.idRelawan = t.idRelawan
             LEFT JOIN user ur ON ur.userId = v.userId
             WHERE t.idMahasiswa = ?
             ORDER BY t.jadwalKelas DESC"
        );
        $st->execute([$idMahasiswa]);
        return array_map([$this, 'uraikan'], $st->fetchAll());
    }

    // Detail tiket
    public function ambilDetail(string $idTiket): ?array
    {
        $st = $this->db->prepare(
            "SELECT t.*, um.nama AS namaMahasiswa, um.emailUB AS emailMahasiswa,
                    ur.nama AS namaRelawan, ur.userId AS userIdRelawan
             FROM ticket t
             JOIN user um ON um.userId = t.idMahasiswa
             LEFT JOIN volunteer v ON v.idRelawan = t.idRelawan
             LEFT JOIN user ur ON ur.userId = v.userId
             WHERE t.idTiket = ?"
        );
        $st->execute([$idTiket]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $row = $this->uraikan($row);
        $this->idTiket = $row['idTiket'];
        $this->idMahasiswa = $row['idMahasiswa'];
        $this->idRelawan = $row['idRelawan'];
        $this->jenisLayanan = $row['jenisLayanan'];
        $this->lokasi = $row['lokasi'];
        $this->jadwalKelas = $row['jadwalKelas'];
        $this->status = $row['status'];
        $this->buktiFoto = $row['buktiFoto'];
        $this->konfirmasiMahasiswa = (bool) $row['konfirmasiMahasiswa'];
        $this->waktuMulai = $row['waktuMulai'];
        $this->waktuSelesai = $row['waktuSelesai'];
        $this->periode = $row['periode'];
        $this->catatanTiket = $row['catatanTiket'];
        return $row;
    }

    // Pembatalan

    // Cek batas pembatalan
    public function cekKelayakanPembatalan(string $idTiket, int $batasJam): string
    {
        $t = $this->ambilDetail($idTiket);
        if (!$t || !in_array($t['status'], self::STATUS_DAPAT_DIBATALKAN, true)) {
            return 'Status Tidak Dapat Dibatalkan';
        }
        $batas = strtotime($t['jadwalKelas']) - $batasJam * 3600;
        return time() <= $batas ? 'Boleh Batal' : 'Lewat Batas';
    }

    // Ubah ke Dibatalkan
    public function ubahStatusDibatalkan(string $idTiket, string $oleh): string
    {
        $this->ubahStatusTiket($idTiket, 'Dibatalkan', $oleh, 'Dibatalkan oleh mahasiswa');
        return $this->cekStatusTiket($idTiket);
    }

    // Verifikasi

    // Tiket menunggu verifikasi
    public function ambilTiketMenungguVerifikasi(string $cari = ''): array
    {
        $st = $this->db->prepare(
            "SELECT t.*, u.nama AS namaMahasiswa
             FROM ticket t JOIN user u ON u.userId = t.idMahasiswa
             WHERE t.status = 'Menunggu Verifikasi'
               AND (? = '' OR t.idTiket LIKE ? OR t.idMahasiswa LIKE ? OR u.nama LIKE ?)
             ORDER BY t.createdAt, t.jadwalKelas"
        );
        $pola = '%' . $cari . '%';
        $st->execute([$cari, $pola, $pola, $pola]);
        return array_map([$this, 'uraikan'], $st->fetchAll());
    }

    public function ambilTiketSeri(string $idSeri): array
    {
        $st = $this->db->prepare('SELECT * FROM ticket WHERE idSeri = ? ORDER BY jadwalKelas');
        $st->execute([$idSeri]);
        return array_map([$this, 'uraikan'], $st->fetchAll());
    }

    // Ubah status tiket
    public function ubahStatusTiket(string $idTiket, string $status, string $oleh, string $catatan = ''): bool
    {
        $st = $this->db->prepare('UPDATE ticket SET status = ?, updatedAt = NOW() WHERE idTiket = ?');
        $st->execute([$status, $idTiket]);
        $this->catatRiwayat($idTiket, $status, $oleh, $catatan);
        return $st->rowCount() === 1;
    }

    // Penugasan

    // Antrean pencarian
    public function ambilAntreanPencarian(): array
    {
        $st = $this->db->query(
            "SELECT * FROM ticket WHERE status = 'Tervalidasi' AND idRelawan IS NULL AND waktuMulai > NOW()
             ORDER BY jadwalKelas"
        );
        return array_map([$this, 'uraikan'], $st->fetchAll());
    }

    // Tetapkan kandidat
    public function tetapkanKandidat(string $idTiket, string $idRelawan): bool
    {
        $st = $this->db->prepare(
            "UPDATE ticket SET idRelawan = ?, status = 'Menunggu Respons', updatedAt = NOW()
             WHERE idTiket = ? AND status = 'Tervalidasi'"
        );
        $st->execute([$idRelawan, $idTiket]);
        if ($st->rowCount() === 1) {
            $this->catatRiwayat($idTiket, 'Menunggu Respons', 'SISTEM', 'Kandidat: ' . $idRelawan);
            return true;
        }
        return false;
    }

    // Detail penugasan
    public function ambilDetailPenugasan(string $idTiket): ?array
    {
        return $this->ambilDetail($idTiket);
    }

    // Penugasan relawan
    public function ambilPenugasanRelawan(string $idRelawan): array
    {
        $st = $this->db->prepare(
            "SELECT t.*, u.nama AS namaMahasiswa FROM ticket t JOIN user u ON u.userId = t.idMahasiswa
             WHERE t.idRelawan = ? AND t.status IN ('Menunggu Respons', 'Teralokasi', 'Dilaksanakan')
             ORDER BY FIELD(t.status, 'Menunggu Respons', 'Dilaksanakan', 'Teralokasi'), t.jadwalKelas"
        );
        $st->execute([$idRelawan]);
        return array_map([$this, 'uraikan'], $st->fetchAll());
    }

    // Ubah status penugasan
    public function ubahStatusPenugasan(string $idTiket, string $status, string $oleh): bool
    {
        return $this->ubahStatusTiket($idTiket, $status, $oleh);
    }

    // Kembali ke antrean
    public function kembalikanKeStatusTervalidasi(string $idTiket, string $oleh, string $catatan): bool
    {
        $st = $this->db->prepare(
            "UPDATE ticket SET status = 'Tervalidasi', idRelawan = NULL, updatedAt = NOW() WHERE idTiket = ?"
        );
        $st->execute([$idTiket]);
        $this->catatRiwayat($idTiket, 'Tervalidasi', $oleh, $catatan);
        return $st->rowCount() === 1;
    }

    // Relawan yang menolak
    public function relawanTidakTersedia(string $idTiket): array
    {
        $t = $this->ambilDetail($idTiket);
        $hasil = [];
        foreach ($t['riwayat'] ?? [] as $r) {
            if (in_array($r['status'], ['Penugasan Ditolak', 'Pembatalan Darurat'], true)) {
                $hasil[] = $r['oleh'];
            }
        }
        return $hasil;
    }

    // Tandai sedang dilaksanakan
    public function sinkronStatusPelaksanaan(): void
    {
        $st = $this->db->query("SELECT idTiket FROM ticket WHERE status = 'Teralokasi' AND waktuMulai <= NOW()");
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $this->ubahStatusTiket($id, 'Dilaksanakan', 'SISTEM', 'Sesi pendampingan dimulai');
        }
    }

    // Pelaksanaan

    // Cek status
    public function cekStatusTiket(string $idTiket): ?string
    {
        $st = $this->db->prepare('SELECT status FROM ticket WHERE idTiket = ?');
        $st->execute([$idTiket]);
        $s = $st->fetchColumn();
        return $s === false ? null : $s;
    }

    // Simpan foto bukti
    public function simpanBuktiFoto(string $fileFoto, string $idTiket): ?string
    {
        $st = $this->db->prepare('UPDATE ticket SET buktiFoto = ?, updatedAt = NOW() WHERE idTiket = ?');
        $st->execute([$fileFoto, $idTiket]);
        return $st->rowCount() === 1 ? $fileFoto : null;
    }

    public function hapusBuktiFoto(string $idTiket): bool
    {
        $st = $this->db->prepare("UPDATE ticket SET buktiFoto = NULL WHERE idTiket = ? AND status = 'Dilaksanakan'");
        $st->execute([$idTiket]);
        return $st->rowCount() === 1;
    }

    // Cek bukti
    public function cekKeberadaanBukti(string $idTiket): string
    {
        $st = $this->db->prepare('SELECT buktiFoto FROM ticket WHERE idTiket = ?');
        $st->execute([$idTiket]);
        return $st->fetchColumn() ? 'Ada' : 'Tidak Ada';
    }

    // Ubah ke Selesai
    public function ubahStatusSelesai(string $idTiket, string $oleh): string
    {
        $this->ubahStatusTiket($idTiket, 'Selesai', $oleh, 'Bukti kegiatan diunggah');
        return (string) $this->cekStatusTiket($idTiket);
    }

    // Detail dan bukti
    public function ambilDetailBukti(string $idTiket): ?array
    {
        return $this->ambilDetail($idTiket);
    }

    // Simpan konfirmasi
    public function simpanKonfirmasi(string $idTiket): bool
    {
        $st = $this->db->prepare('UPDATE ticket SET konfirmasiMahasiswa = TRUE, updatedAt = NOW() WHERE idTiket = ?');
        $st->execute([$idTiket]);
        return $st->rowCount() === 1;
    }

    // Perbarui verifikasi
    public function perbaruiVerifikasi(string $idTiket, string $oleh): string
    {
        $this->catatRiwayat($idTiket, 'Dikonfirmasi Mahasiswa', $oleh, 'Kehadiran relawan dikonfirmasi');
        $st = $this->db->prepare('SELECT konfirmasiMahasiswa FROM ticket WHERE idTiket = ?');
        $st->execute([$idTiket]);
        return (int) $st->fetchColumn() === 1 ? 'Terverifikasi' : 'Belum Terverifikasi';
    }

    // Tandai bermasalah
    public function tandaiBermasalah(string $idTiket, string $oleh, string $idLaporan): string
    {
        $this->ubahStatusTiket($idTiket, 'Bermasalah', $oleh, 'Laporan ' . $idLaporan);
        return (string) $this->cekStatusTiket($idTiket);
    }

    // Riwayat dan rekap

    // Riwayat penugasan
    public function ambilRiwayatPenugasan(string $idRelawan, string $periode = ''): array
    {
        $st = $this->db->prepare(
            "SELECT t.*, j.durasiTercatat, j.statusJam
             FROM ticket t LEFT JOIN jam_pengabdian j ON j.idTiket = t.idTiket
             WHERE t.idRelawan = ? AND t.status IN ('Dilaksanakan', 'Selesai', 'Bermasalah')
               AND (? = '' OR t.periode = ?)
             ORDER BY t.jadwalKelas DESC"
        );
        $st->execute([$idRelawan, $periode, $periode]);
        return array_map([$this, 'uraikan'], $st->fetchAll());
    }

    // Tiket terkait laporan
    public function ambilTiketTerkait(string $idTiket): ?array
    {
        return $this->ambilDetail($idTiket);
    }

    // Tambah catatan
    public function perbaruiCatatanTiket(string $idTiket, string $keputusan): string
    {
        $t = $this->ambilDetail($idTiket);
        $catatan = trim(($t['catatanTiket'] ? $t['catatanTiket'] . "\n" : '') . $keputusan);
        $st = $this->db->prepare('UPDATE ticket SET catatanTiket = ?, updatedAt = NOW() WHERE idTiket = ?');
        $st->execute([$catatan, $idTiket]);
        return $catatan;
    }

    // Jumlah tiket periode
    public function ambilJumlahTiket(array $periode): array
    {
        $st = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'Selesai'), 0) AS selesai,
                    COALESCE(SUM(status IN ('Menunggu Verifikasi', 'Tervalidasi', 'Menunggu Respons', 'Teralokasi', 'Dilaksanakan')), 0) AS berjalan,
                    COALESCE(SUM(status IN ('Ditolak', 'Dibatalkan')), 0) AS batal
             FROM ticket WHERE DATE(jadwalKelas) BETWEEN ? AND ?"
        );
        $st->execute([$periode['awal'], $periode['akhir']]);
        $hasil = $st->fetch();

        $perMinggu = [];
        $mulai = strtotime($periode['awal']);
        $akhir = strtotime($periode['akhir']);
        $q = $this->db->prepare('SELECT COUNT(*) FROM ticket WHERE DATE(jadwalKelas) BETWEEN ? AND ?');
        for ($i = 1, $t = $mulai; $t <= $akhir && $i <= 12; $i++, $t = strtotime('+7 days', $t)) {
            $ujung = min(strtotime('+6 days', $t), $akhir);
            $q->execute([date('Y-m-d', $t), date('Y-m-d', $ujung)]);
            $perMinggu[] = ['label' => 'Mg ' . $i, 'jumlah' => (int) $q->fetchColumn(),
                'rentang' => tglIndo(date('Y-m-d', $t), 'tanpaTahun') . '–' . tglIndo(date('Y-m-d', $ujung), 'tanpaTahun')];
        }
        $hasil['perMinggu'] = $perMinggu;
        return $hasil;
    }

    // Jumlah periode baru
    public function ambilDataPeriodeBaru(array $periode): array
    {
        return $this->ambilJumlahTiket($periode);
    }

    // Ringkasan status
    public function hitungStatus(?string $idMahasiswa = null, ?string $idRelawan = null): array
    {
        $sql = 'SELECT status, COUNT(*) n FROM ticket WHERE 1 = 1';
        $p = [];
        if ($idMahasiswa) {
            $sql .= ' AND idMahasiswa = ?';
            $p[] = $idMahasiswa;
        }
        if ($idRelawan) {
            $sql .= ' AND idRelawan = ?';
            $p[] = $idRelawan;
        }
        $st = $this->db->prepare($sql . ' GROUP BY status');
        $st->execute($p);
        $hasil = [];
        foreach ($st->fetchAll() as $r) {
            $hasil[$r['status']] = (int) $r['n'];
        }
        return $hasil;
    }

    // Bantuan

    public function catatRiwayat(string $idTiket, string $status, string $oleh, string $catatan = ''): void
    {
        $st = $this->db->prepare('SELECT riwayatStatus FROM ticket WHERE idTiket = ?');
        $st->execute([$idTiket]);
        $riwayat = json_decode((string) $st->fetchColumn(), true) ?: [];
        $entri = ['status' => $status, 'waktu' => date('Y-m-d H:i:s'), 'oleh' => $oleh];
        if ($catatan !== '') {
            $entri['catatan'] = $catatan;
        }
        $riwayat[] = $entri;
        $this->db->prepare('UPDATE ticket SET riwayatStatus = ? WHERE idTiket = ?')
            ->execute([json_encode($riwayat, JSON_UNESCAPED_UNICODE), $idTiket]);
    }

    private function uraikan(array $row): array
    {
        $row['riwayat'] = json_decode((string) ($row['riwayatStatus'] ?? ''), true) ?: [];
        return $row;
    }
}
