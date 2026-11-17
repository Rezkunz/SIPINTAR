<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keperluan / tujuan pinjaman (UC-08): diisi saat pengajuan dan dipakai sebagai
 * bahan pertimbangan pada penilaian kelayakan. Nullable agar data lama tetap valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblPINJAMAN', function (Blueprint $table) {
            if (! Schema::hasColumn('tblPINJAMAN', 'keperluan')) {
                $table->string('keperluan', 255)->nullable()->after('metode_bayar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tblPINJAMAN', function (Blueprint $table) {
            if (Schema::hasColumn('tblPINJAMAN', 'keperluan')) {
                $table->dropColumn('keperluan');
            }
        });
    }
};
