<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tblRENCANA_POTONGAN', function (Blueprint $table) {
            $table->string('bukti_transfer', 255)->nullable()->after('catatan_sekolah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblRENCANA_POTONGAN', function (Blueprint $table) {
            $table->dropColumn('bukti_transfer');
        });
    }
};

