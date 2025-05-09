<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\QRCode;
use App\Models\Pengguna;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\Storage;

class AdminDashboardController extends Controller
{
    /**
     * Get dashboard data
     */
    public function dashboardData(Request $request)
    {
        // Verifikasi admin
        $pengguna = $request->user();
        if ($pengguna->peran !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Get filter parameters
        $tahun = $request->tahun ?? Carbon::now()->year;
        $bulan = $request->bulan ?? Carbon::now()->month;
        
        // Tanggal awal dan akhir bulan
        $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        
        // Total karyawan
        $totalKaryawan = Karyawan::count();
        
        // Data absensi bulanan
        $absensi = Absensi::whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->get();
        
        // Hitung statistik
        $totalHariKerja = $startDate->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday(); // Monday to Friday
        }, $endDate) + 1;
        
        $totalHadir = $absensi->whereIn('status', ['hadir', 'terlambat'])->count();
        $totalTerlambat = $absensi->where('status', 'terlambat')->count();
        $totalIzin = $absensi->where('status', 'izin')->count();
        $totalSakit = $absensi->where('status', 'sakit')->count();
        $totalAlpha = $totalHariKerja * $totalKaryawan - $totalHadir - $totalIzin - $totalSakit;
        $totalAlpha = $totalAlpha < 0 ? 0 : $totalAlpha;
        
        // Hitung persentase
        $persentaseKehadiran = ($totalKaryawan > 0 && $totalHariKerja > 0) ? 
            round(($totalHadir / ($totalKaryawan * $totalHariKerja)) * 100, 2) : 0;
        $persentaseKeterlambatan = $totalHadir > 0 ? 
            round(($totalTerlambat / $totalHadir) * 100, 2) : 0;
        
        // Data per hari untuk grafik
        $absensiPerHari = [];
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayName = $currentDate->locale('id')->dayName;
            
            $hariData = [
                'tanggal' => $dateStr,
                'hari' => $dayName,
                'hadir' => $absensi->where('tanggal', $dateStr)->whereIn('status', ['hadir', 'terlambat'])->count(),
                'terlambat' => $absensi->where('tanggal', $dateStr)->where('status', 'terlambat')->count(),
                'izin' => $absensi->where('tanggal', $dateStr)->where('status', 'izin')->count(),
                'sakit' => $absensi->where('tanggal', $dateStr)->where('status', 'sakit')->count(),
            ];
            
            $absensiPerHari[] = $hariData;
            $currentDate->addDay();
        }
        
        // Data karyawan terlambat terbanyak (top 5)
        $karyawanTerlambat = [];
        $absensiGroupByKaryawan = $absensi->where('status', 'terlambat')->groupBy('karyawan_id');
        
        foreach ($absensiGroupByKaryawan as $karyawanId => $items) {
            $karyawan = Karyawan::find($karyawanId);
            if ($karyawan) {
                $karyawanTerlambat[] = [
                    'karyawan_id' => $karyawan->id,
                    'nama' => $karyawan->nama_lengkap,
                    'jabatan' => $karyawan->jabatan,
                    'departemen' => $karyawan->departemen,
                    'jumlah_terlambat' => $items->count(),
                ];
            }
        }
        
        // Sort by jumlah_terlambat descending and take top 5
        usort($karyawanTerlambat, function ($a, $b) {
            return $b['jumlah_terlambat'] - $a['jumlah_terlambat'];
        });
        
        $karyawanTerlambat = array_slice($karyawanTerlambat, 0, 5);
        
        // QR Code hari ini
        $today = Carbon::today();
        $qrCodeHariIni = QRCode::where('tanggal', $today)
            ->where('status', 'aktif')
            ->first();
            
        $qrCodeData = null;
        if ($qrCodeHariIni) {
            $qrCodeData = [
                'id' => $qrCodeHariIni->id,
                'deskripsi' => $qrCodeHariIni->deskripsi,
                'waktu_mulai' => $qrCodeHariIni->waktu_mulai,
                'waktu_berakhir' => $qrCodeHariIni->waktu_berakhir,
                'image_url' => url('storage/qrcodes/qrcode-' . $qrCodeHariIni->id . '.png'),
            ];
        }

        return response()->json([
            'status' => true,
            'data' => [
                'tanggal' => Carbon::now()->format('Y-m-d'),
                'total_karyawan' => $totalKaryawan,
                'bulan_ini' => [
                    'periode' => $startDate->format('F Y'),
                    'total_hari_kerja' => $totalHariKerja,
                    'total_hadir' => $totalHadir,
                    'total_terlambat' => $totalTerlambat,
                    'total_izin' => $totalIzin,
                    'total_sakit' => $totalSakit,
                    'total_alpha' => $totalAlpha,
                    'persentase_kehadiran' => $persentaseKehadiran,
                    'persentase_keterlambatan' => $persentaseKeterlambatan,
                ],
                'grafik_absensi' => $absensiPerHari,
                'karyawan_terlambat' => $karyawanTerlambat,
                'qrcode_hari_ini' => $qrCodeData,
            ]
        ]);
    }

    /**
     * Get all employees data
     */
    public function getKaryawanList(Request $request)
    {
        // Verifikasi admin
        $pengguna = $request->user();
        if ($pengguna->peran !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Get filter parameters
        $search = $request->search;
        $departemen = $request->departemen;
        $status = $request->status;

        // Build query
        $query = Karyawan::query();
        
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('nama_lengkap', 'like', "%$search%")
                      ->orWhere('nip', 'like', "%$search%");
            });
        }
        
        if ($departemen) {
            $query->where('departemen', $departemen);
        }
        
        if ($status) {
            $query->where('status_karyawan', $status);
        }
        
        // Get paginated results
        $perPage = $request->per_page ?? 10;
        $karyawan = $query->with('pengguna')->paginate($perPage);
        
        // Transform data
        $result = $karyawan->map(function ($item) {
            return [
                'id' => $item->id,
                'nip' => $item->nip,
                'nama_lengkap' => $item->nama_lengkap,
                'jabatan' => $item->jabatan,
                'departemen' => $item->departemen,
                'status' => $item->status_karyawan,
                'tanggal_bergabung' => $item->tanggal_bergabung->format('Y-m-d'),
                'email' => $item->pengguna ? $item->pengguna->email : null,
                'status_akun' => $item->pengguna ? $item->pengguna->status : null,
                'foto_profil' => $item->pengguna && $item->pengguna->foto_profil ? 
                                 url('storage/' . $item->pengguna->foto_profil) : null,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'karyawan' => $result,
                'pagination' => [
                    'total' => $karyawan->total(),
                    'per_page' => $karyawan->perPage(),
                    'current_page' => $karyawan->currentPage(),
                    'last_page' => $karyawan->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Export absensi data to PDF
     */
    public function exportAbsensi(Request $request)
    {
        // Verifikasi admin
        $pengguna = $request->user();
        if ($pengguna->peran !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Get filter parameters
        $tahun = $request->tahun ?? Carbon::now()->year;
        $bulan = $request->bulan ?? Carbon::now()->month;
        $karyawanId = $request->karyawan_id;
        $jenisLaporan = $request->jenis ?? 'bulanan'; // bulanan, mingguan
        
        // Tanggal awal dan akhir
        if ($jenisLaporan == 'bulanan') {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
            $judulPeriode = $startDate->format('F Y');
        } else {
            $startDate = Carbon::parse($request->tanggal_awal ?? Carbon::now()->startOfWeek());
            $endDate = Carbon::parse($request->tanggal_akhir ?? Carbon::now()->endOfWeek());
            $judulPeriode = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
        }
        
        // Build query
        $query = Absensi::whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        
        if ($karyawanId) {
            $query->where('karyawan_id', $karyawanId);
        }
        
        // Get results
        $absensi = $query->with('karyawan')->orderBy('tanggal', 'asc')->get();
        
        // Group by karyawan if no specific karyawan is selected
        $dataPerKaryawan = [];
        
        if (!$karyawanId) {
            $absensiGroupByKaryawan = $absensi->groupBy('karyawan_id');
            
            foreach ($absensiGroupByKaryawan as $id => $items) {
                $karyawan = Karyawan::find($id);
                if ($karyawan) {
                    $hadir = $items->whereIn('status', ['hadir', 'terlambat'])->count();
                    $terlambat = $items->where('status', 'terlambat')->count();
                    $izin = $items->where('status', 'izin')->count();
                    $sakit = $items->where('status', 'sakit')->count();
                    
                    $totalHariKerja = $startDate->diffInDaysFiltered(function (Carbon $date) {
                        return $date->isWeekday(); // Monday to Friday
                    }, $endDate) + 1;
                    
                    $alpha = $totalHariKerja - $hadir - $izin - $sakit;
                    $alpha = $alpha < 0 ? 0 : $alpha;
                    
                    $dataPerKaryawan[] = [
                        'karyawan' => $karyawan,
                        'hadir' => $hadir,
                        'terlambat' => $terlambat,
                        'izin' => $izin,
                        'sakit' => $sakit,
                        'alpha' => $alpha,
                        'total_hari_kerja' => $totalHariKerja,
                        'persentase_kehadiran' => $totalHariKerja > 0 ? round(($hadir / $totalHariKerja) * 100, 2) : 0,
                    ];
                }
            }
        } else {
            // Get detail data for a specific karyawan
            $karyawan = Karyawan::find($karyawanId);
            $absensiData = [];
            
            // Generate calendar data
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');
                $dayName = $currentDate->locale('id')->dayName;
                
                $absensiHari = $absensi->where('tanggal', $dateStr)->first();
                
                $absensiData[] = [
                    'tanggal' => $dateStr,
                    'hari' => $dayName,
                    'status' => $absensiHari ? $absensiHari->status : ($currentDate->isWeekend() ? 'weekend' : 'alpha'),
                    'waktu_masuk' => $absensiHari && $absensiHari->waktu_masuk ? 
                                    Carbon::parse($absensiHari->waktu_masuk)->format('H:i:s') : '-',
                    'waktu_keluar' => $absensiHari && $absensiHari->waktu_keluar ? 
                                     Carbon::parse($absensiHari->waktu_keluar)->format('H:i:s') : '-',
                    'lokasi' => $absensiHari ? $absensiHari->lokasi_masuk : '-',
                    'keterangan' => $absensiHari ? $absensiHari->keterangan : '-',
                ];
                
                $currentDate->addDay();
            }
            
            // Calculate summary
            $hadir = $absensi->whereIn('status', ['hadir', 'terlambat'])->count();
            $terlambat = $absensi->where('status', 'terlambat')->count();
            $izin = $absensi->where('status', 'izin')->count();
            $sakit = $absensi->where('status', 'sakit')->count();
            
            $totalHariKerja = $startDate->diffInDaysFiltered(function (Carbon $date) {
                return $date->isWeekday(); // Monday to Friday
            }, $endDate) + 1;
            
            $alpha = $totalHariKerja - $hadir - $izin - $sakit;
            $alpha = $alpha < 0 ? 0 : $alpha;
            
            $ringkasan = [
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'total_hari_kerja' => $totalHariKerja,
                'persentase_kehadiran' => $totalHariKerja > 0 ? round(($hadir / $totalHariKerja) * 100, 2) : 0,
            ];
            
            $dataPerKaryawan = [
                'karyawan' => $karyawan,
                'absensi' => $absensiData,
                'ringkasan' => $ringkasan,
            ];
        }
        
        // Create PDF
        $data = [
            'title' => 'Laporan Absensi ' . ($jenisLaporan == 'bulanan' ? 'Bulanan' : 'Mingguan'),
            'periode' => $judulPeriode,
            'tanggal_cetak' => Carbon::now()->format('d M Y H:i'),
            'jenis_laporan' => $jenisLaporan,
            'data' => $dataPerKaryawan,
            'karyawan_id' => $karyawanId,
        ];
        
        $pdf = PDF::loadView('pdf.laporan-absensi', $data);
        
        // Save PDF file
        $filename = 'laporan-absensi-' . ($karyawanId ? 'karyawan-' . $karyawanId . '-' : '') . 
                   $startDate->format('Y-m-d') . '-' . $endDate->format('Y-m-d') . '.pdf';
                   
        $path = 'laporan/' . $filename;
        Storage::put('public/' . $path, $pdf->output());
        
        return response()->json([
            'status' => true,
            'message' => 'Laporan absensi berhasil dibuat',
            'data' => [
                'file_name' => $filename,
                'download_url' => url('storage/' . $path),
            ]
        ]);
    }
}