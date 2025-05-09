<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absensi</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
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
        .info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #7c3aed;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777;
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .badge-success {
            background-color: #10b981;
        }
        .badge-warning {
            background-color: #f59e0b;
        }
        .badge-info {
            background-color: #3b82f6;
        }
        .badge-danger {
            background-color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SIQAR - Sistem QR Absensi Responsif</h1>
            <p>Laporan Absensi</p>
        </div>
        
        <div class="info">
            <p><strong>Tanggal Cetak:</strong> {{ $tanggal_cetak }}</p>
            <p><strong>Total Data:</strong> {{ count($records) }}</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Karyawan</th>
                    <th>NIP</th>
                    <th>Tanggal</th>
                    <th>Waktu Masuk</th>
                    <th>Waktu Keluar</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $record['karyawan'] }}</td>
                    <td>{{ $record['nip'] }}</td>
                    <td>{{ $record['tanggal'] }}</td>
                    <td>{{ $record['waktu_masuk'] }}</td>
                    <td>{{ $record['waktu_keluar'] }}</td>
                    <td>
                        @switch($record['status'])
                            @case('hadir')
                                <div class="badge badge-success">Hadir</div>
                                @break
                            @case('terlambat')
                                <div class="badge badge-warning">Terlambat</div>
                                @break
                            @case('izin')
                                <div class="badge badge-info">Izin</div>
                                @break
                            @case('sakit')
                                <div class="badge badge-info">Sakit</div>
                                @break
                            @case('alpha')
                                <div class="badge badge-danger">Alpha</div>
                                @break
                            @default
                                {{ $record['status'] }}
                        @endswitch
                    </td>
                    <td>{{ $record['keterangan'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} SIQAR - Sistem QR Absensi Responsif. All rights reserved.</p>
        </div>
    </div>
</body>
</html>