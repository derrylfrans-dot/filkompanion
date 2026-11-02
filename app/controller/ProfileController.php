<?php
// Profil mahasiswa
class ProfileController extends Controller
{
    public const KATEGORI = ['Tunarungu', 'Tunadaksa'];

    private UserDB $userDB;

    public function __construct()
    {
        $this->userDB = new UserDB();
    }

    // Halaman profil
    public function membukaMenuProfil(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas']);
        $dataProfil = $this->memintaDataProfil($p['userId']);
        $this->tampilkanFormProfil($dataProfil);
    }

    // Ambil profil
    public function memintaDataProfil(string $idMahasiswa): array
    {
        if ($idMahasiswa === '') {
            return ['status' => 'Gagal', 'pesan' => 'ID mahasiswa tidak valid'];
        }
        $dataProfil = $this->userDB->ambilProfilMahasiswa($idMahasiswa);
        if ($dataProfil === null) {
            $dataProfil = [
                'userId' => $idMahasiswa, 'nama' => '', 'emailUB' => '', 'kategoriDisabilitas' => null,
                'kebutuhanPendampingan' => null, 'catatanKebutuhan' => null, 'updatedAt' => null,
            ];
        }
        return $dataProfil;
    }

    // Tampilkan form
    public function tampilkanFormProfil(array $dataProfil): void
    {
        $isianLama = $this->ambilIsianLama();
        $this->render('AKN-UI02_ProfileUI', [
            'judulHalaman' => 'Profil Kebutuhan Disabilitas',
            'menuAktif'    => 'profil',
            'profil'       => array_merge($dataProfil, $isianLama),
            'opsiKategori' => self::KATEGORI,
            'opsiKebutuhan'=> array_values(daftarLayanan()),
        ]);
    }

    // Kirim perubahan
    public function submitPerubahanProfil(): void
    {
        $p = $this->wajibPeran(['Mahasiswa Disabilitas']);
        $this->wajibPost();
        $dataProfilBaru = [
            'idMahasiswa'           => $p['userId'],
            'kategoriDisabilitas'   => (string) $this->input('kategoriDisabilitas'),
            'kebutuhanPendampingan' => (string) $this->input('kebutuhanPendampingan'),
            'catatanKebutuhan'      => (string) $this->input('catatanKebutuhan'),
        ];
        $hasil = $this->validasiDanSimpanProfil($dataProfilBaru);
        if ($hasil['status'] === 'Berhasil') {
            $this->setFlash('sukses', 'Profil berhasil disimpan', 'Siap dipakai untuk pencocokan relawan.');
        } else {
            $this->simpanIsianLama($dataProfilBaru);
            $this->setFlash('error', $hasil['pesan'], implode(' ', $hasil['bagianWajib'] ?? []));
        }
        $this->redirect('profil');
    }

    // Validasi lalu simpan
    public function validasiDanSimpanProfil(array $dataProfilBaru): array
    {
        if (($dataProfilBaru['idMahasiswa'] ?? '') === '') {
            return ['status' => 'Gagal', 'pesan' => 'Identitas mahasiswa wajib tersedia'];
        }
        $bagianWajib = [];
        if (!in_array($dataProfilBaru['kategoriDisabilitas'], self::KATEGORI, true)) {
            $bagianWajib[] = 'Pilih kategori disabilitas.';
        }
        if (!in_array($dataProfilBaru['kebutuhanPendampingan'], daftarLayanan(), true)) {
            $bagianWajib[] = 'Pilih kebutuhan utama.';
        }
        if (mb_strlen($dataProfilBaru['catatanKebutuhan']) > 255) {
            $bagianWajib[] = 'Catatan kebutuhan maksimal 255 karakter.';
        }
        if ($bagianWajib) {
            return ['status' => 'Tidak Valid',
                'pesan' => 'Kategori disabilitas dan kebutuhan pendampingan wajib diisi', 'bagianWajib' => $bagianWajib];
        }
        $statusValidasi = $this->userDB->validasiDanSimpanProfil($dataProfilBaru);
        if ($statusValidasi === 'SUCCESS') {
            return ['status' => 'Berhasil', 'pesan' => 'Profil berhasil disimpan'];
        }
        return ['status' => 'Gagal', 'pesan' => 'Profil tidak dapat disimpan'];
    }
}
