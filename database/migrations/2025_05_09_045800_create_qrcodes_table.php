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
        Schema::create('qrcode', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->nullable();
            $table->string('deskripsi');
            $table->date('tanggal');
            $table->time('waktu_mulai');
            $table->time('waktu_berakhir');
            $table->foreignId('lokasi_id')->constrained('lokasi');
            $table->string('status')->default('aktif')->comment('aktif atau nonaktif');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('pengguna');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qrcode');
    }
};
