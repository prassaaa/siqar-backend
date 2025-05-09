<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\LokasiController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AbsensiController;
use App\Http\Controllers\API\QRCodeController;
use App\Http\Controllers\API\AdminDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route untuk autentikasi
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Route yang memerlukan autentikasi
Route::middleware('auth:sanctum')->group(function () {
    // Profil pengguna
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Absensi
    Route::post('/absensi/scan', [AbsensiController::class, 'scanQR']);
    Route::get('/absensi/history', [AbsensiController::class, 'history']);
    Route::get('/absensi/today', [AbsensiController::class, 'today']);
    Route::post('/absensi/request-leave', [AbsensiController::class, 'requestLeave']);
    Route::get('/absensi/monthly-report', [AbsensiController::class, 'monthlyReport']);
    
    // QR Code
    Route::get('/qrcode/check', [QRCodeController::class, 'checkQRCode']);
    Route::get('/qrcode/active', [QRCodeController::class, 'getActiveQRCode']);
    
    // Lokasi
    Route::get('/lokasi', [LokasiController::class, 'index']);
    Route::get('/lokasi/{id}', [LokasiController::class, 'show']);
    
    // Admin Dashboard
    Route::middleware('admin')->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'dashboardData']);
        Route::get('/admin/karyawan', [AdminDashboardController::class, 'getKaryawanList']);
        Route::post('/admin/export-absensi', [AdminDashboardController::class, 'exportAbsensi']);
        
        // QR Code Management (Admin)
        Route::post('/admin/qrcode/generate', [QRCodeController::class, 'generateQRCode']);
        Route::get('/admin/qrcode/list', [QRCodeController::class, 'getAllQRCodes']);
        Route::put('/admin/qrcode/{id}/deactivate', [QRCodeController::class, 'deactivateQRCode']);
        
        // Lokasi Management (Admin)
        Route::post('/lokasi', [LokasiController::class, 'store']);
        Route::put('/lokasi/{id}', [LokasiController::class, 'update']);
        Route::delete('/lokasi/{id}', [LokasiController::class, 'destroy']);
    });
});