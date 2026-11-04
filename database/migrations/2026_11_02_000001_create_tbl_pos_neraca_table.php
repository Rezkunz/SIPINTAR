<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pos neraca yang tidak tercatat otomatis di sistem (deposito, peralatan, modal, dll),
     * diinput manual oleh Manager. Nilai per pos berlaku sejak tgl_berlaku hingga ada entri baru.
     */
    public function up(): void
    {
        Schema::create('tblPOS_NERACA', function (Blueprint $table) {
            $table->increments('pos_neraca_id');
            $table->enum('kelompok', ['aktiva', 'kewajiban', 'ekuitas']);
            $table->string('nama_pos', 100);
            $table->decimal('nilai', 15, 2);
            $table->date('tgl_berlaku');
            $table->string('keterangan', 255)->nullable();
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->index(['kelompok', 'nama_pos', 'tgl_berlaku']);
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblPOS_NERACA');
    }
};
