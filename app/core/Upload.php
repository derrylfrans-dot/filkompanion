<?php
// Penanganan unggahan
class Upload
{
    public const MIME = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
    ];

    public static function folder(string $jenis): string
    {
        return __DIR__ . '/../../storage/uploads/' . $jenis;
    }

    // Rapikan $_FILES
    public static function daftarBerkas(?array $input): array
    {
        if (!$input || !isset($input['name'])) {
            return [];
        }
        if (!is_array($input['name'])) {
            return $input['error'] === UPLOAD_ERR_NO_FILE ? [] : [$input];
        }
        $hasil = [];
        foreach ($input['name'] as $i => $nama) {
            if ($input['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $hasil[] = [
                'name'     => $nama,
                'type'     => $input['type'][$i],
                'tmp_name' => $input['tmp_name'][$i],
                'error'    => $input['error'][$i],
                'size'     => $input['size'][$i],
            ];
        }
        return $hasil;
    }

    // Cek format & ukuran
    public static function periksa(array $berkas, array $ekstensiDiizinkan): ?string
    {
        $cfg = require __DIR__ . '/../../config/config.php';
        $batas = $cfg['app']['batasUnggahMB'] * 1024 * 1024;
        if ($berkas['error'] === UPLOAD_ERR_INI_SIZE || $berkas['error'] === UPLOAD_ERR_FORM_SIZE) {
            return 'Ukuran "' . $berkas['name'] . '" melebihi batas upload_max_filesize server (' . ini_get('upload_max_filesize') . ').';
        }
        if ($berkas['error'] !== UPLOAD_ERR_OK) {
            return 'Berkas "' . $berkas['name'] . '" gagal diunggah (kode ' . $berkas['error'] . ').';
        }
        $ext = strtolower(pathinfo($berkas['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $ekstensiDiizinkan, true)) {
            return 'Format "' . $berkas['name'] . '" tidak didukung. Gunakan ' . strtoupper(implode(', ', $ekstensiDiizinkan)) . '.';
        }
        if ($berkas['size'] > $batas) {
            return 'Ukuran "' . $berkas['name'] . '" melebihi ' . $cfg['app']['batasUnggahMB'] . ' MB.';
        }
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($f, $berkas['tmp_name']);
            finfo_close($f);
            if (!in_array($mime, self::MIME[$ext], true)) {
                return 'Isi berkas "' . $berkas['name'] . '" tidak sesuai dengan formatnya.';
            }
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png'], true) && @getimagesize($berkas['tmp_name']) === false) {
            return 'Berkas "' . $berkas['name'] . '" bukan gambar yang valid.';
        }
        return null;
    }

    // Simpan berkas
    public static function simpan(array $berkas, string $jenis, string $namaDasar): string
    {
        $ext = strtolower(pathinfo($berkas['name'], PATHINFO_EXTENSION));
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $nama = preg_replace('/[^A-Za-z0-9_\-]/', '', $namaDasar) . '.' . $ext;
        $tujuan = self::folder($jenis) . '/' . $nama;
        if (!is_dir(self::folder($jenis))) {
            mkdir(self::folder($jenis), 0775, true);
        }
        if (!move_uploaded_file($berkas['tmp_name'], $tujuan)) {
            throw new RuntimeException('Berkas tidak dapat disimpan ke folder ' . $jenis);
        }
        return $nama;
    }

    public static function hapus(string $jenis, ?string $nama): void
    {
        if ($nama && preg_match('/^[A-Za-z0-9_\-]+\.(pdf|jpg|png)$/', $nama)) {
            $path = self::folder($jenis) . '/' . $nama;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    // Kirim ke browser
    public static function kirim(string $jenis, string $nama, ?string $namaUnduh = null): void
    {
        if (!preg_match('/^[A-Za-z0-9_\-]+\.(pdf|jpg|png)$/', $nama, $m)) {
            http_response_code(404);
            exit('Berkas tidak ditemukan.');
        }
        $path = self::folder($jenis) . '/' . $nama;
        if (!is_file($path)) {
            http_response_code(404);
            exit('Berkas tidak ditemukan.');
        }
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'][$m[1]];
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $namaUnduh ?: $nama) . '"');
        readfile($path);
        exit;
    }
}
