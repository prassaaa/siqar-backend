<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lokasi;
use Illuminate\Support\Facades\Validator;

class LokasiController extends Controller
{
    /**
     * Get all locations
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $status = $request->status;
        $search = $request->search;

        // Build query
        $query = Lokasi::query();
        
        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'aktif'); // Default to active locations only
        }
        
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('nama_lokasi', 'like', "%$search%")
                    ->orWhere('alamat', 'like', "%$search%");
            });
        }
        
        // Get paginated results
        $perPage = $request->per_page ?? 10;
        $lokasi = $query->orderBy('nama_lokasi', 'asc')
            ->paginate($perPage);
        
        // Transform data
        $result = $lokasi->map(function ($item) {
            return [
                'id' => $item->id,
                'nama_lokasi' => $item->nama_lokasi,
                'alamat' => $item->alamat,
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'radius' => $item->radius,
                'status' => $item->status,
                'keterangan' => $item->keterangan,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'lokasi' => $result,
                'pagination' => [
                    'total' => $lokasi->total(),
                    'per_page' => $lokasi->perPage(),
                    'current_page' => $lokasi->currentPage(),
                    'last_page' => $lokasi->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Get a specific location
     */
    public function show($id)
    {
        $lokasi = Lokasi::find($id);
        
        if (!$lokasi) {
            return response()->json([
                'status' => false,
                'message' => 'Lokasi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'lokasi' => [
                    'id' => $lokasi->id,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'alamat' => $lokasi->alamat,
                    'latitude' => $lokasi->latitude,
                    'longitude' => $lokasi->longitude,
                    'radius' => $lokasi->radius,
                    'status' => $lokasi->status,
                    'keterangan' => $lokasi->keterangan,
                ]
            ]
        ]);
    }

    /**
     * Create a new location (Admin only)
     */
    public function store(Request $request)
    {
        // Only allow admin to access
        $pengguna = $request->user();
        
        if ($pengguna->peran != 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya admin yang dapat menambahkan lokasi',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_lokasi' => 'required|string|max:255',
            'alamat' => 'required|string|max:500',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric|min:10',
            'status' => 'required|in:aktif,nonaktif',
            'keterangan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create location
        $lokasi = Lokasi::create([
            'nama_lokasi' => $request->nama_lokasi,
            'alamat' => $request->alamat,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius,
            'status' => $request->status,
            'keterangan' => $request->keterangan,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Lokasi berhasil ditambahkan',
            'data' => [
                'lokasi' => [
                    'id' => $lokasi->id,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'alamat' => $lokasi->alamat,
                    'latitude' => $lokasi->latitude,
                    'longitude' => $lokasi->longitude,
                    'radius' => $lokasi->radius,
                    'status' => $lokasi->status,
                    'keterangan' => $lokasi->keterangan,
                ]
            ]
        ], 201);
    }

    /**
     * Update a location (Admin only)
     */
    public function update(Request $request, $id)
    {
        // Only allow admin to access
        $pengguna = $request->user();
        
        if ($pengguna->peran != 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya admin yang dapat memperbarui lokasi',
            ], 403);
        }

        // Find location
        $lokasi = Lokasi::find($id);
        
        if (!$lokasi) {
            return response()->json([
                'status' => false,
                'message' => 'Lokasi tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_lokasi' => 'sometimes|string|max:255',
            'alamat' => 'sometimes|string|max:500',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'radius' => 'sometimes|numeric|min:10',
            'status' => 'sometimes|in:aktif,nonaktif',
            'keterangan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update location
        if ($request->has('nama_lokasi')) {
            $lokasi->nama_lokasi = $request->nama_lokasi;
        }
        
        if ($request->has('alamat')) {
            $lokasi->alamat = $request->alamat;
        }
        
        if ($request->has('latitude')) {
            $lokasi->latitude = $request->latitude;
        }
        
        if ($request->has('longitude')) {
            $lokasi->longitude = $request->longitude;
        }
        
        if ($request->has('radius')) {
            $lokasi->radius = $request->radius;
        }
        
        if ($request->has('status')) {
            $lokasi->status = $request->status;
        }
        
        if ($request->has('keterangan')) {
            $lokasi->keterangan = $request->keterangan;
        }
        
        $lokasi->save();

        return response()->json([
            'status' => true,
            'message' => 'Lokasi berhasil diperbarui',
            'data' => [
                'lokasi' => [
                    'id' => $lokasi->id,
                    'nama_lokasi' => $lokasi->nama_lokasi,
                    'alamat' => $lokasi->alamat,
                    'latitude' => $lokasi->latitude,
                    'longitude' => $lokasi->longitude,
                    'radius' => $lokasi->radius,
                    'status' => $lokasi->status,
                    'keterangan' => $lokasi->keterangan,
                ]
            ]
        ]);
    }

    /**
     * Delete a location (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        // Only allow admin to access
        $pengguna = $request->user();
        
        if ($pengguna->peran != 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Hanya admin yang dapat menghapus lokasi',
            ], 403);
        }

        // Find location
        $lokasi = Lokasi::find($id);
        
        if (!$lokasi) {
            return response()->json([
                'status' => false,
                'message' => 'Lokasi tidak ditemukan',
            ], 404);
        }

        // Delete location
        $lokasi->delete();

        return response()->json([
            'status' => true,
            'message' => 'Lokasi berhasil dihapus',
        ]);
    }
}