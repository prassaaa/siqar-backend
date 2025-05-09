<?php

use Illuminate\Support\Facades\Route;
use App\Models\QRCode;
use chillerlan\QRCode\QRCode as QRGenerator;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Log;

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

    $filePath = public_path('qrcodes' . DIRECTORY_SEPARATOR . 'qrcode-' . $qrCode->id . '.png');

    if (!file_exists($filePath)) {
        // Buat direktori jika belum ada
        $directory = public_path('qrcodes');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        try {
            // Generate QR Code jika belum ada
            $options = new QROptions([
                'outputType' => QRGenerator::OUTPUT_IMAGE_PNG,
                'eccLevel' => QRGenerator::ECC_H,
                'scale' => 10,
                'imageBase64' => false,
            ]);

            $qrImage = (new QRGenerator($options))->render($qrCode->kode);

            $success = file_put_contents($filePath, $qrImage);

            if ($success) {
                Log::info("QR Code berhasil disimpan di {$filePath}");
            } else {
                Log::error("Gagal menyimpan QR Code di {$filePath}");
            }
        } catch (\Exception $e) {
            Log::error("Error saat membuat QR Code: " . $e->getMessage());
            return "Error membuat QR Code: " . $e->getMessage();
        }
    }

    return view('qrcode.show', [
        'qrCode' => $qrCode,
        'qrImageUrl' => url('qrcodes/qrcode-' . $qrCode->id . '.png'),
    ]);
})->name('qrcode.show');

// Route untuk API QR Code (jika diperlukan untuk mobile app)
Route::get('/api/qrcode/{id}', function ($id) {
    $qrCode = QRCode::find($id);

    if (!$qrCode || !$qrCode->kode) {
        return response()->json(['error' => 'QR Code tidak ditemukan'], 404);
    }

    $filePath = public_path('qrcodes' . DIRECTORY_SEPARATOR . 'qrcode-' . $qrCode->id . '.png');

    if (file_exists($filePath)) {
        return response()->json([
            'qrcode' => [
                'id' => $qrCode->id,
                'kode' => $qrCode->kode,
                'url' => url('qrcodes/qrcode-' . $qrCode->id . '.png'),
            ]
        ]);
    }

    return response()->json(['error' => 'File QR Code tidak ditemukan'], 404);
});

// Route debugging untuk memeriksa path
Route::get('/debug-path', function () {
    $testPath = public_path('qrcodes');
    $testDir = false;
    $testWrite = false;

    if (!file_exists($testPath)) {
        try {
            mkdir($testPath, 0755, true);
            $testDir = true;
        } catch (\Exception $e) {
            $testDir = $e->getMessage();
        }
    } else {
        $testDir = true;
    }

    try {
        $testFile = $testPath . DIRECTORY_SEPARATOR . 'test.txt';
        $testWrite = file_put_contents($testFile, 'Test write ' . date('Y-m-d H:i:s'));
        if ($testWrite) {
            unlink($testFile);
        }
    } catch (\Exception $e) {
        $testWrite = $e->getMessage();
    }

    $paths = [
        'public_path' => public_path(),
        'qrcodes_path' => $testPath,
        'directory_created' => $testDir,
        'write_test' => $testWrite,
        'directory_separator' => DIRECTORY_SEPARATOR
    ];

    return response()->json($paths);
});
