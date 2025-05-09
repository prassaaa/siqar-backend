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
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('peran')->default('karyawan'); // admin atau karyawan
            $table->string('status')->default('nonaktif'); // aktif atau nonaktif
            $table->string('foto_profil')->nullable();
            $table->timestamp('terakhir_login')->nullable();
            $table->string('otp')->nullable();
            $table->timestamp('otp_expiry')->nullable();
            $table->string('reset_token')->nullable();
            $table->timestamp('reset_expiry')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengguna');
    }
};
