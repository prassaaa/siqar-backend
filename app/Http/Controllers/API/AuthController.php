<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pengguna;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:mongodb.pengguna,email',
            'password' => 'required|string|min:8|confirmed',
            'nama_lengkap' => 'required|string|max:255',
            'nip' => 'required|string|max:20|unique:mongodb.karyawan,nip',
            'jabatan' => 'required|string|max:100',
            'departemen' => 'required|string|max:100',
            'no_telepon' => 'required|string|max:15',
            'alamat' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate OTP
        $otp = mt_rand(100000, 999999);
        $otpExpiry = Carbon::now()->addMinutes(10);

        // Create pengguna
        $pengguna = Pengguna::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'peran' => 'karyawan',
            'status' => 'nonaktif', // akan diaktifkan setelah verifikasi email
            'otp' => $otp,
            'otp_expiry' => $otpExpiry,
        ]);

        // Create karyawan
        $karyawan = Karyawan::create([
            'pengguna_id' => $pengguna->id,
            'nip' => $request->nip,
            'nama_lengkap' => $request->nama_lengkap,
            'jabatan' => $request->jabatan,
            'departemen' => $request->departemen,
            'no_telepon' => $request->no_telepon,
            'alamat' => $request->alamat,
            'tanggal_bergabung' => Carbon::now(),
            'status_karyawan' => 'kontrak',
        ]);

        // Send OTP Email
        try {
            Mail::send('emails.otp', ['otp' => $otp], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Kode OTP Verifikasi Akun SIQAR');
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengirim email OTP: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Pendaftaran berhasil, silakan verifikasi email Anda',
            'data' => [
                'user_id' => $pengguna->id,
                'email' => $pengguna->email
            ]
        ], 201);
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:mongodb.pengguna,email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $pengguna = Pengguna::where('email', $request->email)->first();

        if (!$pengguna || $pengguna->otp != $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Kode OTP tidak valid',
            ], 422);
        }

        if (Carbon::now() > $pengguna->otp_expiry) {
            return response()->json([
                'status' => false,
                'message' => 'Kode OTP sudah kedaluwarsa',
            ], 422);
        }

        // Activate user
        $pengguna->status = 'aktif';
        $pengguna->email_verified_at = Carbon::now();
        $pengguna->otp = null;
        $pengguna->otp_expiry = null;
        $pengguna->save();

        // Generate token
        $token = $pengguna->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Verifikasi OTP berhasil',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $pengguna->id,
                    'nama' => $pengguna->nama,
                    'email' => $pengguna->email,
                    'peran' => $pengguna->peran,
                ]
            ]
        ]);
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:mongodb.pengguna,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $pengguna = Pengguna::where('email', $request->email)->first();

        if ($pengguna->status == 'aktif' && $pengguna->email_verified_at) {
            return response()->json([
                'status' => false,
                'message' => 'Email sudah diverifikasi',
            ], 422);
        }

        // Generate new OTP
        $otp = mt_rand(100000, 999999);
        $otpExpiry = Carbon::now()->addMinutes(10);

        $pengguna->otp = $otp;
        $pengguna->otp_expiry = $otpExpiry;
        $pengguna->save();

        // Send OTP Email
        try {
            Mail::send('emails.otp', ['otp' => $otp], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Kode OTP Verifikasi Akun SIQAR');
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengirim email OTP: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Kode OTP baru telah dikirim ke email Anda',
        ]);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $pengguna = Pengguna::where('email', $request->email)->first();

        if (!$pengguna || !Hash::check($request->password, $pengguna->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Email atau password salah',
            ], 401);
        }

        if ($pengguna->status != 'aktif') {
            return response()->json([
                'status' => false,
                'message' => 'Akun Anda belum aktif atau telah dinonaktifkan',
            ], 401);
        }

        // Update last login
        $pengguna->terakhir_login = Carbon::now();
        $pengguna->save();

        // Generate token
        $token = $pengguna->createToken('auth_token')->plainTextToken;

        // Get karyawan data if user is karyawan
        $karyawanData = null;
        if ($pengguna->peran == 'karyawan') {
            $karyawan = Karyawan::where('pengguna_id', $pengguna->id)->first();
            if ($karyawan) {
                $karyawanData = [
                    'id' => $karyawan->id,
                    'nip' => $karyawan->nip,
                    'nama_lengkap' => $karyawan->nama_lengkap,
                    'jabatan' => $karyawan->jabatan,
                    'departemen' => $karyawan->departemen,
                    'no_telepon' => $karyawan->no_telepon,
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Login berhasil',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $pengguna->id,
                    'nama' => $pengguna->nama,
                    'email' => $pengguna->email,
                    'peran' => $pengguna->peran,
                    'foto_profil' => $pengguna->foto_profil,
                    'karyawan' => $karyawanData,
                ]
            ]
        ]);
    }

    /**
     * Forgot Password
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:mongodb.pengguna,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $pengguna = Pengguna::where('email', $request->email)->first();

        // Generate reset token
        $resetToken = Str::random(60);
        $resetExpiry = Carbon::now()->addHours(1);

        $pengguna->reset_token = $resetToken;
        $pengguna->reset_expiry = $resetExpiry;
        $pengguna->save();

        // Send reset password email
        try {
            Mail::send('emails.reset-password', [
                'token' => $resetToken,
                'expiry' => $resetExpiry->format('H:i'),
            ], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Reset Password SIQAR');
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengirim email reset password: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Link reset password telah dikirim ke email Anda',
        ]);
    }

    /**
     * Reset Password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $pengguna = Pengguna::where('reset_token', $request->token)->first();

        if (!$pengguna) {
            return response()->json([
                'status' => false,
                'message' => 'Token tidak valid',
            ], 422);
        }

        if (Carbon::now() > $pengguna->reset_expiry) {
            return response()->json([
                'status' => false,
                'message' => 'Token reset password sudah kedaluwarsa',
            ], 422);
        }

        // Update password
        $pengguna->password = Hash::make($request->password);
        $pengguna->reset_token = null;
        $pengguna->reset_expiry = null;
        $pengguna->save();

        return response()->json([
            'status' => true,
            'message' => 'Password berhasil direset, silakan login',
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $pengguna = $request->user();
        
        // Get karyawan data if user is karyawan
        $karyawanData = null;
        if ($pengguna->peran == 'karyawan') {
            $karyawan = Karyawan::where('pengguna_id', $pengguna->id)->first();
            if ($karyawan) {
                $karyawanData = [
                    'id' => $karyawan->id,
                    'nip' => $karyawan->nip,
                    'nama_lengkap' => $karyawan->nama_lengkap,
                    'jabatan' => $karyawan->jabatan,
                    'departemen' => $karyawan->departemen,
                    'no_telepon' => $karyawan->no_telepon,
                    'alamat' => $karyawan->alamat,
                    'tanggal_bergabung' => $karyawan->tanggal_bergabung->format('Y-m-d'),
                    'status_karyawan' => $karyawan->status_karyawan,
                ];
            }
        }

        return response()->json([
            'status' => true,
            'data' => [
                'user' => [
                    'id' => $pengguna->id,
                    'nama' => $pengguna->nama,
                    'email' => $pengguna->email,
                    'peran' => $pengguna->peran,
                    'status' => $pengguna->status,
                    'foto_profil' => $pengguna->foto_profil,
                    'terakhir_login' => $pengguna->terakhir_login ? $pengguna->terakhir_login->format('Y-m-d H:i:s') : null,
                    'karyawan' => $karyawanData,
                ]
            ]
        ]);
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $pengguna = $request->user();
        
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:mongodb.pengguna,email,' . $pengguna->id . ',_id',
            'foto_profil' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'current_password' => 'sometimes|required_with:new_password',
            'new_password' => 'sometimes|string|min:8|confirmed',
            'nama_lengkap' => 'sometimes|string|max:255',
            'jabatan' => 'sometimes|string|max:100',
            'departemen' => 'sometimes|string|max:100',
            'no_telepon' => 'sometimes|string|max:15',
            'alamat' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify current password if changing password
        if ($request->has('current_password') && $request->has('new_password')) {
            if (!Hash::check($request->current_password, $pengguna->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Password saat ini tidak valid',
                ], 422);
            }

            $pengguna->password = Hash::make($request->new_password);
        }

        // Update user profile
        if ($request->has('nama')) {
            $pengguna->nama = $request->nama;
        }

        if ($request->has('email') && $request->email != $pengguna->email) {
            $pengguna->email = $request->email;
            $pengguna->email_verified_at = null;
            $pengguna->status = 'nonaktif';
            
            // Generate OTP for new email verification
            $otp = mt_rand(100000, 999999);
            $otpExpiry = Carbon::now()->addMinutes(10);
            $pengguna->otp = $otp;
            $pengguna->otp_expiry = $otpExpiry;
            
            // Send OTP Email
            try {
                Mail::send('emails.otp', ['otp' => $otp], function ($message) use ($request) {
                    $message->to($request->email);
                    $message->subject('Kode OTP Verifikasi Email Baru SIQAR');
                });
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal mengirim email OTP: ' . $e->getMessage(),
                ], 500);
            }
        }

        // Upload and update profile photo
        if ($request->hasFile('foto_profil')) {
            $file = $request->file('foto_profil');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('profile-photos', $fileName, 'public');
            $pengguna->foto_profil = $filePath;
        }

        $pengguna->save();

        // Update karyawan data if user is karyawan
        if ($pengguna->peran == 'karyawan') {
            $karyawan = Karyawan::where('pengguna_id', $pengguna->id)->first();
            
            if ($karyawan) {
                if ($request->has('nama_lengkap')) {
                    $karyawan->nama_lengkap = $request->nama_lengkap;
                }
                
                if ($request->has('jabatan')) {
                    $karyawan->jabatan = $request->jabatan;
                }
                
                if ($request->has('departemen')) {
                    $karyawan->departemen = $request->departemen;
                }
                
                if ($request->has('no_telepon')) {
                    $karyawan->no_telepon = $request->no_telepon;
                }
                
                if ($request->has('alamat')) {
                    $karyawan->alamat = $request->alamat;
                }
                
                $karyawan->save();
            }
        }

        // Check if email was changed
        if ($request->has('email') && $request->email != $pengguna->email) {
            return response()->json([
                'status' => true,
                'message' => 'Profil berhasil diperbarui. Silakan verifikasi email baru Anda.',
                'email_changed' => true,
                'email' => $request->email
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Profil berhasil diperbarui',
        ]);
    }
}