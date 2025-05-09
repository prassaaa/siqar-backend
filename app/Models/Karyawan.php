<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $table = 'karyawan';

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
        'tanggal_bergabung' => 'date',
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
