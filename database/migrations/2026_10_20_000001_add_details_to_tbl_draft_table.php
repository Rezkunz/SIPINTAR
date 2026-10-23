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
        Schema::table('tblDRAFT', function (Blueprint $table) {
            $table->string('keterangan', 255)->nullable()->after('nominal');
            $table->string('alasan_penolakan', 255)->nullable()->after('status_verifikasi');
            $table->dateTime('tgl_verifikasi')->nullable()->after('alasan_penolakan');

            $table->index('status_verifikasi', 'idx_tbldraft_status_verifikasi');
            $table->index('siswa_id', 'idx_tbldraft_siswa_id');
            $table->index('rekening_id', 'idx_tbldraft_rekening_id');
            $table->index(['siswa_id', 'status_verifikasi'], 'idx_tbldraft_siswa_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblDRAFT', function (Blueprint $table) {
            $table->dropIndex('idx_tbldraft_siswa_status');
            $table->dropIndex('idx_tbldraft_rekening_id');
            $table->dropIndex('idx_tbldraft_siswa_id');
            $table->dropIndex('idx_tbldraft_status_verifikasi');
            $table->dropColumn(['keterangan', 'alasan_penolakan', 'tgl_verifikasi']);
        });
    }
};
