<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tblREKENING MODIFY COLUMN status_rekening ENUM('pending', 'aktif', 'blokir', 'tutup') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tblREKENING MODIFY COLUMN status_rekening ENUM('aktif', 'blokir', 'tutup') NOT NULL DEFAULT 'aktif'");
        }
    }
};
