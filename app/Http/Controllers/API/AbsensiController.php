<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\QRCode;
use App\Models\Lokasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AbsensiController extends Controller
{
    /**
     * Scan QR Code for attendance
     */
    public function scanQR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'tipe' => 'required|in:masuk,keluar',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get authenticated user
        $pengguna = $request->user();
        
        // Check if user is karyawan
        if ($pengguna->peran != 'karyawan') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya karyawan yang dapat melakukan absensi',
            ], 403);
        }
        
        // Get karyawan data
        $karyawan = Karyawan::where('pengguna_id', $pengguna->id)->first();
        
        if (!$karyawan) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan',
            ], 404);
        }

        // Find QR Code
        $qrCode = QRCode::where('kode', $request->qr_code)
            ->where('status', 'aktif')
            ->first();

        if (!$qrCode) {
            return response()->json([
                'status' => false,
                'message' => 'QR Code tidak valid atau sudah tidak aktif',
            ], 404);
        }

        // Check QR Code expiry
        $now = Carbon::now();
        $qrDate = Carbon::parse($qrCode->tanggal);
        $startTime = Carbon::parse($qrCode->waktu_mulai);
        $endTime = Carbon::parse($qrCode->waktu_berakhir);

        // Check date
        if (!$now->isSameDay($qrDate)) {
            return response()->json([
                'status' => false,
                'message' => 'QR Code ini tidak berlaku untuk hari ini',
            ], 400);
        }

        // Check time
        if ($now->lt($startTime) || $now->gt($endTime)) {
            return response()->json([
                'status' => false,
                'message' => 'QR Code hanya berlaku dari ' . $startTime->format('H:i') . ' sampai ' . $endTime->format('H:i'),
            ], 400);
        }

        // Check location
        $lokasi = Lokasi::find($qrCode->lokasi_id);
        
        if (!$lokasi) {
            return response()->json([
                'status' => false,
                'message' => 'Lokasi absensi tidak ditemukan',
            ], 404);
        }

        // Calculate distance between user location and attendance location
        $distance = $this->calculateDistance(
            $request->latitude, 
            $request->longitude, 
            $lokasi->latitude, 
            $lokasi->longitude
        );

        // Check if user is within radius
        if ($distance > $lokasi->radius) {
            return response()->json([
                'status' => false,
                'message' => 'Anda berada di luar radius absensi. Jarak Anda: ' . round($distance) . ' meter, Radius yang diizinkan: ' . $lokasi->radius . ' meter',
            ], 400);
        }

        // Check if already checked in/out today
        $today = Carbon::today();
        $absensi = Absensi::where('karyawan_id', $karyawan->id)
            ->where('tanggal', $today)
            ->first();

        $terlambat = false;
        
        if ($request->tipe == 'masuk') {
            if ($absensi && $absensi->waktu_masuk) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda sudah melakukan absensi masuk hari ini',
                ], 400);
            }
            
            // Check if late
            $jamMasuk = Carbon::parse($qrCode->lokasi->jam_masuk ?? '08:00:00');
            $terlambat = $now->gt($jamMasuk);
            
            if ($absensi) {
                // Update existing record
                $absensi->qrcode_id = $qrCode->id;
                $absensi->waktu_masuk = $now;
                $absensi->lokasi_masuk = $lokasi->nama_lokasi;
                $absensi->latitude_masuk = $request->latitude;
                $absensi->longitude_masuk = $request->longitude;
                $absensi->status = $terlambat ? 'terlambat' : 'hadir';
                $absensi->save();
            } else {
                // Create new record
                $absensi = Absensi::create([
                    'karyawan_id' => $karyawan->id,
                    'qrcode_id' => $qrCode->id,
                    'tanggal' => $today,
                    'waktu_masuk' => $now,
                    'lokasi_masuk' => $lokasi->nama_lokasi,
                    'latitude_masuk' => $request->latitude,
                    'longitude_masuk' => $request->longitude,
                    'status' => $terlambat ? 'terlambat' : 'hadir',
                ]);
            }
            
            $message = $terlambat ? 
                'Absensi masuk berhasil, tetapi Anda terlambat!' : 
                'Absensi masuk berhasil!';
                
        } else { // keluar
            if (!$absensi) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda belum melakukan absensi masuk hari ini',
                ], 400);
            }
            
            if ($absensi->waktu_keluar) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda sudah melakukan absensi keluar hari ini',
                ], 400);
            }
            
            // Update record
            $absensi->waktu_keluar = $now;
            $absensi->lokasi_keluar = $lokasi->nama_lokasi;
            $absensi->latitude_keluar = $request->latitude;
            $absensi->longitude_keluar = $request->longitude;
            $absensi->save();
            
            $message = 'Absensi keluar berhasil!';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'absensi' => [
                    'id' => $absensi->id,
                    'tanggal' => $absensi->tanggal->format('Y-m-d'),
                    'waktu_masuk' => $absensi->waktu_masuk ? $absensi->waktu_masuk->format('H:i:s') : null,
                    'waktu_keluar' => $absensi->waktu_keluar ? $absensi->waktu_keluar->format('H:i:s') : null,
                    'status' => $absensi->status,
                    'lokasi' => $lokasi->nama_lokasi,
                ]
            ]
        ]);
    }

    /**
     * Get attendance history
     */
    public function history(Request $request)
    {
        // Get authenticated user
        $pengguna = $request->user();
        
        // Check if user is karyawan
        if ($pengguna->peran != 'karyawan') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya karyawan yang dapat melihat riwayat absensi',
            ], 403);
        }
        
        // Get karyawan data
        $karyawan = Karyawan::where('pengguna_id', $pengguna->id)->first();
        
        if (!$karyawan) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan',
            ], 404);
        }

        // Get filter parameters
        $tanggalMulai = $request->tanggal_mulai ? Carbon::parse($request->tanggal_mulai) : Carbon::now()->startOfMonth();
        $tanggalAkhir = $request->tanggal_akhir ? Carbon::parse($request->tanggal_akhir) : Carbon::now()->endOfMonth();
        $status = $request->status;

        // Build query
        $query = Absensi::where('karyawan_id', $karyawan->id)
            ->whereBetween('tanggal', [$tanggalMulai->format('Y-m-d'), $tanggalAkhir->format('Y-m-d')]);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        // Get paginated results
        $perPage = $request->per_page ?? 10;
        $absensi = $query->orderBy('tanggal', 'desc')
            ->paginate($perPage);
        
        // Transform data
        $result = $absensi->map(function ($item) {
            return [
                'id' => $item->id,
                'tanggal' => $item->tanggal->format('Y-m-d'),
                'hari' => $item->tanggal->locale('id')->dayName,
                'waktu_masuk' => $item->waktu_masuk ? $item->waktu_masuk->format('H:i:s') : null,
                'waktu_keluar' => $item->waktu_keluar ? $item->waktu_keluar->format('H:i:s') : null,
                'lokasi_masuk' => $item->lokasi_masuk,
                'lokasi_keluar' => $item->lokasi_keluar,
                'status' => $item->status,
                'keterangan' => $item->keterangan,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'absensi' => $result,
                'pagination' => [
                    'total' => $absensi->total(),
                    'per_page' => $absensi->perPage(),
                    'current_page' => $absensi->currentPage(),
                    'last_page' => $absensi->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Get today's attendance status
     */
    public function today(Request $request)
    {
        // Get authenticated user
        $pengguna = $request->user();
        
        // Check if user is karyawan
        if ($pengguna->peran != 'karyawan') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya karyawan yang dapat melihat status absensi',
            ], 403);
        }
        
        // Get karyawan data
        $karyawan = Karyawan::where('pengguna_id', $pengguna->id)->first();
        
        if (!$karyawan) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan',
            ], 404);
        }

        // Get today's attendance
        $today = Carbon::today();
        $absensi = Absensi::where('karyawan_id', $karyawan->id)
            ->where('tanggal', $today)
            ->first();
        
        // Get active QR Code for today
        $qrCode = QRCode::where('tanggal', $today)
            ->where('status', 'aktif')
            ->first();
        
        $lokasi = null;
        if ($qrCode) {
            $lokasi = Lokasi::find($qrCode->lokasi_id);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'tanggal' => $today->format('Y-m-d'),
                'hari' => $today->locale('id')->dayName,
                'absensi' => $absensi ? [
                    'id' => $absensi->id,
                    'waktu_masuk' => $absensi->waktu_masuk ? $absensi->waktu_masuk->format('H:i:s') : null,
                    'waktu_keluar' => $absensi->waktu_keluar ? $absensi->waktu_keluar->format('H:i:s') : null,
                    'status' => $absensi->status,
                    'keterangan' => $absensi->keterangan,
                ] : null,
                'lokasi_absensi' => $lokasi ? [
                    'id' => $lokasi->id,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'alamat' => $lokasi->alamat,
                    'radius' => $lokasi->radius,
                ] : null,
            ]
        ]);
    }

    /**
     * Request leave/absence
     */
    public function requestLeave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date|after_or_equal:today',
            'status' => 'required|in:izin,sakit',
            'keterangan' => 'required|string|max:500',
            'bukti' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get authenticated user
        $pengguna = $request->user();
        
        // Check if user is karyawan
        if ($pengguna->peran != 'karyawan') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya karyawan yang dapat mengajukan izin/sakit',
            ], 403);
        }
        
        // Get karyawan data
        $karyawan = Karyawan::where('pengguna_id', $pengguna->id)->first();
        
        if (!$karyawan) {
            return response()->json([
                'status' => false,
                'message' => 'Data karyawan tidak ditemukan',
            ], 404);
        }

        // Check if already has attendance record for that date
        $tanggal = Carbon::parse($request->tanggal);
        $absensi = Absensi::where('karyawan_id', $karyawan->id)
            ->where('tanggal', $tanggal)
            ->first();
        
        if ($absensi) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah memiliki catatan absensi pada tanggal tersebut',
            ], 400);
        }

        // Upload bukti if provided
        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $file = $request->file('bukti');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $buktiPath = $file->storeAs('bukti-izin', $fileName, 'public');
        }

        // Create absence record
        $absensi = Absensi::create([
            'karyawan_id' => $karyawan->id,
            'tanggal' => $tanggal,
            'status' => $request->status,
            'keterangan' => $request->keterangan,
            'bukti' => $buktiPath,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan ' . $request->status . ' berhasil disimpan',
            'data' => [
                'absensi' => [
                    'id' => $absensi->id,
                    'tanggal' => $absensi->tanggal->format('Y-m-d'),
                    'status' => $absensi->status,
                    'keterangan' => $absensi->keterangan,
                ]
            ]
        ]);
    }

    /**
     * Get monthly report
     */
    public function monthlyReport(Request $request)
    {
        // Get authenticated user
        $pengguna = $request->user();
        
        // Get filter parameters
        $tahun = $request->tahun ?? Carbon::now()->year;
        $bulan = $request->bulan ?? Carbon::now()->month;
        
        // Get karyawan data
        $karyawanId = null;
        if ($pengguna->peran == 'karyawan') {
            $karyawan = Karyawan::where('pengguna_id', $pengguna->id)->first();
            
            if (!$karyawan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data karyawan tidak ditemukan',
                ], 404);
            }
            
            $karyawanId = $karyawan->id;
        } else if ($request->has('karyawan_id')) {
            $karyawanId = $request->karyawan_id;
        }

        // Build query
        $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        
        $query = Absensi::whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        
        if ($karyawanId) {
            $query->where('karyawan_id', $karyawanId);
        }
        
        // Get results
        $absensi = $query->get();
        
        // Calculate statistics
        $totalHariKerja = $startDate->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday(); // Monday to Friday
        }, $endDate) + 1;
        
        $totalHadir = $absensi->whereIn('status', ['hadir', 'terlambat'])->count();
        $totalTerlambat = $absensi->where('status', 'terlambat')->count();
        $totalIzin = $absensi->where('status', 'izin')->count();
        $totalSakit = $absensi->where('status', 'sakit')->count();
        $totalAlpha = $totalHariKerja - $totalHadir - $totalIzin - $totalSakit;
        $totalAlpha = $totalAlpha < 0 ? 0 : $totalAlpha;
        
        // Calculate percentages
        $persentaseKehadiran = $totalHariKerja > 0 ? round(($totalHadir / $totalHariKerja) * 100, 2) : 0;
        $persentaseKeterlambatan = $totalHadir > 0 ? round(($totalTerlambat / $totalHadir) * 100, 2) : 0;
        
        // Response data
        $reportData = [
            'periode' => $startDate->format('F Y'),
            'tahun' => $tahun,
            'bulan' => $bulan,
            'total_hari_kerja' => $totalHariKerja,
            'total_hadir' => $totalHadir,
            'total_terlambat' => $totalTerlambat,
            'total_izin' => $totalIzin,
            'total_sakit' => $totalSakit,
            'total_alpha' => $totalAlpha,
            'persentase_kehadiran' => $persentaseKehadiran,
            'persentase_keterlambatan' => $persentaseKeterlambatan,
        ];
        
        // Add karyawan details if requesting specific karyawan
        if ($karyawanId) {
            $karyawan = Karyawan::find($karyawanId);
            
            if ($karyawan) {
                $reportData['karyawan'] = [
                    'id' => $karyawan->id,
                    'nip' => $karyawan->nip,
                    'nama_lengkap' => $karyawan->nama_lengkap,
                    'jabatan' => $karyawan->jabatan,
                    'departemen' => $karyawan->departemen,
                ];
            }
        }

        return response()->json([
            'status' => true,
            'data' => $reportData
        ]);
    }

    /**
     * Calculate distance between two coordinates in meters
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        // Earth radius in meters
        $earthRadius = 6371000;
        
        // Convert latitude and longitude from degrees to radians
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        
        // Calculate differences
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        
        // Haversine formula
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }
}