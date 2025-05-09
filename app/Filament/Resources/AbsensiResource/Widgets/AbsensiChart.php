<?php

namespace App\Filament\Resources\AbsensiResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Absensi;
use Carbon\Carbon;

class AbsensiChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Absensi Mingguan';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Ambil data 7 hari terakhir
        $dates = collect();
        $hadir = collect();
        $terlambat = collect();
        $izin = collect();
        $sakit = collect();
        $alpha = collect();

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dates->push($date->format('d M'));

            // Hitung jumlah status untuk setiap hari
            $hadir->push(Absensi::where('tanggal', $date->format('Y-m-d'))
                ->where('status', 'hadir')
                ->count());
                
            $terlambat->push(Absensi::where('tanggal', $date->format('Y-m-d'))
                ->where('status', 'terlambat')
                ->count());
                
            $izin->push(Absensi::where('tanggal', $date->format('Y-m-d'))
                ->where('status', 'izin')
                ->count());
                
            $sakit->push(Absensi::where('tanggal', $date->format('Y-m-d'))
                ->where('status', 'sakit')
                ->count());
                
            $alpha->push(Absensi::where('tanggal', $date->format('Y-m-d'))
                ->where('status', 'alpha')
                ->count());
        }

        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $hadir->all(),
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $terlambat->all(),
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                ],
                [
                    'label' => 'Izin',
                    'data' => $izin->all(),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6',
                ],
                [
                    'label' => 'Sakit',
                    'data' => $sakit->all(),
                    'backgroundColor' => '#6366f1',
                    'borderColor' => '#6366f1',
                ],
                [
                    'label' => 'Alpha',
                    'data' => $alpha->all(),
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#ef4444',
                ],
            ],
            'labels' => $dates->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}