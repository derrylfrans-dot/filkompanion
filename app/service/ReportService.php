<?php
// Pembuat laporan
class ReportService
{
    private string $formatGenerator = 'PDF|XLSX';

    // Buat PDF/XLSX
    public function generateDokumenLaporan(array $rekapitulasi, string $formatLaporan): ?array
    {
        $namaDasar = 'Rekap_FILKOMpanion_' . $rekapitulasi['periode']['awal'] . '_' . $rekapitulasi['periode']['akhir'];
        if ($formatLaporan === 'PDF') {
            return ['nama' => $namaDasar . '.pdf', 'mime' => 'application/pdf', 'isi' => $this->buatPdf($rekapitulasi)];
        }
        if ($formatLaporan === 'XLSX') {
            return [
                'nama' => $namaDasar . '.xlsx',
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'isi'  => $this->buatXlsx($rekapitulasi),
            ];
        }
        return null;
    }

    private function buatPdf(array $r): string
    {
        $biru = [0.043, 0.325, 0.580];
        $abu = [0.36, 0.42, 0.51];
        $pdf = new PdfSederhana();
        $pdf->tambahHalaman();
        $pdf->kotak(0, 0, 595.28, 70, $biru);
        $pdf->teks(40, 32, 'FILKOMpanion', 18, true, [1, 1, 1]);
        $pdf->teks(40, 52, 'Laporan Rekapitulasi Layanan Pendampingan - Fakultas Ilmu Komputer UB', 10, false, [1, 1, 1]);

        $pdf->teks(40, 100, 'Periode laporan: ' . $r['periode']['label'], 12, true);
        $pdf->teks(40, 116, 'Dibuat ' . tglIndo($r['dibuat'], 'lengkap') . ' oleh ' . $r['dibuatOleh'], 9, false, $abu);

        $kpi = [
            ['Jumlah tiket', $r['jumlahTiket']['total'] . ' tiket'],
            ['Tiket selesai', $r['jumlahTiket']['selesai'] . ' tiket'],
            ['Jam terverifikasi', angka($r['jamTerverifikasi']) . ' jam'],
            ['Laporan kendala', $r['laporanKendala'] . ' laporan'],
        ];
        foreach ($kpi as $i => [$label, $nilai]) {
            $x = 40 + $i * 130;
            $pdf->kotak($x, 132, 120, 52, [0.96, 0.97, 0.99], [0.84, 0.87, 0.92]);
            $pdf->teks($x + 10, 150, $label, 9, false, $abu);
            $pdf->teks($x + 10, 172, $nilai, 14, true);
        }

        $y = 214;
        $pdf->teks(40, $y, 'Perkembangan tiket per minggu', 11, true);
        $y += 18;
        $this->barisTabel($pdf, $y, ['Minggu', 'Rentang tanggal', 'Jumlah tiket'], [40, 160, 400], true);
        foreach ($r['jumlahTiket']['perMinggu'] as $m) {
            $y += 18;
            $this->barisTabel($pdf, $y, [$m['label'], $m['rentang'], (string) $m['jumlah']], [40, 160, 400]);
        }

        $y += 36;
        $pdf->teks(40, $y, 'Performa relawan', 11, true);
        $y += 18;
        $kolom = [40, 250, 350, 460];
        $this->barisTabel($pdf, $y, ['Relawan', 'Tiket selesai', 'Jam terverifikasi', 'Laporan kendala'], $kolom, true);
        foreach ($r['dataPerforma'] as $p) {
            $y += 18;
            if ($y > 790) {
                $pdf->tambahHalaman();
                $y = 60;
            }
            $this->barisTabel($pdf, $y, [$p['nama'] . ' (' . $p['idRelawan'] . ')', (string) $p['tiketSelesai'],
                angka($p['jamTerverifikasi']) . ' jam', (string) $p['laporanKendala']], $kolom);
        }
        if (count($r['dataPerforma']) === 0) {
            $y += 18;
            $pdf->teks(40, $y, 'Belum ada data performa relawan pada periode ini.', 9, false, $abu);
        }
        $pdf->teks(40, 815, 'Hanya jam berstatus Terverifikasi yang disahkan untuk proses konversi SKM.', 8, false, $abu);
        return $pdf->hasil();
    }

    private function barisTabel(PdfSederhana $pdf, float $y, array $isi, array $x, bool $judul = false): void
    {
        if ($judul) {
            $pdf->kotak(36, $y - 12, 523, 18, [0.863, 0.933, 0.988]);
        }
        foreach ($isi as $i => $teks) {
            $pdf->teks($x[$i], $y, $teks, 9, $judul);
        }
        $pdf->garis(36, $y + 6, 559, $y + 6);
    }

    private function buatXlsx(array $r): string
    {
        $x = new XlsxSederhana();
        $x->tambahSheet('Ringkasan', [
            ['Indikator', 'Nilai'],
            ['Periode laporan', $r['periode']['label']],
            ['Jumlah tiket', (int) $r['jumlahTiket']['total']],
            ['Tiket selesai', (int) $r['jumlahTiket']['selesai']],
            ['Jam terverifikasi', (float) $r['jamTerverifikasi']],
            ['Laporan kendala', (int) $r['laporanKendala']],
            ['Dibuat', tglIndo($r['dibuat'], 'lengkap') . ' oleh ' . $r['dibuatOleh']],
        ]);
        $mingguan = [['Minggu', 'Rentang tanggal', 'Jumlah tiket']];
        foreach ($r['jumlahTiket']['perMinggu'] as $m) {
            $mingguan[] = [$m['label'], $m['rentang'], (int) $m['jumlah']];
        }
        $x->tambahSheet('Tiket per Minggu', $mingguan);
        $performa = [['ID Relawan', 'Nama relawan', 'Tiket selesai', 'Jam terverifikasi', 'Laporan kendala']];
        foreach ($r['dataPerforma'] as $p) {
            $performa[] = [$p['idRelawan'], $p['nama'], (int) $p['tiketSelesai'], (float) $p['jamTerverifikasi'], (int) $p['laporanKendala']];
        }
        $x->tambahSheet('Performa Relawan', $performa);
        return $x->hasil();
    }
}
