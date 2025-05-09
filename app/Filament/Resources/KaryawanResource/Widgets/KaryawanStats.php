<?php

namespace App\Filament\Resources\KaryawanResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;

class KaryawanStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Hitung total karyawan berdasarkan status
        $totalKaryawan = Karyawan::count();
        $karyawanTetap = Karyawan::where('status_karyawan', 'tetap')->count();
        $karyawanKontrak = Karyawan::where('status_karyawan', 'kontrak')->count();
        $karyawanMagang = Karyawan::where('status_karyawan', 'magang')->count();
        
        // Data department
        $departments = Karyawan::distinct('departemen')->pluck('departemen')->toArray();
        $departmentCount = count($departments);

        return [
            Stat::make('Total Karyawan', $totalKaryawan)
                ->description('Jumlah seluruh karyawan')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Karyawan Tetap', $karyawanTetap)
                ->description(($totalKaryawan > 0) ? round(($karyawanTetap / $totalKaryawan) * 100, 1) . '% dari total karyawan' : '0% dari total karyawan')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Karyawan Kontrak', $karyawanKontrak)
                ->description(($totalKaryawan > 0) ? round(($karyawanKontrak / $totalKaryawan) * 100, 1) . '% dari total karyawan' : '0% dari total karyawan')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('warning'),
        ];
    }
}