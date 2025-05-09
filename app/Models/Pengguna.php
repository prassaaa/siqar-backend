<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

class Pengguna extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'pengguna';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'email',
        'password',
        'peran', // admin atau karyawan
        'status', // aktif atau nonaktif
        'foto_profil',
        'terakhir_login',
        'otp',
        'otp_expiry',
        'reset_token',
        'reset_expiry',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'terakhir_login' => 'datetime',
        'otp_expiry' => 'datetime',
        'reset_expiry' => 'datetime',
    ];

    public function karyawan()
    {
        return $this->hasOne(Karyawan::class, 'pengguna_id');
    }

    /**
     * Check if user can access Filament
     *
     * @return bool
     */
    public function canAccessFilament(): bool
    {
        return $this->peran === 'admin' && $this->status === 'aktif';
    }
    
    /**
     * Check if user can access a specific panel
     *
     * @param Panel $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->peran === 'admin' && $this->status === 'aktif';
    }

    /**
     * Get the name for Filament
     *
     * @return string
     */
    public function getFilamentName(): string
    {
        return $this->nama ?? 'Admin';
    }
}