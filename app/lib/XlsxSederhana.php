<?php
// Penulis XLSX sederhana
class XlsxSederhana
{
    // Daftar sheet
    private array $sheet = [];

    // Tambah sheet
    public function tambahSheet(string $nama, array $baris): void
    {
        $this->sheet[] = ['nama' => mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $nama), 0, 31), 'baris' => $baris];
    }

    public function hasil(): string
    {
        $berkas = [];
        $n = count($this->sheet);
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $berkas['[Content_Types].xml'] = $ct . '</Types>';
        $berkas['_rels/.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->sheet as $i => $s) {
            $no = $i + 1;
            $wb .= '<sheet name="' . $this->xml($s['nama']) . '" sheetId="' . $no . '" r:id="rId' . $no . '"/>';
            $rels .= '<Relationship Id="rId' . $no . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $no . '.xml"/>';
            $berkas['xl/worksheets/sheet' . $no . '.xml'] = $this->sheetXml($s['baris']);
        }
        $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $berkas['xl/workbook.xml'] = $wb . '</sheets></workbook>';
        $berkas['xl/_rels/workbook.xml.rels'] = $rels . '</Relationships>';
        $berkas['xl/styles.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDCEEFC"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
        return $this->zip($berkas);
    }

    private function sheetXml(array $baris): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<cols><col min="1" max="1" width="30" customWidth="1"/><col min="2" max="8" width="20" customWidth="1"/></cols><sheetData>';
        foreach ($baris as $r => $kolom) {
            $xml .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($kolom) as $c => $nilai) {
                $ref = $this->kolom($c) . ($r + 1);
                $gaya = $r === 0 ? ' s="1"' : '';
                if (is_int($nilai) || is_float($nilai)) {
                    $xml .= '<c r="' . $ref . '"' . $gaya . '><v>' . $nilai . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr"' . $gaya . '><is><t>' . $this->xml((string) $nilai) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        return $xml . '</sheetData></worksheet>';
    }

    private function kolom(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private function xml(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    // Pengemas ZIP
    private function zip(array $berkas): string
    {
        $data = '';
        $pusat = '';
        $jumlah = 0;
        $waktu = ((date('H') << 11) | (date('i') << 5) | (int) (date('s') / 2));
        $tanggal = (((date('Y') - 1980) << 9) | (date('n') << 5) | date('j'));
        foreach ($berkas as $nama => $isi) {
            $crc = crc32($isi);
            $panjang = strlen($isi);
            $offset = strlen($data);
            $data .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $waktu, $tanggal, $crc, $panjang, $panjang, strlen($nama), 0)
                . $nama . $isi;
            $pusat .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $waktu, $tanggal, $crc, $panjang, $panjang,
                strlen($nama), 0, 0, 0, 0, 0, $offset) . $nama;
            $jumlah++;
        }
        return $data . $pusat
            . pack('VvvvvVVv', 0x06054b50, 0, 0, $jumlah, $jumlah, strlen($pusat), strlen($data), 0);
    }
}
