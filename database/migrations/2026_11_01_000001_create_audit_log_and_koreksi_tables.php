<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for audit trail, loan correction, and salary reference.
     */
    public function up(): void
    {
        // 1. Tambahkan data referensi penghasilan ke tblNASABAH jika belum ada
        if (Schema::hasTable('tblNASABAH')) {
            Schema::table('tblNASABAH', function (Blueprint $table) {
                if (!Schema::hasColumn('tblNASABAH', 'gaji_pokok')) {
                    $table->decimal('gaji_pokok', 15, 2)->default(0)->after('kelas_unit');
                }
                if (!Schema::hasColumn('tblNASABAH', 'tunjangan')) {
                    $table->decimal('tunjangan', 15, 2)->default(0)->after('gaji_pokok');
                }
            });
        }

        // 2. Buat tabel tblAUDIT_LOG untuk mencatat jejak audit seluruh transaksi tulis
        if (!Schema::hasTable('tblAUDIT_LOG')) {
            Schema::create('tblAUDIT_LOG', function (Blueprint $table) {
                $table->increments('audit_log_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('aksi', 50); // INSERT, UPDATE, DELETE, DISBURSE, PAYMENT, CORRECTION
                $table->string('entitas', 50); // tblPINJAMAN, tblANGSURAN, tblMUTASI_KAS, dll
                $table->unsignedInteger('entitas_id')->nullable();
                $table->json('data_lama')->nullable();
                $table->json('data_baru')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('set null');
                $table->index(['entitas', 'entitas_id']);
                $table->index('created_at');
            });
        }

        // 3. Buat tabel tblKOREKSI_ANGSURAN untuk pembatalan/koreksi transaksi angsuran tanpa hapus historis
        if (!Schema::hasTable('tblKOREKSI_ANGSURAN')) {
            Schema::create('tblKOREKSI_ANGSURAN', function (Blueprint $table) {
                $table->increments('koreksi_id');
                $table->unsignedInteger('angsuran_id');
                $table->unsignedInteger('user_id'); // Bendahara Bank Mini yang mengoreksi
                $table->date('tgl_koreksi');
                $table->text('alasan_koreksi');
                $table->decimal('nominal_pokok', 15, 2);
                $table->decimal('nominal_bunga', 15, 2)->default(0);
                $table->decimal('nominal_total', 15, 2);
                $table->string('status_koreksi', 30)->default('selesai');
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('angsuran_id')->references('angsuran_id')->on('tblANGSURAN')->onDelete('cascade');
                $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
                $table->index('tgl_koreksi');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblKOREKSI_ANGSURAN');
        Schema::dropIfExists('tblAUDIT_LOG');

        if (Schema::hasTable('tblNASABAH')) {
            Schema::table('tblNASABAH', function (Blueprint $table) {
                if (Schema::hasColumn('tblNASABAH', 'tunjangan')) {
                    $table->dropColumn('tunjangan');
                }
                if (Schema::hasColumn('tblNASABAH', 'gaji_pokok')) {
                    $table->dropColumn('gaji_pokok');
                }
            });
        }
    }
};

