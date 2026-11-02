<?php
// Penulis PDF sederhana
class PdfSederhana
{
    private const LEBAR = 595.28;
    private const TINGGI = 841.89;

    // Isi per halaman
    private array $halaman = [];
    private int $aktif = -1;

    public function tambahHalaman(): void
    {
        $this->halaman[] = '';
        $this->aktif = count($this->halaman) - 1;
    }

    public function teks(float $x, float $y, string $isi, float $ukuran = 10, bool $tebal = false, array $warna = [0.06, 0.14, 0.25]): void
    {
        $font = $tebal ? 'F2' : 'F1';
        $this->tulis(sprintf(
            "BT %.3F %.3F %.3F rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n",
            $warna[0], $warna[1], $warna[2], $font, $ukuran, $x, self::TINGGI - $y, $this->escape($isi)
        ));
    }

    public function garis(float $x1, float $y1, float $x2, float $y2, float $tebal = 0.6, array $warna = [0.84, 0.87, 0.92]): void
    {
        $this->tulis(sprintf(
            "%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
            $warna[0], $warna[1], $warna[2], $tebal, $x1, self::TINGGI - $y1, $x2, self::TINGGI - $y2
        ));
    }

    public function kotak(float $x, float $y, float $w, float $h, array $isi, ?array $tepi = null): void
    {
        $op = $tepi ? 'B' : 'f';
        $tepiStr = $tepi ? sprintf('%.3F %.3F %.3F RG 0.8 w ', $tepi[0], $tepi[1], $tepi[2]) : '';
        $this->tulis(sprintf(
            "%.3F %.3F %.3F rg %s%.2F %.2F %.2F %.2F re %s\n",
            $isi[0], $isi[1], $isi[2], $tepiStr, $x, self::TINGGI - $y - $h, $w, $h, $op
        ));
    }

    // Perkiraan lebar teks
    public static function lebarTeks(string $isi, float $ukuran): float
    {
        return strlen($isi) * $ukuran * 0.5;
    }

    public function hasil(): string
    {
        $objek = [];
        $objek[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objek[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objek[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];
        $no = 5;
        foreach ($this->halaman as $isi) {
            $idHal = $no++;
            $idIsi = $no++;
            $kids[] = "$idHal 0 R";
            $objek[$idHal] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::LEBAR, self::TINGGI, $idIsi
            );
            $objek[$idIsi] = '<< /Length ' . strlen($isi) . " >>\nstream\n" . $isi . "endstream";
        }
        $objek[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objek);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offset = [];
        foreach ($objek as $id => $isi) {
            $offset[$id] = strlen($pdf);
            $pdf .= "$id 0 obj\n$isi\nendobj\n";
        }
        $xref = strlen($pdf);
        $jumlah = max(array_keys($objek)) + 1;
        $pdf .= "xref\n0 $jumlah\n0000000000 65535 f \n";
        for ($i = 1; $i < $jumlah; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offset[$i]);
        }
        $pdf .= "trailer\n<< /Size $jumlah /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
        return $pdf;
    }

    private function tulis(string $perintah): void
    {
        if ($this->aktif < 0) {
            $this->tambahHalaman();
        }
        $this->halaman[$this->aktif] .= $perintah;
    }

    private function escape(string $teks): string
    {
        $hasil = function_exists('iconv') ? @iconv('UTF-8', 'CP1252//TRANSLIT', $teks) : false;
        if ($hasil === false) {
            $teks = strtr($teks, ['–' => '-', '—' => '-', '·' => '-', '’' => "'", '“' => '"', '”' => '"']);
            $hasil = preg_replace('/[^\x20-\x7E]/', '?', $teks);
        }
        $teks = $hasil;
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $teks);
    }
}
