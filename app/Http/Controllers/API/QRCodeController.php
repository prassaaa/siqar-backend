<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QRCode;
use App\Models\Lokasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use Illuminate\Support\Str;

class QRCodeController extends Controller
{
    /**
     * Check if a QR Code is valid
     */
    public function checkQRCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find QR Code
        $qrCode = QRCode::where('kode', $request->kode)
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

        // Check if QR Code is valid for today
        if (!$now->isSameDay($qrDate)) {
            return response()->json([
                'status' => false,
                'message' => 'QR Code ini tidak berlaku untuk hari ini',
                'data' => [
                    'qrcode' => [
                        'id' => $qrCode->id,
                        'deskripsi' => $qrCode->deskripsi,
                        'tanggal' => $qrCode->tanggal->format('Y-m-d'),
                        'valid' => false,
                    ]
                ]
            ], 200); // Still return 200 to inform it's just expired
        }

        // Check time validity
        $valid = true;
        $message = 'QR Code valid dan dapat digunakan';
        
        if ($now->lt($startTime) || $now->gt($endTime)) {
            $valid = false;
            $message = 'QR Code hanya berlaku dari ' . $startTime->format('H:i') . ' sampai ' . $endTime->format('H:i');
        }

        // Get location data
        $lokasi = Lokasi::find($qrCode->lokasi_id);
        
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'qrcode' => [
                    'id' => $qrCode->id,
                    'deskripsi' => $qrCode->deskripsi,
                    'tanggal' => $qrCode->tanggal->format('Y-m-d'),
                    'waktu_mulai' => $startTime->format('H:i'),
                    'waktu_berakhir' => $endTime->format('H:i'),
                    'valid' => $valid,
                ],
                'lokasi' => $lokasi ? [
                    'id' => $lokasi->id,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'alamat' => $lokasi->alamat,
                    'latitude' => $lokasi->latitude,
                    'longitude' => $lokasi->longitude,
                    'radius' => $lokasi->radius,
                ] : null,
            ]
        ]);
    }

    /**
     * Get latest active QR Code for attendance
     */
    public function getActiveQRCode(Request $request)
    {
        // Only allow karyawan to access
        $pengguna = $request->user();
        
        if ($pengguna->peran != 'karyawan') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya karyawan yang dapat mengakses QR Code absensi',
            ], 403);
        }

        // Get today's active QR Codes
        $today = Carbon::today();
        $now = Carbon::now();
        
        $qrCode = QRCode::where('tanggal', $today)
            ->where('status', 'aktif')
            ->where('waktu_mulai', '<=', $now)
            ->where('waktu_berakhir', '>=', $now)
            ->first();

        if (!$qrCode) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada QR Code aktif saat ini',
            ], 404);
        }

        // Get location data
        $lokasi = Lokasi::find($qrCode->lokasi_id);
        
        return response()->json([
            'status' => true,
            'message' => 'QR Code aktif ditemukan',
            'data' => [
                'qrcode' => [
                    'id' => $qrCode->id,
                    'kode' => $qrCode->kode,
                    'deskripsi' => $qrCode->deskripsi,
                    'tanggal' => $qrCode->tanggal->format('Y-m-d'),
                    'waktu_mulai' => Carbon::parse($qrCode->waktu_mulai)->format('H:i'),
                    'waktu_berakhir' => Carbon::parse($qrCode->waktu_berakhir)->format('H:i'),
                ],
                'lokasi' => $lokasi ? [
                    'id' => $lokasi->id,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'alamat' => $lokasi->alamat,
                    'latitude' => $lokasi->latitude,
                    'longitude' => $lokasi->longitude,
                    'radius' => $lokasi->radius,
                ] : null,
            ]
        ]);
    }

    /**
     * Generate new QR Code (Admin only)
     */
    public function generateQRCode(Request $request)
    {
        // Only allow admin to access
        $pengguna = $request->user();
        
        if ($pengguna->peran != 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya admin yang dapat membuat QR Code',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'deskripsi' => 'required|string|max:255',
            'tanggal' => 'required|date|after_or_equal:today',
            'waktu_mulai' => 'required|date_format:H:i',
            'waktu_berakhir' => 'required|date_format:H:i|after:waktu_mulai',
            'lokasi_id' => 'required|exists:mongodb.lokasi,_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a unique code
        $uniqueCode = Str::random(16);
        
        // Create QR Code record
        $qrCode = QRCode::create([
            'kode' => $uniqueCode,
            'deskripsi' => $request->deskripsi,
            'tanggal' => Carbon::parse($request->tanggal),
            'waktu_mulai' => $request->waktu_mulai,
            'waktu_berakhir' => $request->waktu_berakhir,
            'lokasi_id' => $request->lokasi_id,
            'status' => 'aktif',
            'dibuat_oleh' => $pengguna->id,
        ]);

        // Generate QR Code image
        $qrImage = QrCodeGenerator::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate($uniqueCode);
        
        // Save QR Code to storage
        $path = 'public/qrcodes/qrcode-' . $qrCode->id . '.png';
        \Storage::put($path, $qrImage);
        
        // Get location data
        $lokasi = Lokasi::find($request->lokasi_id);

        return response()->json([
            'status' => true,
            'message' => 'QR Code berhasil dibuat',
            'data' => [
                'qrcode' => [
                    'id' => $qrCode->id,
                    'kode' => $qrCode->kode,
                    'deskripsi' => $qrCode->deskripsi,
                    'tanggal' => $qrCode->tanggal->format('Y-m-d'),
                    'waktu_mulai' => $qrCode->waktu_mulai,
                    'waktu_berakhir' => $qrCode->waktu_berakhir,
                    'status' => $qrCode->status,
                    'image_url' => url('storage/qrcodes/qrcode-' . $qrCode->id . '.png'),
                ],
                'lokasi' => $lokasi ? [
                    'id' => $lokasi->id,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'alamat' => $lokasi->alamat,
                ] : null,
            ]
        ]);
    }

    /**
     * Get all active QR Codes (Admin only)
     */
    public function getAllQRCodes(Request $request)
    {
        // Only allow admin to access
        $pengguna = $request->user();
        
        if ($pengguna->peran != 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya admin yang dapat melihat daftar QR Code',
            ], 403);
        }

        // Get filter parameters
        $tanggalMulai = $request->tanggal_mulai ? Carbon::parse($request->tanggal_mulai) : Carbon::now()->subDays(7);
        $tanggalAkhir = $request->tanggal_akhir ? Carbon::parse($request->tanggal_akhir) : Carbon::now()->addDays(7);
        $status = $request->status;
        $lokasiId = $request->lokasi_id;

        // Build query
        $query = QRCode::whereBetween('tanggal', [$tanggalMulai->format('Y-m-d'), $tanggalAkhir->format('Y-m-d')]);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($lokasiId) {
            $query->where('lokasi_id', $lokasiId);
        }
        
        // Get paginated results
        $perPage = $request->per_page ?? 10;
        $qrCodes = $query->orderBy('tanggal', 'desc')
            ->orderBy('waktu_mulai', 'desc')
            ->with('lokasi')
            ->paginate($perPage);
        
        // Transform data
        $result = $qrCodes->map(function ($item) {
            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'deskripsi' => $item->deskripsi,
                'tanggal' => $item->tanggal->format('Y-m-d'),
                'waktu_mulai' => $item->waktu_mulai,
                'waktu_berakhir' => $item->waktu_berakhir,
                'lokasi' => $item->lokasi ? [
                    'id' => $item->lokasi->id,
                    'nama_lokasi' => $item->lokasi->nama_lokasi,
                ] : null,
                'status' => $item->status,
                'image_url' => $item->kode ? url('storage/qrcodes/qrcode-' . $item->id . '.png') : null,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'qrcodes' => $result,
                'pagination' => [
                    'total' => $qrCodes->total(),
                    'per_page' => $qrCodes->perPage(),
                    'current_page' => $qrCodes->currentPage(),
                    'last_page' => $qrCodes->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Deactivate a QR Code (Admin only)
     */
    public function deactivateQRCode(Request $request, $id)
    {
        // Only allow admin to access
        $pengguna = $request->user();
        
        if ($pengguna->peran != 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya admin yang dapat menonaktifkan QR Code',
            ], 403);
        }

        // Find QR Code
        $qrCode = QRCode::find($id);
        
        if (!$qrCode) {
            return response()->json([
                'status' => false,
                'message' => 'QR Code tidak ditemukan',
            ], 404);
        }

        // Deactivate QR Code
        $qrCode->status = 'nonaktif';
        $qrCode->save();

        return response()->json([
            'status' => true,
            'message' => 'QR Code berhasil dinonaktifkan',
        ]);
    }
}