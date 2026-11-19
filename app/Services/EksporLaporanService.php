<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
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
    public const LOGO         = 'images/sipintar_mark.png';   // logo Bank Mini yang sama dengan sidebar/login; relatif terhadap public/

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

    /**
     * Logo Bank Mini sebagai data URI untuk kop PDF (dompdf tidak perlu mengakses file/URL luar).
     */
    public static function logoDataUri(): ?string
    {
        static $uri = false;

        if ($uri === false) {
            $path = public_path(self::LOGO);
            $uri  = is_file($path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($path)) : null;
        }

        return $uri;
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

    /**
     * Susun lembar Excel. Lebar kolom dihitung sendiri dari isi tabel (bukan auto-size), supaya
     * catatan, tanda tangan, atau tabel lain tidak membuat kolom melebar.
     *
     * Kisi kolom mengikuti tabel dengan kolom terbanyak; tabel lain yang strukturnya berbeda
     * memakai gabungan beberapa kolom kisi (merge) sesuai lebar yang dibutuhkan.
     */
    private static function bangunSpreadsheet(array $dok): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('SIPINTAR')->setTitle($dok['judul']);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $dok['judul']), 0, 31));
        $sheet->setShowGridlines(false);

        // Kisi kolom: tabel dengan kolom terbanyak (pertama bila sama banyak)
        $indeksKisi = 0;
        foreach ($dok['bagian'] as $i => $b) {
            if (count($b['kolom']) > count($dok['bagian'][$indeksKisi]['kolom'])) {
                $indeksKisi = $i;
            }
        }
        $lebar = self::lebarDibutuhkan($dok['bagian'][$indeksKisi]);

        // Petakan tabel lain ke kisi; kolom yang tetap kekurangan lebar → lebarkan kolom kisi terakhirnya
        $rentangSemua = [];
        foreach ($dok['bagian'] as $i => $b) {
            if ($i === $indeksKisi) {
                $rentangSemua[$i] = array_map(fn ($k) => [$k, $k], array_keys($lebar));
                continue;
            }
            $butuh = array_values(self::lebarDibutuhkan($b));
            $rentangSemua[$i] = self::petakanKeKisi($butuh, $lebar);
            foreach ($rentangSemua[$i] as $j => [$a, $z]) {
                $ada = array_sum(array_slice($lebar, $a, $z - $a + 1));
                if ($ada < $butuh[$j]) {
                    $lebar[$z] += $butuh[$j] - $ada;
                }
            }
        }

        // Blok tanda tangan kiri (Manager) & kanan (Bendahara) masing-masing minimal 30 karakter:
        // pakai beberapa kolom dari tepi; bila kolomnya terlalu sedikit (mis. Neraca 2 kolom), lebarkan kolom tepi.
        $jumlahKisi = count($lebar);
        $ttdKanan = $jumlahKisi - 1;
        $w = $lebar[$ttdKanan];
        while ($w < 30 && $ttdKanan > 1) {
            $w += $lebar[--$ttdKanan];
        }
        if ($w < 30) {
            $lebar[$jumlahKisi - 1] += 30 - $w;
        }
        $ttdKiri = 0;
        $w = $lebar[0];
        while ($w < 30 && $ttdKiri + 1 < $ttdKanan) {
            $w += $lebar[++$ttdKiri];
        }
        if ($w < 30) {
            $lebar[$ttdKiri] += 30 - $w;
        }

        // Kop selebar minimal ~78 karakter agar logo tidak menimpa teks kop yang di tengah
        $kurang = 78 - array_sum($lebar);
        if ($kurang > 0) {
            // Tambahkan ke kolom teks terlebar (mis. "Pos" pada Neraca)
            $target = 0;
            foreach ($dok['bagian'][$indeksKisi]['kolom'] as $i => $k) {
                if (empty($k['angka']) && $lebar[$i] >= $lebar[$target]) {
                    $target = $i;
                }
            }
            $lebar[$target] += $kurang;
        }

        $jumlahKolom = count($lebar);
        $kolomAkhir  = Coordinate::stringFromColumnIndex($jumlahKolom);
        foreach ($lebar as $i => $w) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($w);
        }

        // Kop laporan (sama dengan kop PDF): nama lembaga, sekolah, alamat + garis ganda hijau,
        // lalu judul & subjudul laporan. Format: [teks, ukuran, tebal, warna, tinggi baris (pt)]
        $kop = [
            [self::NAMA_LEMBAGA, 13, true, self::WARNA_UTAMA, 21],
            [self::NAMA_SEKOLAH, 11, true, '0F172A', 17],
            [self::ALAMAT, 8, false, '64748B', 15],
            [null, 0, false, null, 6],                       // jarak di bawah garis kop
            [$dok['judul'], 12, true, '0F172A', 19],
            [$dok['subjudul'], 10, false, '334155', 16],
        ];
        $baris = 1;
        foreach ($kop as $i => [$teks, $ukuran, $tebal, $warna, $tinggi]) {
            if ($teks === null && $i !== 3) {
                continue;
            }
            $sheet->getRowDimension($baris)->setRowHeight($tinggi);
            if ($teks !== null) {
                $sheet->setCellValue("A{$baris}", $teks);
                $sheet->mergeCells("A{$baris}:{$kolomAkhir}{$baris}");
                $gaya = $sheet->getStyle("A{$baris}");
                $gaya->getFont()->setBold($tebal)->setSize($ukuran)->getColor()->setRGB($warna);
                $gaya->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }
            $baris++;
        }
        $sheet->getStyle("A3:{$kolomAkhir}3")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setRGB(self::WARNA_UTAMA);
        $baris++;

        // Logo Bank Mini di kiri kop, sejajar tiga baris kop (21 + 17 + 15 pt = 53 pt ≈ 71 px)
        if (is_file(public_path(self::LOGO))) {
            (new Drawing())
                ->setName('Logo Bank Mini')
                ->setPath(public_path(self::LOGO))
                ->setHeight(56)
                ->setCoordinates('A1')
                ->setOffsetX(6)
                ->setOffsetY(7)
                ->setWorksheet($sheet);
        }

        foreach ($dok['bagian'] as $i => $bagian) {
            $baris = self::tulisBagian($sheet, $bagian, $baris, $rentangSemua[$i], $lebar);
            $baris += 1;
        }

        if ($dok['tanda_tangan']) {
            $baris++;
            $kiri  = ['A', Coordinate::stringFromColumnIndex($ttdKiri + 1)];
            $kanan = [Coordinate::stringFromColumnIndex($ttdKanan + 1), $kolomAkhir];
            $tulisTtd = function (array $blok, int $r, string $teks) use ($sheet) {
                $sheet->setCellValue("{$blok[0]}{$r}", $teks);
                if ($blok[0] !== $blok[1]) {
                    $sheet->mergeCells("{$blok[0]}{$r}:{$blok[1]}{$r}");
                }
                $sheet->getStyle("{$blok[0]}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            };
            $tulisTtd($kanan, $baris, 'Lemahabang, ' . $dok['dicetak']->translatedFormat('d F Y'));
            $baris++;
            $tulisTtd($kiri, $baris, 'Manager');
            $tulisTtd($kanan, $baris, 'Bendahara');
            $baris += 4;
            $tulisTtd($kiri, $baris, '(................................)');
            $tulisTtd($kanan, $baris, '(................................)');
        }

        $sheet->getPageSetup()
            ->setOrientation($dok['orientasi'] === 'landscape' ? 'landscape' : 'portrait')
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true);
        $sheet->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.5);
        $sheet->setSelectedCell('A1');

        return $spreadsheet;
    }

    /**
     * Lebar (satuan karakter Excel) yang dibutuhkan tiap kolom sebuah tabel:
     * judul kolom boleh dilipat (cukup kata terpanjangnya), isi teks dibatasi 42 lalu dilipat.
     */
    private static function lebarDibutuhkan(array $bagian): array
    {
        $lebar = [];
        foreach ($bagian['kolom'] as $i => $k) {
            $angka  = !empty($k['angka']);
            $judul  = (string) $k['label'];
            // Judul kolom boleh dilipat: cukup selebar kata terpanjang, atau seluruh judul bila pendek
            $kata   = max(array_map([self::class, 'lebarTeks'], preg_split('/\s+/', $judul)));
            $wJudul = max($kata, min(self::lebarTeks($judul), 15)) * 1.1;   // huruf tebal sedikit lebih lebar

            $wIsi = 0;
            foreach (array_merge($bagian['baris'], $bagian['kaki'] ?? []) as $r) {
                $tebal = in_array($r['gaya'], ['subtotal', 'total', 'grup'], true) ? 1.1 : 1;
                $wIsi  = max($wIsi, self::lebarTeks(self::teksSel($r['sel'][$i] ?? null, $angka)) * $tebal);
            }

            $lebar[$i] = max(max($wJudul, $wIsi) + 2, $angka ? 11 : 5);
        }

        // Batas lebar: kolom teks terlebar (Uraian, Pos) maks 62 agar sebisa mungkin satu baris; kolom lain maks 45 lalu dilipat
        $terlebar = null;
        foreach ($bagian['kolom'] as $i => $k) {
            if (empty($k['angka']) && ($terlebar === null || $lebar[$i] > $lebar[$terlebar])) {
                $terlebar = $i;
            }
        }
        foreach ($lebar as $i => $w) {
            $lebar[$i] = round(min($w, $i === $terlebar ? 62 : 45), 1);
        }

        return $lebar;
    }

    /**
     * Perkiraan lebar teks dalam satuan lebar kolom Excel (1 = lebar angka "0" pada Calibri 10).
     */
    private static function lebarTeks(string $teks): float
    {
        $w = 0.0;
        foreach (mb_str_split($teks) as $c) {
            $w += match (true) {
                $c === ' '                      => 0.45,
                ctype_digit($c)                 => 1.0,
                str_contains('ilI.,:;|!\'', $c) => 0.45,
                str_contains('fjrt()[]-/', $c)  => 0.6,
                str_contains('mwMW@%', $c)      => 1.45,
                ctype_upper($c)                 => 1.15,
                default                         => 0.9,
            };
        }

        // Kalibrasi terhadap tampilan Excel sebenarnya (huruf Calibri ±20% lebih lebar dari bobot dasar)
        return $w * 1.2;
    }

    /**
     * Petakan kolom tabel ke kisi: tiap kolom memakai satu atau lebih kolom kisi berurutan
     * sampai lebarnya cukup, dengan menyisakan minimal satu kolom kisi untuk kolom berikutnya.
     *
     * @return array<int, array{0:int,1:int}> [indeks kisi awal, indeks kisi akhir] per kolom
     */
    private static function petakanKeKisi(array $butuh, array $kisi): array
    {
        // Pemrograman dinamis: bagi kolom kisi secara berurutan ke kolom tabel sehingga kekurangan lebar
        // (bobot besar) dan kelebihan lebar (bobot kecil) seminimal mungkin. Kolom kisi sisa di kanan boleh tidak dipakai.
        $butuh = array_values($butuh);
        $n = count($kisi);
        $m = count($butuh);
        $awalan = [0];
        foreach ($kisi as $i => $w) {
            $awalan[$i + 1] = $awalan[$i] + $w;
        }

        $biaya = array_fill(0, $m + 1, array_fill(0, $n + 1, INF));
        $asal  = [];
        $biaya[0][0] = 0;
        for ($j = 1; $j <= $m; $j++) {
            for ($p = $j; $p <= $n - ($m - $j); $p++) {
                for ($q = $j - 1; $q < $p; $q++) {
                    if ($biaya[$j - 1][$q] === INF) {
                        continue;
                    }
                    $lebar = $awalan[$p] - $awalan[$q];
                    $nilai = $biaya[$j - 1][$q] + max(0, $butuh[$j - 1] - $lebar) * 10 + max(0, $lebar - $butuh[$j - 1]);
                    if ($nilai < $biaya[$j][$p]) {
                        $biaya[$j][$p] = $nilai;
                        $asal[$j][$p]  = $q;
                    }
                }
            }
        }

        $p = $m;
        for ($k = $m; $k <= $n; $k++) {
            if ($biaya[$m][$k] < $biaya[$m][$p]) {
                $p = $k;
            }
        }

        $rentang = [];
        for ($j = $m; $j >= 1; $j--) {
            $q = $asal[$j][$p];
            $rentang[$j - 1] = [$q, $p - 1];
            $p = $q;
        }
        ksort($rentang);

        return $rentang;
    }

    private static function teksSel($nilai, bool $angka): string
    {
        if ($nilai === null) {
            return '';
        }

        return $angka && is_numeric($nilai) ? number_format((float) $nilai, 0, ',', '.') : (string) $nilai;
    }

    /**
     * Tulis satu tabel. $rentang = [indeks kisi awal, akhir] tiap kolom; $lebar = lebar kisi.
     */
    private static function tulisBagian($sheet, array $bagian, int $baris, array $rentang, array $lebar): int
    {
        $kolom  = $bagian['kolom'];
        $huruf  = fn (int $i) => Coordinate::stringFromColumnIndex($i + 1);
        $akhir  = $huruf(end($rentang)[1]);
        $sel    = function (int $j, int $r) use ($rentang, $huruf, $sheet): string {
            [$a, $b] = $rentang[$j];
            if ($a !== $b) {
                $sheet->mergeCells($huruf($a) . $r . ':' . $huruf($b) . $r);
            }

            return $huruf($a) . $r;
        };
        $lebarSel = fn (int $j) => array_sum(array_slice($lebar, $rentang[$j][0], $rentang[$j][1] - $rentang[$j][0] + 1));
        // Perkiraan jumlah baris teks terlipat berdasarkan lebar huruf (lihat lebarTeks)
        $tinggi   = fn (string $teks, float $w, float $pt = 13.5) => max(1, (int) ceil(self::lebarTeks($teks) / 1.08 / max(1, $w - 1.2))) * $pt;

        if (!empty($bagian['judul'])) {
            $sheet->setCellValue("A{$baris}", $bagian['judul']);
            $sheet->mergeCells("A{$baris}:{$akhir}{$baris}");
            $sheet->getStyle("A{$baris}")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(self::WARNA_UTAMA);
            $sheet->getRowDimension($baris)->setRowHeight(18);
            $baris++;
        }

        $awalTabel = $baris;

        if (!empty($bagian['grup_kolom'])) {
            $j = 0;
            foreach ($bagian['grup_kolom'] as $grup) {
                $span = $grup['span'] ?? 1;
                $a = $huruf($rentang[$j][0]);
                $b = $huruf($rentang[$j + $span - 1][1]);
                $sheet->setCellValue("{$a}{$baris}", $grup['label']);
                if ($a !== $b) {
                    $sheet->mergeCells("{$a}{$baris}:{$b}{$baris}");
                }
                $j += $span;
            }
            self::gayaHeader($sheet, "A{$baris}:{$akhir}{$baris}");
            $sheet->getRowDimension($baris)->setRowHeight(18);
            $baris++;
        }

        $tinggiJudul = 18;
        foreach ($kolom as $j => $k) {
            $sheet->setCellValue($sel($j, $baris), $k['label']);
            $tinggiJudul = max($tinggiJudul, $tinggi((string) $k['label'], $lebarSel($j) / 1.15, 13.5) + 4);
        }
        self::gayaHeader($sheet, "A{$baris}:{$akhir}{$baris}");
        $sheet->getRowDimension($baris)->setRowHeight($tinggiJudul);
        $baris++;

        if (empty($bagian['baris'])) {
            $sheet->setCellValue("A{$baris}", self::TEKS_KOSONG);
            $sheet->mergeCells("A{$baris}:{$akhir}{$baris}");
            $sheet->getStyle("A{$baris}")->getFont()->setItalic(true)->getColor()->setRGB('64748B');
            $sheet->getStyle("A{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $baris++;
        }

        foreach (array_merge($bagian['baris'], $bagian['kaki'] ?? []) as $r) {
            $tinggiBaris = 15;
            foreach ($kolom as $j => $k) {
                $nilai  = $r['sel'][$j] ?? null;
                $alamat = $sel($j, $baris);
                $angka  = !empty($k['angka']);

                if ($angka && is_numeric($nilai)) {
                    $sheet->setCellValueExplicit($alamat, (float) $nilai, DataType::TYPE_NUMERIC);
                    $sheet->getStyle($alamat)->getNumberFormat()->setFormatCode(self::FORMAT_ANGKA);
                } elseif (is_int($nilai) || is_float($nilai)) {
                    // Angka di kolom non-nominal (No, jumlah transaksi): tetap angka, bukan teks
                    $sheet->setCellValueExplicit($alamat, $nilai, DataType::TYPE_NUMERIC);
                } elseif (is_string($nilai) && preg_match('/^(-?[\d.]*\d(?:,\d+)?)\s*%$/', trim($nilai), $persen)) {
                    // "92,41%" -> angka persen sungguhan (0,9241 berformat 0,00%)
                    $sheet->setCellValueExplicit($alamat, (float) str_replace(['.', ','], ['', '.'], $persen[1]) / 100, DataType::TYPE_NUMERIC);
                    $sheet->getStyle($alamat)->getNumberFormat()->setFormatCode('0.00%');
                } else {
                    $sheet->setCellValueExplicit($alamat, (string) ($nilai ?? ''), DataType::TYPE_STRING);
                    // Teks berbentuk angka (mis. angsuran "1/20", no. rekening) tidak diberi tanda peringatan
                    $sheet->getCell($alamat)->getIgnoredErrors()->setNumberStoredAsText(true)->setTwoDigitTextYear(true);
                    $tinggiBaris = max($tinggiBaris, $tinggi((string) $nilai, $lebarSel($j)));
                }

                $align = $k['align'] ?? ($angka ? 'right' : 'left');
                $sheet->getStyle($alamat)->getAlignment()->setHorizontal($align);
            }

            $rentangBaris = "A{$baris}:{$akhir}{$baris}";
            $sheet->getStyle($rentangBaris)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            $sheet->getRowDimension($baris)->setRowHeight($tinggiBaris);
            if (in_array($r['gaya'], ['subtotal', 'total', 'grup'], true)) {
                $sheet->getStyle($rentangBaris)->getFont()->setBold(true);
            }
            if ($r['gaya'] === 'grup') {
                $sheet->getStyle($rentangBaris)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WARNA_MUDA);
            }
            if ($r['gaya'] === 'total') {
                $sheet->getStyle($rentangBaris)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WARNA_AKSEN);
                $sheet->getStyle($rentangBaris)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
            }
            $baris++;
        }

        $sheet->getStyle("A{$awalTabel}:{$akhir}" . ($baris - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        if (!empty($bagian['catatan'])) {
            $sheet->setCellValue("A{$baris}", $bagian['catatan']);
            $sheet->mergeCells("A{$baris}:{$akhir}{$baris}");
            $gaya = $sheet->getStyle("A{$baris}");
            $gaya->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('64748B');
            $gaya->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($baris)->setRowHeight($tinggi($bagian['catatan'], array_sum(array_slice($lebar, 0, end($rentang)[1] + 1)) * 1.1, 12.5) + 2);
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
