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
        Schema::table('tblRENCANA_POTONGAN', function (Blueprint $table) {
            $table->date('tgl_potongan')->nullable()->after('total_potongan');
            $table->decimal('total_realisasi', 15, 2)->default(0)->after('tgl_potongan');
            $table->string('metode_penyerahan', 20)->nullable()->after('total_realisasi');
            $table->text('catatan_sekolah')->nullable()->after('metode_penyerahan');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tblRENCANA_POTONGAN MODIFY COLUMN status_potongan ENUM('draft', 'dikirim', 'menunggu_verifikasi', 'selesai') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('tblDETAIL_POTONGAN', function (Blueprint $table) {
            $table->decimal('nominal_realisasi', 15, 2)->nullable()->after('nominal_rencana');
            $table->string('keterangan', 255)->nullable()->after('hasil_potongan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblDETAIL_POTONGAN', function (Blueprint $table) {
            $table->dropColumn(['nominal_realisasi', 'keterangan']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tblRENCANA_POTONGAN MODIFY COLUMN status_potongan ENUM('draft', 'dikirim', 'selesai') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('tblRENCANA_POTONGAN', function (Blueprint $table) {
            $table->dropColumn(['tgl_potongan', 'total_realisasi', 'metode_penyerahan', 'catatan_sekolah']);
        });
    }
};

