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
        if (Schema::hasTable('tblPENCAIRAN') && !Schema::hasColumn('tblPENCAIRAN', 'metode_cair')) {
            Schema::table('tblPENCAIRAN', function (Blueprint $table) {
                $table->string('metode_cair', 20)->default('cash')->after('nominal_cair');
                $table->string('catatan_cair', 255)->nullable()->after('metode_cair');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tblPENCAIRAN') && Schema::hasColumn('tblPENCAIRAN', 'metode_cair')) {
            Schema::table('tblPENCAIRAN', function (Blueprint $table) {
                $table->dropColumn(['metode_cair', 'catatan_cair']);
            });
        }
    }
};
