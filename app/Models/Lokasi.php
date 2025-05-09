<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Lokasi extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'lokasi';

    protected $fillable = [
        'nama_lokasi',
        'alamat',
        'latitude',
        'longitude',
        'radius', // dalam meter
        'status', // aktif atau nonaktif
        'keterangan',
    ];

    public function qrcodes()
    {
        return $this->hasMany(QRCode::class, 'lokasi_id');
    }
}