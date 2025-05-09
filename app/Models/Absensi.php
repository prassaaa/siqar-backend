<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'absensi';

    protected $fillable = [
        'karyawan_id',
        'qrcode_id',
        'tanggal',
        'waktu_masuk',
        'waktu_keluar',
        'status', // hadir, terlambat, izin, sakit, alpha
        'lokasi_masuk',
        'lokasi_keluar',
        'latitude_masuk',
        'longitude_masuk',
        'latitude_keluar',
        'longitude_keluar',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_masuk' => 'datetime',
        'waktu_keluar' => 'datetime',
        'lokasi_masuk' => 'array',
        'lokasi_keluar' => 'array',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function qrcode()
    {
        return $this->belongsTo(QRCode::class, 'qrcode_id');
    }
}