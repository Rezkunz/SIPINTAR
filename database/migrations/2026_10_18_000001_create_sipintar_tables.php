<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for the 19 SIPINTAR ERD tables.
     */
    public function up(): void
    {
        // 1. tblROLE
        Schema::create('tblROLE', function (Blueprint $table) {
            $table->increments('role_id');
            $table->string('nama_role', 50)->unique();
        });

        // 2. tblNASABAH
        Schema::create('tblNASABAH', function (Blueprint $table) {
            $table->increments('nasabah_id');
            $table->string('no_hp', 15)->unique();
            $table->string('nama_nasabah', 100);
            $table->enum('jenis_nasabah', ['pegawai', 'non_pegawai']);
            $table->string('kelas_unit', 50)->nullable();
            $table->enum('status_nasabah', ['aktif', 'nonaktif'])->default('aktif');
        });

        // 3. tblJENIS_TRANSAKSI
        Schema::create('tblJENIS_TRANSAKSI', function (Blueprint $table) {
            $table->increments('jenis_transaksi_id');
            $table->string('nama_jenis', 20)->unique();
        });

        // 4. tblKAS
        Schema::create('tblKAS', function (Blueprint $table) {
            $table->increments('kas_id');
            $table->string('nama_kas', 50);
            $table->enum('jenis_kas', ['kas', 'bank']);
            $table->decimal('saldo', 15, 2)->default(0);
        });

        // 5. tblUSER
        Schema::create('tblUSER', function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('nasabah_id')->nullable()->unique();
            $table->string('no_hp', 15)->unique();
            $table->string('no_induk', 30)->nullable()->unique();
            $table->string('email', 100)->nullable()->unique();
            $table->string('password', 255);
            $table->string('nama_user', 100);
            $table->enum('status_akun', ['aktif', 'nonaktif'])->default('aktif');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('role_id')->references('role_id')->on('tblROLE')->onDelete('cascade');
            $table->foreign('nasabah_id')->references('nasabah_id')->on('tblNASABAH')->onDelete('set null');
        });

        // 6. tblREKENING
        Schema::create('tblREKENING', function (Blueprint $table) {
            $table->increments('rekening_id');
            $table->string('no_rek', 20)->unique();
            $table->unsignedInteger('nasabah_id')->unique();
            $table->date('tgl_buka');
            $table->decimal('saldo', 15, 2)->default(0);
            $table->enum('status_rekening', ['pending', 'aktif', 'blokir', 'tutup'])->default('pending');

            $table->foreign('nasabah_id')->references('nasabah_id')->on('tblNASABAH')->onDelete('cascade');
        });

        // 7. tblDRAFT
        Schema::create('tblDRAFT', function (Blueprint $table) {
            $table->increments('draft_id');
            $table->unsignedInteger('rekening_id');
            $table->unsignedInteger('jenis_transaksi_id');
            $table->unsignedInteger('siswa_id');
            $table->unsignedInteger('teller_id')->nullable();
            $table->decimal('nominal', 15, 2);
            $table->dateTime('tgl_input');
            $table->enum('status_verifikasi', ['pending', 'disetujui', 'ditolak'])->default('pending');

            $table->foreign('rekening_id')->references('rekening_id')->on('tblREKENING')->onDelete('cascade');
            $table->foreign('jenis_transaksi_id')->references('jenis_transaksi_id')->on('tblJENIS_TRANSAKSI')->onDelete('cascade');
            $table->foreign('siswa_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
            $table->foreign('teller_id')->references('user_id')->on('tblUSER')->onDelete('set null');
        });

        // 8. tblPINJAMAN
        Schema::create('tblPINJAMAN', function (Blueprint $table) {
            $table->increments('pinjaman_id');
            $table->unsignedInteger('nasabah_id');
            $table->unsignedInteger('user_id');
            $table->decimal('nominal_pinjaman', 15, 2);
            $table->smallInteger('tenor');
            $table->decimal('bunga_persen', 5, 2)->default(2.00);
            $table->date('tgl_pengajuan');
            $table->enum('metode_bayar', ['potong_gaji', 'tunai']);
            $table->enum('status_pinjaman', ['diajukan', 'layak', 'ditolak', 'cair', 'lunas'])->default('diajukan');

            $table->foreign('nasabah_id')->references('nasabah_id')->on('tblNASABAH')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
        });

        // 9. tblPERIODE
        Schema::create('tblPERIODE', function (Blueprint $table) {
            $table->increments('periode_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->smallInteger('tahun');
            $table->smallInteger('bulan');
            $table->enum('status_periode', ['buka', 'tutup'])->default('buka');
            $table->dateTime('tgl_tutup')->nullable();

            $table->unique(['tahun', 'bulan']);
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('set null');
        });

        // 10. tblTRANSAKSI
        Schema::create('tblTRANSAKSI', function (Blueprint $table) {
            $table->increments('transaksi_id');
            $table->unsignedInteger('rekening_id');
            $table->unsignedInteger('jenis_transaksi_id');
            $table->unsignedInteger('periode_id');
            $table->unsignedInteger('draft_id')->nullable()->unique();
            $table->unsignedInteger('user_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->decimal('saldo_akhir', 15, 2);
            $table->dateTime('tgl_transaksi');

            $table->foreign('rekening_id')->references('rekening_id')->on('tblREKENING')->onDelete('cascade');
            $table->foreign('jenis_transaksi_id')->references('jenis_transaksi_id')->on('tblJENIS_TRANSAKSI')->onDelete('cascade');
            $table->foreign('periode_id')->references('periode_id')->on('tblPERIODE')->onDelete('cascade');
            $table->foreign('draft_id')->references('draft_id')->on('tblDRAFT')->onDelete('set null');
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
        });

        // 11. tblKELAYAKAN
        Schema::create('tblKELAYAKAN', function (Blueprint $table) {
            $table->increments('kelayakan_id');
            $table->unsignedInteger('pinjaman_id')->unique();
            $table->unsignedInteger('user_id');
            $table->decimal('penghasilan', 15, 2)->nullable();
            $table->enum('hasil_kelayakan', ['layak', 'tidak_layak']);
            $table->string('catatan', 255)->nullable();
            $table->date('tgl_nilai');

            $table->foreign('pinjaman_id')->references('pinjaman_id')->on('tblPINJAMAN')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
        });

        // 12. tblPENCAIRAN
        Schema::create('tblPENCAIRAN', function (Blueprint $table) {
            $table->increments('pencairan_id');
            $table->unsignedInteger('pinjaman_id')->unique();
            $table->unsignedInteger('user_id');
            $table->date('tgl_cair');
            $table->decimal('nominal_cair', 15, 2);

            $table->foreign('pinjaman_id')->references('pinjaman_id')->on('tblPINJAMAN')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
        });

        // 13. tblJADWAL_ANGSURAN
        Schema::create('tblJADWAL_ANGSURAN', function (Blueprint $table) {
            $table->increments('jadwal_angsuran_id');
            $table->unsignedInteger('pinjaman_id');
            $table->smallInteger('angsuran_ke');
            $table->date('jatuh_tempo');
            $table->decimal('pokok', 15, 2);
            $table->decimal('bunga', 15, 2);
            $table->enum('status_bayar', ['belum', 'lunas'])->default('belum');

            $table->unique(['pinjaman_id', 'angsuran_ke']);
            $table->foreign('pinjaman_id')->references('pinjaman_id')->on('tblPINJAMAN')->onDelete('cascade');
        });

        // 14. tblRENCANA_POTONGAN
        Schema::create('tblRENCANA_POTONGAN', function (Blueprint $table) {
            $table->increments('rencana_potongan_id');
            $table->unsignedInteger('periode_id');
            $table->unsignedInteger('user_id');
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->enum('status_potongan', ['draft', 'dikirim', 'selesai'])->default('draft');

            $table->foreign('periode_id')->references('periode_id')->on('tblPERIODE')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
        });

        // 15. tblSALDO_KAS
        Schema::create('tblSALDO_KAS', function (Blueprint $table) {
            $table->increments('saldo_kas_id');
            $table->unsignedInteger('periode_id');
            $table->unsignedInteger('kas_id');
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('saldo_akhir', 15, 2)->default(0);

            $table->unique(['periode_id', 'kas_id']);
            $table->foreign('periode_id')->references('periode_id')->on('tblPERIODE')->onDelete('cascade');
            $table->foreign('kas_id')->references('kas_id')->on('tblKAS')->onDelete('cascade');
        });

        // 16. tblBUKTI
        Schema::create('tblBUKTI', function (Blueprint $table) {
            $table->increments('bukti_id');
            $table->unsignedInteger('transaksi_id');
            $table->unsignedInteger('user_id');
            $table->string('no_bukti', 30)->unique();
            $table->dateTime('tgl_cetak');

            $table->foreign('transaksi_id')->references('transaksi_id')->on('tblTRANSAKSI')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
        });

        // 17. tblDETAIL_POTONGAN
        Schema::create('tblDETAIL_POTONGAN', function (Blueprint $table) {
            $table->increments('detail_potongan_id');
            $table->unsignedInteger('rencana_potongan_id');
            $table->unsignedInteger('jadwal_angsuran_id');
            $table->decimal('nominal_rencana', 15, 2);
            $table->enum('hasil_potongan', ['berhasil', 'gagal'])->nullable();

            $table->unique(['rencana_potongan_id', 'jadwal_angsuran_id']);
            $table->foreign('rencana_potongan_id')->references('rencana_potongan_id')->on('tblRENCANA_POTONGAN')->onDelete('cascade');
            $table->foreign('jadwal_angsuran_id')->references('jadwal_angsuran_id')->on('tblJADWAL_ANGSURAN')->onDelete('cascade');
        });

        // 18. tblANGSURAN
        Schema::create('tblANGSURAN', function (Blueprint $table) {
            $table->increments('angsuran_id');
            $table->unsignedInteger('jadwal_angsuran_id')->unique();
            $table->unsignedInteger('detail_potongan_id')->nullable()->unique();
            $table->unsignedInteger('user_id');
            $table->date('tgl_bayar');
            $table->enum('jenis_bayar', ['angsuran', 'pelunasan'])->default('angsuran');
            $table->decimal('bayar_pokok', 15, 2);
            $table->decimal('bayar_bunga', 15, 2)->default(0);

            $table->foreign('jadwal_angsuran_id')->references('jadwal_angsuran_id')->on('tblJADWAL_ANGSURAN')->onDelete('cascade');
            $table->foreign('detail_potongan_id')->references('detail_potongan_id')->on('tblDETAIL_POTONGAN')->onDelete('set null');
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
        });

        // 19. tblMUTASI_KAS
        Schema::create('tblMUTASI_KAS', function (Blueprint $table) {
            $table->increments('mutasi_kas_id');
            $table->unsignedInteger('kas_id');
            $table->unsignedInteger('periode_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('transaksi_id')->nullable()->unique();
            $table->unsignedInteger('angsuran_id')->nullable()->unique();
            $table->unsignedInteger('pencairan_id')->nullable()->unique();
            $table->date('tgl_mutasi');
            $table->enum('arah', ['masuk', 'keluar']);
            $table->decimal('nominal', 15, 2);
            $table->string('uraian', 255)->nullable();

            $table->foreign('kas_id')->references('kas_id')->on('tblKAS')->onDelete('cascade');
            $table->foreign('periode_id')->references('periode_id')->on('tblPERIODE')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('tblUSER')->onDelete('cascade');
            $table->foreign('transaksi_id')->references('transaksi_id')->on('tblTRANSAKSI')->onDelete('set null');
            $table->foreign('angsuran_id')->references('angsuran_id')->on('tblANGSURAN')->onDelete('set null');
            $table->foreign('pencairan_id')->references('pencairan_id')->on('tblPENCAIRAN')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblMUTASI_KAS');
        Schema::dropIfExists('tblANGSURAN');
        Schema::dropIfExists('tblDETAIL_POTONGAN');
        Schema::dropIfExists('tblBUKTI');
        Schema::dropIfExists('tblSALDO_KAS');
        Schema::dropIfExists('tblRENCANA_POTONGAN');
        Schema::dropIfExists('tblJADWAL_ANGSURAN');
        Schema::dropIfExists('tblPENCAIRAN');
        Schema::dropIfExists('tblKELAYAKAN');
        Schema::dropIfExists('tblTRANSAKSI');
        Schema::dropIfExists('tblPERIODE');
        Schema::dropIfExists('tblPINJAMAN');
        Schema::dropIfExists('tblDRAFT');
        Schema::dropIfExists('tblREKENING');
        Schema::dropIfExists('tblUSER');
        Schema::dropIfExists('tblKAS');
        Schema::dropIfExists('tblJENIS_TRANSAKSI');
        Schema::dropIfExists('tblNASABAH');
        Schema::dropIfExists('tblROLE');
    }
};
