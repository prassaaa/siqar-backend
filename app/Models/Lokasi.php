<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    use HasFactory;

    protected $table = 'lokasi';

    protected $fillable = [
        'nama_lokasi',
        'alamat',
        'latitude',
        'longitude',
        'radius', // dalam meter
        'toleransi_keterlambatan',
        'status', // aktif atau nonaktif
        'keterangan',
    ];

    public function qrcodes()
    {
        return $this->hasMany(QRCode::class, 'lokasi_id');
    }
}
