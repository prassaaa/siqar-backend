<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIQAR - QR Code Absensi</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            color: #7c3aed;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            margin-top: 0;
        }
        .qr-container {
            margin: 30px 0;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .qr-image {
            max-width: 300px;
            height: auto;
            margin: 0 auto;
        }
        .info {
            margin-top: 30px;
            background-color: #f0f0ff;
            padding: 15px;
            border-radius: 8px;
            text-align: left;
        }
        .info p {
            margin: 8px 0;
        }
        .info strong {
            color: #5b21b6;
        }
        .footer {
            margin-top: 40px;
            font-size: 14px;
            color: #777;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            color: white;
        }
        .status-active {
            background-color: #10b981;
        }
        .status-inactive {
            background-color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SIQAR</h1>
            <p>Sistem QR Absensi Responsif</p>
        </div>
        
        <h2>QR Code Absensi</h2>
        <div class="qr-container">
            <img src="{{ $qrImageUrl }}" alt="QR Code Absensi" class="qr-image">
        </div>
        
        <div class="info">
            <p><strong>Deskripsi:</strong> {{ $qrCode->deskripsi }}</p>
            <p><strong>Tanggal:</strong> {{ $qrCode->tanggal->format('d F Y') }}</p>
            <p><strong>Waktu:</strong> {{ \Carbon\Carbon::parse($qrCode->waktu_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($qrCode->waktu_berakhir)->format('H:i') }}</p>
            <p>
                <strong>Status:</strong> 
                <span class="status {{ $qrCode->status == 'aktif' ? 'status-active' : 'status-inactive' }}">
                    {{ $qrCode->status == 'aktif' ? 'Aktif' : 'Nonaktif' }}
                </span>
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} SIQAR - Sistem QR Absensi Responsif.</p>
            <p>Scan QR code ini menggunakan aplikasi SIQAR untuk melakukan absensi.</p>
        </div>
    </div>
</body>
</html>