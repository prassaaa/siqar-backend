<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password SIQAR</title>
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
        .reset-button {
            display: block;
            width: 200px;
            margin: 30px auto;
            padding: 15px 10px;
            background-color: #7c3aed;
            color: #fff;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .token-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
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
            <h2>Permintaan Reset Password</h2>
            <p>Halo,</p>
            <p>Kami menerima permintaan untuk mereset password akun SIQAR Anda. Silakan gunakan token berikut untuk mereset password Anda:</p>
            
            <div class="token-info">{{ $token }}</div>
            
            <p>Token ini hanya berlaku hingga {{ $expiry }}.</p>
            
            <p>Jika Anda tidak melakukan permintaan reset password, silakan abaikan email ini. Akun Anda akan tetap aman.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} SIQAR - Sistem QR Absensi Responsif. All rights reserved.</p>
            <p>Email ini dikirim secara otomatis, mohon untuk tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>