<?php

namespace Tests\Modul_Dashboard\iterasi1;

use Tests\Modul_Dashboard\DashboardDuskTestCase;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * UC-13 | TC-14-07 (Positive) - Ekspor laporan ke PDF/Excel
 * Aktor    : Manajer Bank Mini - manajer@sipintar.test
 * Expected : File terunduh dan isinya sama dengan tampilan di layar.
 */
class TC_14_07Test extends DashboardDuskTestCase
{
    public function test_ekspor_laporan_pdf_dan_excel(): void
    {
        $this->transaksiTabungan(self::SISWA, 275000);
        $this->transaksiTabungan(self::PEGAWAI, 150000);
        $this->buatPinjamanCair(5000000, 10, 'potong_gaji', 2);

        $this->browse(function (Browser $browser) {
            $this->loginSebagai($browser, self::MANAJER)
                ->visit('/laporan/neraca?bulan=' . date('n') . '&tahun=' . date('Y'))
                ->screenshot('TC-14-07');
            $layar = $this->posNeraca($browser);

            $unduh = function (string $selector) use ($browser): array {
                $href = $browser->attribute($selector, 'href');

                return $browser->driver->executeAsyncScript(
                    'const cb = arguments[arguments.length - 1];'
                    . 'fetch(arguments[0]).then(async r => {'
                    . '  const buf = new Uint8Array(await r.arrayBuffer()); let s = "";'
                    . '  for (let i = 0; i < buf.length; i += 8192) s += String.fromCharCode(...buf.subarray(i, i + 8192));'
                    . '  cb([r.status, r.headers.get("content-type"), btoa(s)]);'
                    . '}).catch(e => cb([0, "ERROR " + e, ""]));',
                    [$href]
                );
            };

            // 2. Ekspor PDF
            [$statusPdf, $tipePdf, $pdf] = $unduh('a.lap-unduh-btn.pdf');
            $this->assertSame(200, $statusPdf);
            $this->assertStringContainsString('pdf', (string) $tipePdf);
            $this->assertStringStartsWith('%PDF', base64_decode($pdf));

            // 3-4. Ekspor Excel lalu buka file dan bandingkan dengan layar
            [$statusXls, , $xlsx] = $unduh('a.lap-unduh-btn.excel');
            $this->assertSame(200, $statusXls);
            $file = tempnam(sys_get_temp_dir(), 'neraca') . '.xlsx';
            file_put_contents($file, base64_decode($xlsx));

            $isiExcel = [];
            foreach (IOFactory::load($file)->getActiveSheet()->toArray(null, true, false) as $baris) {
                foreach ($baris as $sel) {
                    if (is_numeric($sel)) {
                        $isiExcel[] = number_format((float) $sel, 0, ',', '.');
                    } elseif ($sel !== null) {
                        $isiExcel[] = preg_replace('/[^0-9.]/', '', (string) $sel);
                    }
                }
            }
            @unlink($file);

            foreach (['Kas Tunai', 'Kas Bank', 'Piutang Pinjaman Pegawai', 'Tabungan Siswa', 'Tabungan Guru / Karyawan'] as $label) {
                $this->assertContains($layar[$label], $isiExcel, "Nilai {$label} di Excel tidak sama dengan layar");
            }
        });
    }
}
