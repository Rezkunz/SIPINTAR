<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alasan Teller saat menolak draft transaksi siswa praktik.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('tblDRAFT', 'alasan_penolakan')) {
            Schema::table('tblDRAFT', function (Blueprint $table) {
                $table->string('alasan_penolakan', 500)->nullable()->after('status_verifikasi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tblDRAFT', 'alasan_penolakan')) {
            Schema::table('tblDRAFT', function (Blueprint $table) {
                $table->dropColumn('alasan_penolakan');
            });
        }
    }
};
