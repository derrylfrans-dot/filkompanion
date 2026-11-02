<?php
// Fungsi bantu tampilan

// Escape HTML
function e($nilai): string
{
    return htmlspecialchars((string) ($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

// URL rute
function url(string $rute, array $param = []): string
{
    $q = array_merge(['r' => $rute], $param);
    return 'index.php?' . http_build_query($q);
}

// URL aset
function aset(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

// Input token CSRF
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Auth::tokenCsrf()) . '">';
}

// Ikon SVG
function ikon(string $nama, string $kelas = ''): string
{
    static $p = [
        'home'          => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'user'          => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users'         => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/><path d="M18 14.6a6 6 0 0 1 3.5 5.4"/>',
        'user-plus'     => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M19 8v6M16 11h6"/>',
        'ticket'        => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/><path d="M13 5v2M13 17v2M13 11v2"/>',
        'file-text'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/>',
        'file-plus'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6M9 15h6"/>',
        'calendar'      => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'clipboard'     => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/>',
        'history'       => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
        'grid'          => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'alert'         => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4M12 17h.01"/>',
        'clock'         => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'chart'         => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
        'bell'          => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'help'          => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'chevron-down'  => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'arrow-right'   => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'lock'          => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'mail'          => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'log-in'        => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
        'log-out'       => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'info'          => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        'alert-circle'  => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
        'check'         => '<path d="M20 6 9 17l-5-5"/>',
        'check-circle'  => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'x-circle'      => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
        'x'             => '<path d="M18 6 6 18M6 6l12 12"/>',
        'minus'         => '<path d="M5 12h14"/>',
        'plus'          => '<path d="M12 5v14M5 12h14"/>',
        'trash'         => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6M14 11v6"/>',
        'upload'        => '<path d="M4 14.9A7 7 0 1 1 15.7 8h1.8a4.5 4.5 0 0 1 2.5 8.2"/><path d="M12 12v9"/><path d="m16 16-4-4-4 4"/>',
        'eye'           => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'search'        => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'map-pin'       => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'repeat'        => '<path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/>',
        'send'          => '<path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/>',
        'filter'        => '<path d="M22 3H2l8 9.46V19l4 2v-8.54z"/>',
        'download'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'image'         => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
        'message'       => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/><path d="M12 8v4M12 16h.01"/>',
    ];
    $isi = $p[$nama] ?? $p['info'];
    return '<svg class="ikon ' . e($kelas) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $isi . '</svg>';
}

// Format tanggal
function tglIndo(?string $waktu, string $format = 'pendek'): string
{
    if (empty($waktu)) {
        return '-';
    }
    $hari       = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $hariPendek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    $bulan      = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $bulanPanjang = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($waktu);
    $tgl = date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
    switch ($format) {
        case 'hari':
            return $hari[(int) date('w', $t)] . ', ' . $tgl;
        case 'hariPendek':
            return $hariPendek[(int) date('w', $t)] . ', ' . $tgl;
        case 'lengkap':
            return $tgl . ', ' . date('H.i', $t);
        case 'panjang':
            return date('j', $t) . ' ' . $bulanPanjang[(int) date('n', $t)] . ' ' . date('Y', $t);
        case 'namaHari':
            return $hari[(int) date('w', $t)];
        case 'tanpaTahun':
            return date('j', $t) . ' ' . $bulan[(int) date('n', $t)];
        default:
            return $tgl;
    }
}

function jam(?string $waktu): string
{
    return $waktu ? date('H.i', strtotime($waktu)) : '-';
}

function rentangJam(?string $mulai, ?string $selesai): string
{
    return jam($mulai) . '–' . jam($selesai);
}

// Format angka
function angka($nilai): string
{
    $n = round((float) $nilai, 2);
    $s = rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    return $s === '' ? '0' : $s;
}

function ukuranFile(int $byte): string
{
    if ($byte >= 1048576) {
        return angka($byte / 1048576) . ' MB';
    }
    return max(1, (int) round($byte / 1024)) . ' KB';
}

function inisial(string $nama): string
{
    $kata = preg_split('/\s+/', trim($nama));
    $hasil = mb_substr($kata[0] ?? '', 0, 1) . mb_substr($kata[1] ?? '', 0, 1);
    return mb_strtoupper($hasil);
}

// Label semester
function periodeDariTanggal(string $tanggal): string
{
    $t = strtotime($tanggal);
    $bln = (int) date('n', $t);
    $thn = (int) date('Y', $t);
    if ($bln >= 8) {
        return 'Semester Ganjil ' . $thn . '/' . ($thn + 1);
    }
    if ($bln === 1) {
        return 'Semester Ganjil ' . ($thn - 1) . '/' . $thn;
    }
    return 'Semester Genap ' . ($thn - 1) . '/' . $thn;
}

// Daftar layanan
function daftarLayanan(): array
{
    return [
        'JBI'               => 'Juru Bahasa Isyarat (JBI)',
        'Note-taker'        => 'Note-taker',
        'Pemandu Mobilitas' => 'Pemandu Mobilitas',
    ];
}

// Nama layanan singkat
function layananSingkat(string $layanan): string
{
    return $layanan === 'Juru Bahasa Isyarat (JBI)' ? 'JBI' : $layanan;
}

// Lokasi singkat
function ruangSingkat(string $lokasi): string
{
    return preg_match('/Ruang\s+([\w.\-]+)/u', $lokasi, $m) ? $m[1] : $lokasi;
}

// Badge status
function badge(string $status, ?string $label = null): string
{
    $peta = [
        'Menunggu Verifikasi'   => ['kuning', 'clock'],
        'Menunggu Validasi'     => ['kuning', 'clock', 'Menunggu'],
        'Menunggu'              => ['kuning', 'clock'],
        'Menunggu Respons'      => ['kuning', 'clock'],
        'Belum Ditindaklanjuti' => ['kuning', 'clock', 'Menunggu Tindak Lanjut'],
        'Tervalidasi'           => ['biru', 'info'],
        'Teralokasi'            => ['biru', 'info'],
        'Dilaksanakan'          => ['biru', 'info'],
        'Belum Dikirim'         => ['biru', 'info'],
        'Selesai'               => ['hijau', 'check-circle'],
        'Aktif'                 => ['hijau', 'check-circle'],
        'Relawan Aktif'         => ['hijau', 'check-circle'],
        'Terverifikasi'         => ['hijau', 'check-circle'],
        'Ditindaklanjuti'       => ['hijau', 'check-circle'],
        'Sesuai'                => ['hijau', 'check-circle'],
        'Jadwal Terkunci'       => ['hijau', 'lock'],
        'Ditolak'               => ['merah', 'x-circle'],
        'Tidak Sesuai'          => ['merah', 'x-circle'],
        'Bermasalah'            => ['merah', 'alert'],
        'Perlu Ditinjau'        => ['merah', 'alert'],
        'Dibatalkan'            => ['abu', 'minus'],
    ];
    [$warna, $ik] = $peta[$status] ?? ['abu', 'info'];
    $teks = $label ?? ($peta[$status][2] ?? $status);
    return '<span class="badge badge--' . $warna . '">' . ikon($ik) . e($teks) . '</span>';
}
