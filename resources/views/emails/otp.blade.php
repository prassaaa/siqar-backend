<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Verifikasi SIQAR</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        .header img {
            max-width: 150px;
            height: auto;
        }
        .content {
            padding: 20px 0;
        }
        .otp-code {
            background-color: #7c3aed;
            color: #fff;
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            padding: 15px;
            margin: 20px 0;
            letter-spacing: 5px;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="color: #7c3aed;">SIQAR</h1>
            <p>Sistem QR Absensi Responsif</p>
        </div>
        
        <div class="content">
            <h2>Verifikasi Email Anda</h2>
            <p>Halo,</p>
            <p>Silakan gunakan kode OTP berikut untuk memverifikasi akun SIQAR Anda:</p>
            
            <div class="otp-code">{{ $otp }}</div>
            
            <p>Kode OTP ini hanya berlaku selama 10 menit.</p>
            <p>Jika Anda tidak merasa melakukan pendaftaran atau permintaan reset password di SIQAR, silakan abaikan email ini.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} SIQAR - Sistem QR Absensi Responsif. All rights reserved.</p>
            <p>Email ini dikirim secara otomatis, mohon untuk tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>