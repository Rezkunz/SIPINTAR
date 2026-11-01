<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pastikan unique index di tblMUTASI_KAS pada angsuran_id diubah menjadi index biasa
        // agar transaksi pembalik (koreksi/reversal) bisa merujuk ke angsuran_id yang sama tanpa duplicate key error.
        try {
            DB::statement('ALTER TABLE tblMUTASI_KAS DROP INDEX tblmutasi_kas_angsuran_id_unique, ADD INDEX tblmutasi_kas_angsuran_id_index (angsuran_id)');
        } catch (\Throwable $e) {
            // Index mungkin sudah diubah secara manual
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE tblMUTASI_KAS DROP INDEX tblmutasi_kas_angsuran_id_index, ADD UNIQUE tblmutasi_kas_angsuran_id_unique (angsuran_id)');
        } catch (\Throwable $e) {
            // Ignore
        }
    }
};

