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
        // Ubah unique index jadwal_angsuran_id di tblANGSURAN menjadi index biasa.
        // Hal ini penting dalam sistem audit trail akuntansi perbankan,
        // di mana transaksi angsuran yang pernah dibatalkan (koreksi/reversal) tetap disimpan baris lamanya,
        // sehingga saat nasabah membayar ulang angsuran tersebut tidak terkena error duplicate entry.
        if (DB::getDriverName() === 'mysql') {
            try {
                // Pastikan index biasa ada sebelum drop unique index agar foreign key constraint tetap valid
                $indexes = collect(DB::select('SHOW INDEX FROM tblANGSURAN'))->pluck('Key_name')->all();
                if (!in_array('tblangsuran_jadwal_angsuran_id_index', $indexes)) {
                    DB::statement('ALTER TABLE tblANGSURAN ADD INDEX tblangsuran_jadwal_angsuran_id_index (jadwal_angsuran_id)');
                }
                if (in_array('tblangsuran_jadwal_angsuran_id_unique', $indexes)) {
                    DB::statement('ALTER TABLE tblANGSURAN DROP INDEX tblangsuran_jadwal_angsuran_id_unique');
                }
            } catch (\Throwable $e) {
                // Ignore jika sudah diubah
            }
        } else {
            // PostgreSQL / SQLite: periksa index lebih dulu agar transaksi DDL PostgreSQL tidak gagal (25P02)
            $indexes = collect(Schema::getIndexes('tblANGSURAN'));
            $hasRegularIndex = $indexes->contains(fn (array $idx) => !$idx['unique'] && $idx['columns'] === ['jadwal_angsuran_id']);
            $uniqueIndex = $indexes->first(fn (array $idx) => $idx['unique'] && $idx['columns'] === ['jadwal_angsuran_id']);

            if (!$hasRegularIndex) {
                Schema::table('tblANGSURAN', function (Blueprint $table) {
                    $table->index('jadwal_angsuran_id');
                });
            }

            if ($uniqueIndex) {
                Schema::table('tblANGSURAN', function (Blueprint $table) use ($uniqueIndex) {
                    $table->dropUnique($uniqueIndex['name']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE tblANGSURAN ADD UNIQUE tblangsuran_jadwal_angsuran_id_unique (jadwal_angsuran_id)');
                DB::statement('ALTER TABLE tblANGSURAN DROP INDEX tblangsuran_jadwal_angsuran_id_index');
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
};

