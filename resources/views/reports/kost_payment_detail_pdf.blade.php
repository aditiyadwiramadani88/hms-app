<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rincian Pembayaran Kost - {{ $booking->room?->room_number ?? 'Custom' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 20px 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header p {
            margin: 3px 0;
            font-size: 11px;
        }
        .header .title {
            margin-top: 10px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 2px 5px;
            font-size: 11px;
        }
        .info-table .label {
            font-weight: bold;
            width: 130px;
        }
        table.main {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.main th, table.main td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: center;
            font-size: 9px;
        }
        table.main th {
            background-color: #333;
            color: white;
            font-weight: bold;
        }
        table.main td.text-right {
            text-align: right;
        }
        table.main td.text-left {
            text-align: left;
        }
        .ttd-check {
            font-size: 14px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 8px;
            text-align: right;
            color: #666;
        }
        .signature-section {
            margin-top: 40px;
            width: 100%;
        }
        .signature-section td {
            text-align: center;
            padding: 5px 10px;
            vertical-align: top;
            width: 33%;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin-top: 50px;
            margin-bottom: 5px;
            margin-left: 20px;
            margin-right: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $hotel->name ?? 'SIMPANG HOMESTAY DAN KOZZ' }}</h1>
        <p>{{ $hotel->address ?? 'JL. SIMPANG BOROBUDUR NO. 41' }}</p>
        <div class="title">RINCIAN PEMBAYARAN KOST BULANAN</div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">NO KAMAR</td>
            <td>: {{ $booking->room?->room_number ?? ($booking->custom_room_name ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label">NAMA PENYEWA</td>
            <td>: {{ $booking->guest->name ?? '-' }}</td>
        </tr>
    </table>

    <table class="main">
        <thead>
            <tr>
                <th rowspan="2" style="width: 25px;">NO</th>
                <th rowspan="2">HARGA<br>KOST</th>
                <th rowspan="2">DEPOSIT</th>
                <th rowspan="2">JUMLAH</th>
                <th colspan="2">TANGGAL</th>
                <th rowspan="2">PERIODE</th>
                <th colspan="3">TTD</th>
            </tr>
            <tr>
                <th>TF</th>
                <th>CASH</th>
                <th>PENGHUNI</th>
                <th>KASIR</th>
                <th>PEMIMPIN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paymentRows as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td class="text-right">{{ number_format($row['harga_kost'], 0, ',', '.') }}</td>
                <td class="text-right">{{ $row['deposit'] > 0 ? number_format($row['deposit'], 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ number_format($row['jumlah'], 0, ',', '.') }}</td>
                <td>{{ $row['tanggal_tf'] ?? '' }}</td>
                <td>{{ $row['tanggal_cash'] ?? '' }}</td>
                <td>{{ $row['periode'] }}</td>
                <td><span class="ttd-check">✓</span></td>
                <td>
                    <span class="ttd-check">✓</span>
                    <br><small>{{ $row['kasir'] }}</small>
                </td>
                <td>
                    @if($row['approval'] && $row['approval']->isApproved())
                        <span class="ttd-check">✓</span>
                        <br><small>{{ $row['approval']->approver->name ?? '' }}</small>
                    @else
                        
                    @endif
                </td>
            </tr>
            @endforeach
            {{-- Empty rows to fill the page like the paper form --}}
            @for($i = count($paymentRows) + 1; $i <= 17; $i++)
            <tr>
                <td>{{ $i }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endfor
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
