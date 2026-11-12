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
        $indexes = collect(Schema::getIndexes('tblMUTASI_KAS'));
        $hasRegularIndex = $indexes->contains(fn (array $idx) => !$idx['unique'] && $idx['columns'] === ['angsuran_id']);
        $uniqueIndex = $indexes->first(fn (array $idx) => $idx['unique'] && $idx['columns'] === ['angsuran_id']);

        if (!$hasRegularIndex) {
            Schema::table('tblMUTASI_KAS', function (Blueprint $table) {
                $table->index('angsuran_id');
            });
        }

        if ($uniqueIndex) {
            Schema::table('tblMUTASI_KAS', function (Blueprint $table) use ($uniqueIndex) {
                $table->dropUnique($uniqueIndex['name']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('tblMUTASI_KAS'));
        $hasUniqueIndex = $indexes->contains(fn (array $idx) => $idx['unique'] && $idx['columns'] === ['angsuran_id']);

        if (!$hasUniqueIndex && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tblMUTASI_KAS DROP INDEX tblmutasi_kas_angsuran_id_index, ADD UNIQUE tblmutasi_kas_angsuran_id_unique (angsuran_id)');
        }
    }
};

