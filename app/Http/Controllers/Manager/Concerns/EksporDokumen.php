<?php

namespace App\Http\Controllers\Manager\Concerns;

use App\Services\EksporLaporanService;
use Illuminate\Http\Request;

/**
 * Aksi unduh PDF & Excel untuk controller laporan; controller cukup menyusun dokumennya.
 */
trait EksporDokumen
{
    /**
     * Struktur dokumen laporan, lihat EksporLaporanService.
     */
    abstract protected function dokumenEkspor(Request $request): array;

    public function exportPdf(Request $request)
    {
        return EksporLaporanService::pdf($this->dokumenEkspor($request));
    }

    public function exportXlsx(Request $request)
    {
        return EksporLaporanService::xlsx($this->dokumenEkspor($request));
    }
}
