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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'siswa_praktik',
                'teller',
                'bendahara',
                'bendahara_mini',
                'manager',
                'nasabah',
            ])->default('nasabah')->after('email');

            $table->string('no_induk')->nullable()->after('role');  // NIS / NIP
            $table->string('phone')->nullable()->after('no_induk');
            $table->string('foto')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('foto');
            $table->dropColumn('phone');
            $table->dropColumn('no_induk');
            $table->dropColumn('role');
        });
    }
};
