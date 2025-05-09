<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;

class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        // Buat admin default
        Pengguna::create([
            'nama' => 'Admin SIQAR',
            'email' => 'admin@siqar.com',
            'password' => Hash::make('admin123'),
            'peran' => 'admin',
            'status' => 'aktif',
        ]);
    }
}