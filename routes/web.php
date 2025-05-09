<?php

use Illuminate\Support\Facades\Route;
use App\Models\QRCode;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/admin');
});

// Route untuk menampilkan QR Code
Route::get('/qrcode/{id}', function ($id) {
    $qrCode = QRCode::find($id);
    
    if (!$qrCode || !$qrCode->kode) {
        return abort(404);
    }
    
    $qrPath = 'public/qrcodes/qrcode-' . $qrCode->id . '.png';
    
    if (!Storage::exists($qrPath)) {
        // Generate QR Code if it doesn't exist
        $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate($qrCode->kode);
            
        Storage::put($qrPath, $qrImage);
    }
    
    return view('qrcode.show', [
        'qrCode' => $qrCode,
        'qrImageUrl' => url('storage/qrcodes/qrcode-' . $qrCode->id . '.png'),
    ]);
})->name('qrcode.show');