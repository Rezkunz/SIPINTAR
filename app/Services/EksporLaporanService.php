<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mengubah "dokumen laporan" menjadi file PDF atau Excel (.xlsx).
 *
 * Struktur dokumen:
 * [
 *   'judul'       => 'REKAP HARIAN TABUNGAN SISWA',
 *   'subjudul'    => 'Bulan Maret 2026',
 *   'nama_file'   => 'REKAP_HARIAN_SISWA_MARET_2026',      // tanpa ekstensi
 *   'orientasi'   => 'portrait' | 'landscape',
 *   'ukuran_font' => 9,                                     // pt, opsional (PDF)
 *   'tanda_tangan'=> true,                                  // kolom TTD Manager & Bendahara
 *   'bagian'      => [[
 *       'judul'      => 'Rincian Transaksi',                // opsional
 *       'grup_kolom' => [['label' => 'Tab. Siswa', 'span' => 2], ...], // opsional, header baris atas
 *       'kolom'      => [['label' => 'No', 'align' => 'center'], ['label' => 'Nominal', 'angka' => true], ...],
 *       'baris'      => [self::baris([...]), self::baris([...], 'subtotal'), ...],
 *       'kaki'       => [self::baris([...], 'total')],         // opsional
 *       'catatan'    => 'Keterangan di bawah tabel',          // opsional
 *   ]],
 * ]
 */
class EksporLaporanService
{
    public const NAMA_LEMBAGA = 'BANK MINI WIYATA CIPTA NIAGA';
    public const NAMA_SEKOLAH = 'SMK NEGERI 1 LEMAHABANG';
    public const ALAMAT       = 'Jl. KH. Wahid Hasyim No. 76 Telp. (0231) 635308, Lemahabang, Kabupaten Cirebon';
    public const TEKS_KOSONG  = 'Tidak ada data pada periode ini.';

    private const WARNA_UTAMA  = '0D5C46';
    private const WARNA_MUDA   = 'ECFDF5';
    private const WARNA_AKSEN  = 'FFFBEB';
    private const FORMAT_ANGKA = '#,##0;-#,##0;0';

    /**
     * Satu baris tabel. $gaya: null | 'grup' (judul kelompok) | 'subtotal' | 'total'.
     */
    public static function baris(array $sel, ?string $gaya = null): array
    {
        return ['sel' => array_values($sel), 'gaya' => $gaya];
    }

    public static function pdf(array $dokumen): Response
    {
        $dokumen = self::lengkapi($dokumen);

        return Pdf::loadView('manager.laporan.ekspor_pdf', ['dok' => $dokumen])
            // Tanam hanya huruf yang dipakai agar ukuran PDF kecil
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4', $dokumen['orientasi'])
            ->download($dokumen['nama_file'] . '.pdf');
    }

    public static function xlsx(array $dokumen): StreamedResponse
    {
        $dokumen     = self::lengkapi($dokumen);
        $spreadsheet = self::bangunSpreadsheet($dokumen);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $dokumen['nama_file'] . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Angka untuk tampilan PDF: pemisah ribuan titik, negatif dengan tanda minus.
     */
    public static function formatAngka($nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '';
        }

        return is_numeric($nilai) ? number_format((float) $nilai, 0, ',', '.') : (string) $nilai;
    }

    private static function lengkapi(array $dokumen): array
    {
        return array_merge([
            'subjudul'     => null,
            'orientasi'    => 'portrait',
            'ukuran_font'  => 9,
            'tanda_tangan' => true,
            'dicetak'      => now(),
        ], $dokumen);
    }

    private static function bangunSpreadsheet(array $dok): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('SIPINTAR')->setTitle($dok['judul']);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $dok['judul']), 0, 31));

        $jumlahKolom = max(array_map(fn ($b) => count($b['kolom']), $dok['bagian']));
        $kolomAkhir  = Coordinate::stringFromColumnIndex($jumlahKolom);

        // Kop laporan
        $baris = 1;
        foreach ([self::NAMA_LEMBAGA, self::NAMA_SEKOLAH, $dok['judul'], $dok['subjudul']] as $i => $teks) {
            if ($teks === null) {
                continue;
            }
            $sheet->setCellValue("A{$baris}", $teks);
            $sheet->mergeCells("A{$baris}:{$kolomAkhir}{$baris}");
            $sheet->getStyle("A{$baris}")->getFont()->setBold($i !== 3)->setSize($i === 2 ? 13 : 11);
            $sheet->getStyle("A{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $baris++;
        }
        $baris++;

        foreach ($dok['bagian'] as $bagian) {
            $baris = self::tulisBagian($sheet, $bagian, $baris);
            $baris += 2;
        }

        if ($dok['tanda_tangan']) {
            $kolomKanan = Coordinate::stringFromColumnIndex(max(2, $jumlahKolom - 1));
            $sheet->setCellValue("{$kolomKanan}{$baris}", 'Lemahabang, ' . $dok['dicetak']->translatedFormat('d F Y'));
            $baris++;
            $sheet->setCellValue("A{$baris}", 'Manager');
            $sheet->setCellValue("{$kolomKanan}{$baris}", 'Bendahara');
            $baris += 4;
            $sheet->setCellValue("A{$baris}", '(................................)');
            $sheet->setCellValue("{$kolomKanan}{$baris}", '(................................)');
        }

        for ($k = 1; $k <= $jumlahKolom; $k++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($k))->setAutoSize(true);
        }
        $sheet->getPageSetup()
            ->setOrientation($dok['orientasi'] === 'landscape' ? 'landscape' : 'portrait')
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        return $spreadsheet;
    }

    private static function tulisBagian($sheet, array $bagian, int $baris): int
    {
        $kolom      = $bagian['kolom'];
        $kolomAkhir = Coordinate::stringFromColumnIndex(count($kolom));

        if (!empty($bagian['judul'])) {
            $sheet->setCellValue("A{$baris}", $bagian['judul']);
            $sheet->getStyle("A{$baris}")->getFont()->setBold(true)->getColor()->setRGB(self::WARNA_UTAMA);
            $baris++;
        }

        $awalTabel = $baris;

        if (!empty($bagian['grup_kolom'])) {
            $k = 1;
            foreach ($bagian['grup_kolom'] as $grup) {
                $span  = $grup['span'] ?? 1;
                $dari  = Coordinate::stringFromColumnIndex($k);
                $ke    = Coordinate::stringFromColumnIndex($k + $span - 1);
                $sheet->setCellValue("{$dari}{$baris}", $grup['label']);
                if ($span > 1) {
                    $sheet->mergeCells("{$dari}{$baris}:{$ke}{$baris}");
                }
                $k += $span;
            }
            self::gayaHeader($sheet, "A{$baris}:{$kolomAkhir}{$baris}");
            $baris++;
        }

        foreach ($kolom as $i => $k) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $baris, $k['label']);
        }
        self::gayaHeader($sheet, "A{$baris}:{$kolomAkhir}{$baris}");
        $baris++;

        if (empty($bagian['baris'])) {
            $sheet->setCellValue("A{$baris}", self::TEKS_KOSONG);
            $sheet->mergeCells("A{$baris}:{$kolomAkhir}{$baris}");
            $sheet->getStyle("A{$baris}")->getFont()->setItalic(true)->getColor()->setRGB('64748B');
            $sheet->getStyle("A{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $baris++;
        }

        foreach (array_merge($bagian['baris'], $bagian['kaki'] ?? []) as $r) {
            foreach ($r['sel'] as $i => $nilai) {
                $sel = Coordinate::stringFromColumnIndex($i + 1) . $baris;
                if (!empty($kolom[$i]['angka']) && is_numeric($nilai)) {
                    $sheet->setCellValueExplicit($sel, (float) $nilai, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle($sel)->getNumberFormat()->setFormatCode(self::FORMAT_ANGKA);
                } else {
                    $sheet->setCellValueExplicit($sel, (string) ($nilai ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $align = $kolom[$i]['align'] ?? (!empty($kolom[$i]['angka']) ? 'right' : 'left');
                $sheet->getStyle($sel)->getAlignment()->setHorizontal($align);
            }

            $rentang = "A{$baris}:{$kolomAkhir}{$baris}";
            if (in_array($r['gaya'], ['subtotal', 'total', 'grup'], true)) {
                $sheet->getStyle($rentang)->getFont()->setBold(true);
            }
            if ($r['gaya'] === 'grup') {
                $sheet->getStyle($rentang)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WARNA_MUDA);
            }
            if ($r['gaya'] === 'total') {
                $sheet->getStyle($rentang)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WARNA_AKSEN);
                $sheet->getStyle($rentang)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
            }
            $baris++;
        }

        $sheet->getStyle("A{$awalTabel}:{$kolomAkhir}" . ($baris - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        if (!empty($bagian['catatan'])) {
            $sheet->setCellValue("A{$baris}", $bagian['catatan']);
            $sheet->getStyle("A{$baris}")->getFont()->setItalic(true)->setSize(9);
            $baris++;
        }

        return $baris;
    }

    private static function gayaHeader($sheet, string $rentang): void
    {
        $gaya = $sheet->getStyle($rentang);
        $gaya->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $gaya->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WARNA_UTAMA);
        $gaya->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    }
}
