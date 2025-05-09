<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'karyawan';

    protected $fillable = [
        'pengguna_id',
        'nip',
        'nama_lengkap',
        'jabatan',
        'departemen',
        'no_telepon',
        'alamat',
        'tanggal_bergabung',
        'status_karyawan', // tetap, kontrak, magang
    ];

    protected $casts = [
        'tanggal_bergabung' => 'datetime',
    ];

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'karyawan_id');
    }
}