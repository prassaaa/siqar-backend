<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class QRCode extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'qrcode';

    protected $fillable = [
        'kode',
        'deskripsi',
        'tanggal',
        'waktu_mulai',
        'waktu_berakhir',
        'lokasi_id',
        'status', // aktif atau nonaktif
        'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_mulai' => 'datetime',
        'waktu_berakhir' => 'datetime',
    ];

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'qrcode_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function admin()
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh');
    }
}