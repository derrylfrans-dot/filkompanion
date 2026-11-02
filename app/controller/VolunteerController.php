<?php
// Pengelolaan relawan
class VolunteerController extends Controller
{
    private const FORMAT_DOKUMEN = ['pdf', 'jpg', 'jpeg', 'png'];

    private VolunteerDB $volunteerDB;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->volunteerDB = new VolunteerDB();
        $this->notificationService = new NotificationService(new UserDB());
    }

    // Pendaftaran

    // Halaman pendaftaran
    public function membukaPendaftaranRelawan(): void
    {
        $p = $this->wajibPeran(['Relawan Pendamping']);
        $pendaftaran = $this->volunteerDB->ambilBerdasarkanUser($p['userId']);
        if ($pendaftaran && $pendaftaran['statusPendaftaran'] === 'Aktif') {
            $this->redirect('relawan/profil');
        }
        $this->render('AKN-UI03_VolunteerRegistrationUI', [
            'judulHalaman' => 'Pendaftaran Relawan Pendamping',
            'menuAktif'    => 'daftar',
            'formRelawan'  => $this->memintaFormPendaftaran(),
            'pendaftaran'  => $pendaftaran,
            'isian'        => $this->ambilIsianLama(),
        ]);
    }

    // Siapkan form
    public function memintaFormPendaftaran(): array
    {
        return [
            'opsiKeahlian' => daftarLayanan(),
            'format'       => 'PDF, JPG, atau PNG',
            'batasMB'      => 5,
        ];
    }

    // Kirim pendaftaran
    public function mengirimPendaftaran(): void
    {
        $p = $this->wajibPeran(['Relawan Pendamping']);
        $this->wajibPost();
        $tambahanNama = $_POST['tambahan'] ?? [];
        $dataRelawan = [
            'userId'           => $p['userId'],
            'keahlian'         => array_values(array_intersect((array) ($_POST['keahlian'] ?? []), array_keys(daftarLayanan()))),
            'keahlianTambahan' => array_values(array_map(fn ($t) => ['nama' => trim($t['nama'] ?? '')], $tambahanNama)),
        ];
        $dokumen = [
            'utama'    => Upload::daftarBerkas($_FILES['dokumen'] ?? null),
            'tambahan' => array_values($this->berkasPerIndeks($_FILES['tambahanDokumen'] ?? null, array_keys($tambahanNama))),
        ];
        $hasil = $this->submitPendaftaran($dataRelawan, $dokumen);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Pendaftaran berhasil dikirim',
                'ID pendaftaran ' . VolunteerDB::idPendaftaran($hasil['idPendaftaran']) . ' berstatus Menunggu Validasi Admin FILKOM.');
        } else {
            $this->simpanIsianLama(['keahlian' => $dataRelawan['keahlian'], 'tambahan' => $dataRelawan['keahlianTambahan']]);
            $this->setFlash('error', $hasil['pesan'], implode(' ', $hasil['detail'] ?? []));
        }
        $this->redirect('relawan/daftar');
    }

    // Proses pendaftaran
    public function submitPendaftaran(array $dataRelawan, array $dokumen): array
    {
        if (empty($dataRelawan['keahlian']) || empty($dokumen['utama'])) {
            return ['status' => 'Gagal', 'pesan' => 'Data dan dokumen keahlian wajib diisi',
                'detail' => ['Pilih minimal satu keahlian dan unggah minimal satu dokumen bukti.']];
        }
        $lama = $this->volunteerDB->ambilBerdasarkanUser($dataRelawan['userId']);
        if ($lama && in_array($lama['statusPendaftaran'], ['Menunggu Validasi', 'Aktif'], true)) {
            return ['status' => 'Gagal', 'pesan' => 'Pendaftaran sudah tercatat dengan status ' . $lama['statusPendaftaran']];
        }
        // Cek setiap berkas
        $kesalahan = [];
        foreach (array_merge($dokumen['utama'], array_filter($dokumen['tambahan'])) as $berkas) {
            if ($pesan = Upload::periksa($berkas, self::FORMAT_DOKUMEN)) {
                $kesalahan[] = $pesan;
            }
        }
        $statusValidasi = $this->volunteerDB->validasiKelengkapan($dataRelawan, $dokumen);
        $kesalahan = array_merge($kesalahan, $statusValidasi['bagian']);
        if ($kesalahan) {
            return ['status' => 'Perlu Perbaikan', 'pesan' => 'Data atau berkas perlu diperbaiki', 'detail' => $kesalahan];
        }

        // Simpan berkas
        $idRelawan = $lama['idRelawan'] ?? $this->volunteerDB->buatIdRelawan();
        if ($lama) {
            foreach (explode(',', (string) $lama['dokumenKeahlian']) as $f) {
                Upload::hapus('dokumen', $f);
            }
        }
        $meta = ['utama' => [], 'tambahan' => []];
        foreach ($dokumen['utama'] as $i => $b) {
            $meta['utama'][] = ['file' => Upload::simpan($b, 'dokumen', $idRelawan . '_' . ($i + 1)),
                'nama' => $b['name'], 'ukuran' => (int) $b['size']];
        }
        foreach ($dokumen['tambahan'] as $i => $b) {
            $meta['tambahan'][$i] = ['file' => Upload::simpan($b, 'dokumen', $idRelawan . '_T' . ($i + 1)),
                'nama' => $b['name'], 'ukuran' => (int) $b['size']];
        }
        $dataRelawan['idRelawan'] = $idRelawan;
        $idPendaftaran = $this->volunteerDB->simpanPendaftaran($dataRelawan, $meta);
        if ($idPendaftaran === null) {
            return ['status' => 'Gagal', 'pesan' => 'Pendaftaran tidak berhasil disimpan'];
        }
        // Status awal Menunggu Validasi
        $this->notificationService->kirimNotifikasiPendaftaran($idPendaftaran, $dataRelawan['userId']);
        return ['status' => 'Berhasil', 'idPendaftaran' => $idPendaftaran, 'statusPendaftaran' => 'Menunggu Validasi'];
    }

    // Profil

    // Halaman profil
    public function membukaProfilDanKetersediaan(): void
    {
        $relawan = $this->wajibRelawanAktif();
        $this->render('AKN-UI04_VolunteerProfileUI', [
            'judulHalaman' => 'Profil dan Ketersediaan Relawan',
            'menuAktif'    => 'profil-relawan',
            'dataRelawan'  => $this->ambilKeahlianDanKetersediaan($relawan['idRelawan']),
        ]);
    }

    // Ambil keahlian
    public function ambilKeahlianDanKetersediaan(string $idRelawan): ?array
    {
        return $this->volunteerDB->ambilKeahlianDanKetersediaan($idRelawan);
    }

    // Kirim perubahan
    public function submitPerubahanRelawan(): void
    {
        $relawan = $this->wajibRelawanAktif();
        $this->wajibPost();

        $ketersediaan = [];
        foreach ((array) ($_POST['blok'] ?? []) as $hari => $daftarBlok) {
            if (in_array($hari, VolunteerDB::HARI, true)) {
                $ketersediaan[$hari] = array_values(array_intersect((array) $daftarBlok, array_keys(VolunteerDB::BLOK)));
            }
        }
        $tambahanLama = $relawan['dataRelawanData']['keahlianTambahan'];
        $dipertahankan = array_map('intval', (array) ($_POST['tambahanTetap'] ?? []));
        $tambahanNama = $_POST['tambahan'] ?? [];
        $dataPerubahan = [
            'idRelawan'        => $relawan['idRelawan'],
            'keahlian'         => array_values(array_intersect((array) ($_POST['keahlian'] ?? []), array_keys(daftarLayanan()))),
            'ketersediaan'     => array_filter($ketersediaan),
            'tambahanLama'     => array_values(array_intersect_key($tambahanLama, array_flip($dipertahankan))),
            'tambahanDihapus'  => array_values(array_diff_key($tambahanLama, array_flip($dipertahankan))),
            'tambahanBaru'     => array_values(array_map(fn ($t) => ['nama' => trim($t['nama'] ?? '')], $tambahanNama)),
            'berkasTambahan'   => array_values($this->berkasPerIndeks($_FILES['tambahanDokumen'] ?? null, array_keys($tambahanNama))),
        ];
        $hasil = $this->validasiDanSimpanPerubahan($dataPerubahan);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Perubahan berhasil disimpan', 'Data siap dipakai untuk pencocokan penugasan.');
        } else {
            $this->setFlash('error', $hasil['pesan'], implode(' ', $hasil['detail'] ?? []));
        }
        $this->redirect('relawan/profil');
    }

    // Validasi lalu simpan
    public function validasiDanSimpanPerubahan(array $dataPerubahan): array
    {
        if (($dataPerubahan['idRelawan'] ?? '') === '') {
            return ['status' => 'Gagal', 'pesan' => 'ID relawan tidak valid'];
        }
        $dataRelawan = $this->volunteerDB->ambilKeahlianDanKetersediaan($dataPerubahan['idRelawan']);
        if ($dataRelawan === null || $dataRelawan['statusPendaftaran'] !== 'Aktif') {
            return ['status' => 'Ditolak', 'pesan' => 'Profil hanya dapat dikelola oleh relawan aktif'];
        }
        if (empty($dataPerubahan['keahlian']) || empty($dataPerubahan['ketersediaan'])) {
            return ['status' => 'Tidak Valid', 'pesan' => 'Keahlian dan ketersediaan wajib diisi',
                'detail' => ['Pilih minimal satu keahlian dan satu blok waktu tersedia.']];
        }
        $detail = [];
        foreach ($dataPerubahan['tambahanBaru'] as $i => $t) {
            $berkas = $dataPerubahan['berkasTambahan'][$i] ?? null;
            if ($t['nama'] === '') {
                $detail[] = 'Nama keahlian tambahan wajib diisi.';
            }
            if (!$berkas) {
                $detail[] = 'Setiap keahlian tambahan wajib disertai dokumen bukti.';
            } elseif ($pesan = Upload::periksa($berkas, self::FORMAT_DOKUMEN)) {
                $detail[] = $pesan;
            }
        }
        if ($detail) {
            return ['status' => 'Tidak Valid', 'pesan' => 'Keahlian tambahan belum lengkap', 'detail' => $detail];
        }
        $tambahan = $dataPerubahan['tambahanLama'];
        $urut = count($dataRelawan['dataRelawanData']['keahlianTambahan']) + 1;
        foreach ($dataPerubahan['tambahanBaru'] as $i => $t) {
            $b = $dataPerubahan['berkasTambahan'][$i];
            $tambahan[] = ['nama' => $t['nama'],
                'file' => Upload::simpan($b, 'dokumen', $dataPerubahan['idRelawan'] . '_T' . ($urut++) . '_' . date('His')),
                'namaAsli' => $b['name'], 'ukuran' => (int) $b['size']];
        }
        foreach ($dataPerubahan['tambahanDihapus'] as $t) {
            Upload::hapus('dokumen', $t['file']);
        }
        $penyimpananBerhasil = $this->volunteerDB->simpanPerubahan([
            'idRelawan'        => $dataPerubahan['idRelawan'],
            'keahlian'         => $dataPerubahan['keahlian'],
            'ketersediaan'     => $dataPerubahan['ketersediaan'],
            'keahlianTambahan' => $tambahan,
        ]);
        if ($penyimpananBerhasil) {
            return ['status' => 'Berhasil', 'pesan' => 'Profil dan ketersediaan diperbarui'];
        }
        return ['status' => 'Gagal', 'pesan' => 'Perubahan tidak dapat disimpan'];
    }

    // Validasi

    // Halaman validasi
    public function membukaMenuValidasiRelawan(): void
    {
        $this->wajibPeran(['Admin FILKOM']);
        $cari = (string) $this->input('cari');
        $daftar = $this->volunteerDB->ambilSemuaPendaftaran($cari);
        $idDipilih = (string) $this->input('id');
        if ($idDipilih === '') {
            $menunggu = $this->volunteerDB->ambilPendaftaranMenunggu();
            $idDipilih = $menunggu[0]['idRelawan'] ?? ($daftar[0]['idRelawan'] ?? '');
        }
        $this->render('AKN-UI05_VolunteerValidationUI', [
            'judulHalaman' => 'Validasi Pendaftaran Relawan',
            'menuAktif'    => 'admin-relawan',
            'daftar'       => $daftar,
            'ringkasan'    => $this->volunteerDB->hitungStatusPendaftaran(),
            'detail'       => $idDipilih ? $this->volunteerDB->ambilKeahlianDanKetersediaan($idDipilih) : null,
            'cari'         => $cari,
        ]);
    }

    // Kirim keputusan
    public function mengirimKeputusanValidasi(): void
    {
        $this->wajibPeran(['Admin FILKOM']);
        $this->wajibPost();
        $keputusan = [
            'idPendaftaran' => (string) $this->input('idPendaftaran'),
            'hasil'         => (string) $this->input('hasil'),
            'alasan'        => mb_substr((string) $this->input('alasan'), 0, 255),
        ];
        $hasil = $this->perbaruiStatusPendaftaran($keputusan);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Keputusan validasi disimpan',
                VolunteerDB::idPendaftaran($keputusan['idPendaftaran']) . ' kini berstatus ' . $hasil['statusPendaftaran'] . '.');
        } else {
            $this->setFlash('error', 'Keputusan belum tersimpan', $hasil['pesan']);
        }
        $this->redirect('admin/relawan', ['id' => $keputusan['idPendaftaran']]);
    }

    // Perbarui status
    public function perbaruiStatusPendaftaran(array $keputusan): array
    {
        $daftarMenunggu = $this->volunteerDB->ambilPendaftaranMenunggu();
        $pendaftaran = null;
        foreach ($daftarMenunggu as $d) {
            if ($d['idRelawan'] === $keputusan['idPendaftaran']) {
                $pendaftaran = $d;
            }
        }
        if ($pendaftaran === null) {
            return ['status' => 'Gagal', 'pesan' => 'Pendaftaran tidak ditemukan atau sudah diproses'];
        }
        if ($keputusan['hasil'] === 'Setujui') {
            $statusBaru = 'Aktif';
        } elseif ($keputusan['hasil'] === 'Tolak' && trim($keputusan['alasan']) !== '') {
            $statusBaru = 'Ditolak';
        } else {
            return ['status' => 'Gagal', 'pesan' => 'Keputusan atau alasan penolakan belum lengkap'];
        }
        $keputusan['statusBaru'] = $statusBaru;
        $hasil = $this->volunteerDB->perbaruiStatusPendaftaran($keputusan);
        if ($hasil === $statusBaru) {
            if ($statusBaru === 'Aktif') {
                $this->kirimHasilValidasi($statusBaru, $pendaftaran['userId']);
            } else {
                $this->notificationService->kirimInformasiPenolakan($keputusan['alasan'], $pendaftaran['userId']);
            }
            return ['status' => 'Berhasil', 'statusPendaftaran' => $statusBaru];
        }
        return ['status' => 'Gagal', 'pesan' => 'Status pendaftaran tidak berhasil diperbarui'];
    }

    // Kirim hasil validasi
    public function kirimHasilValidasi(string $statusBaru, string $userIdRelawan): void
    {
        $this->notificationService->kirimHasilValidasi($statusBaru === 'Aktif' ? 'Relawan Aktif' : $statusBaru, $userIdRelawan);
    }

    // Buka dokumen
    public function lihatDokumen(): void
    {
        $p = $this->wajibPeran(['Admin FILKOM', 'Relawan Pendamping']);
        $relawan = $this->volunteerDB->ambilKeahlianDanKetersediaan((string) $this->input('id'));
        $nama = (string) $this->input('file');
        if (!$relawan || !in_array($nama, explode(',', (string) $relawan['dokumenKeahlian']), true)
            || ($p['rolePengguna'] !== 'Admin FILKOM' && $relawan['userId'] !== $p['userId'])) {
            http_response_code(404);
            exit('Dokumen tidak ditemukan.');
        }
        Upload::kirim('dokumen', $nama);
    }

    // Bantuan

    private function wajibRelawanAktif(): array
    {
        $p = $this->wajibPeran(['Relawan Pendamping']);
        $relawan = $this->volunteerDB->ambilBerdasarkanUser($p['userId']);
        if (!$relawan || $relawan['statusPendaftaran'] !== 'Aktif') {
            $this->setFlash('info', 'Menu aktif setelah validasi', 'Lengkapi pendaftaran relawan dan tunggu validasi Admin FILKOM.');
            $this->redirect('relawan/daftar');
        }
        return $relawan;
    }

    // Ambil berkas tambahan
    private function berkasPerIndeks(?array $input, array $indeks): array
    {
        $hasil = [];
        foreach ($indeks as $i) {
            $ada = $input && isset($input['error'][$i]) && $input['error'][$i] !== UPLOAD_ERR_NO_FILE;
            $hasil[$i] = $ada ? [
                'name' => $input['name'][$i], 'type' => $input['type'][$i], 'tmp_name' => $input['tmp_name'][$i],
                'error' => $input['error'][$i], 'size' => $input['size'][$i],
            ] : null;
        }
        return $hasil;
    }
}
