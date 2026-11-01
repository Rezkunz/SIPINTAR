<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AuditLogService
{
    /**
     * Catat aksi transaksi ke dalam tblAUDIT_LOG.
     */
    public static function log(
        string $aksi,
        string $entitas,
        ?int $entitasId = null,
        ?array $dataLama = null,
        ?array $dataBaru = null
    ): void {
        try {
            DB::table('tblAUDIT_LOG')->insert([
                'user_id'    => auth()->id(),
                'aksi'       => $aksi,
                'entitas'    => $entitas,
                'entitas_id' => $entitasId,
                'data_lama'  => $dataLama ? json_encode($dataLama) : null,
                'data_baru'  => $dataBaru ? json_encode($dataBaru) : null,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Fail safe agar audit log tidak menggagalkan alur transaksi bila tabel belum siap
            report($e);
        }
    }
}

