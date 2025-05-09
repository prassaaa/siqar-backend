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
        Schema::table('lokasi', function (Blueprint $table) {
            // Jika kolom jam_masuk belum ada, tambahkan
            if (!Schema::hasColumn('lokasi', 'jam_masuk')) {
                $table->time('jam_masuk')->default('08:00:00')->after('radius');
            }

            // Periksa jika kolom toleransi_keterlambatan sudah ada
            if (!Schema::hasColumn('lokasi', 'toleransi_keterlambatan')) {
                $table->integer('toleransi_keterlambatan')->default(15)->after('radius');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lokasi', function (Blueprint $table) {
            if (Schema::hasColumn('lokasi', 'jam_masuk')) {
                $table->dropColumn('jam_masuk');
            }

            if (Schema::hasColumn('lokasi', 'toleransi_keterlambatan')) {
                $table->dropColumn('toleransi_keterlambatan');
            }
        });
    }
};
