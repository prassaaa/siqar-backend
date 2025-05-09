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
        Schema::create('karyawan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->constrained('pengguna')->onDelete('cascade');
            $table->string('nip', 20)->unique();
            $table->string('nama_lengkap');
            $table->string('jabatan', 100);
            $table->string('departemen', 100);
            $table->string('no_telepon', 15);
            $table->text('alamat');
            $table->date('tanggal_bergabung');
            $table->string('status_karyawan')->comment('tetap, kontrak, magang');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawan');
    }
};
