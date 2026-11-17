<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-16 Menyerahkan data tunjangan: Bendahara Sekolah mencatat gaji, tunjangan, dan
 * potongan lain (mis. pajak) tiap pegawai per bulan. Dipakai Bendahara Bank Mini saat
 * menilai kelayakan pinjaman dan saat menyusun rencana potongan tunjangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblDATA_TUNJANGAN', function (Blueprint $table) {
            $table->increments('data_tunjangan_id');
            $table->unsignedInteger('nasabah_id');
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('tunjangan', 15, 2)->default(0);
            $table->decimal('potongan_lain', 15, 2)->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->unique(['nasabah_id', 'tahun', 'bulan']);
            $table->foreign('nasabah_id')->references('nasabah_id')->on('tblNASABAH')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblDATA_TUNJANGAN');
    }
};
