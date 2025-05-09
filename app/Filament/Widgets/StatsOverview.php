<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\QRCode;
use App\Models\Lokasi;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        // Hitung total karyawan
        $totalKaryawan = Karyawan::count();
        
        // Data absensi hari ini
        $today = Carbon::today();
        $totalAbsensiHariIni = Absensi::where('tanggal', $today)->count();
        
        // Hitung karyawan yang hadir hari ini (status hadir atau terlambat)
        $karyawanHadirHariIni = Absensi::where('tanggal', $today)
            ->whereIn('status', ['hadir', 'terlambat'])
            ->count();
        
        // Hitung persentase kehadiran
        $persentaseKehadiran = $totalKaryawan > 0 
            ? round(($karyawanHadirHariIni / $totalKaryawan) * 100, 2) 
            : 0;
            
        // Hitung karyawan yang terlambat hari ini
        $karyawanTerlambatHariIni = Absensi::where('tanggal', $today)
            ->where('status', 'terlambat')
            ->count();

        // Hitung lokasi aktif
        $lokasiAktif = Lokasi::where('status', 'aktif')->count();

        // Hitung QR code aktif hari ini
        $qrCodeAktif = QRCode::where('tanggal', $today)
            ->where('status', 'aktif')
            ->count();

        return [
            Stat::make('Total Karyawan', $totalKaryawan)
                ->description('Jumlah seluruh karyawan')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Kehadiran Hari Ini', $karyawanHadirHariIni . ' dari ' . $totalKaryawan)
                ->description('Persentase: ' . $persentaseKehadiran . '%')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->chart([$persentaseKehadiran, $persentaseKehadiran, $persentaseKehadiran, $persentaseKehadiran, $persentaseKehadiran]),

            Stat::make('Keterlambatan Hari Ini', $karyawanTerlambatHariIni)
                ->description('Karyawan yang terlambat hari ini')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Lokasi Aktif', $lokasiAktif)
                ->description('Jumlah lokasi yang aktif')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info'),
                
            Stat::make('QR Code Hari Ini', $qrCodeAktif)
                ->description('QR Code aktif hari ini')
                ->descriptionIcon('heroicon-m-qr-code')
                ->color('primary'),
        ];
    }
}