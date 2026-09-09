<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kost Bulanan - {{ $month->format('F Y') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            margin: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0 0;
            font-size: 13px;
            font-weight: normal;
        }
        .header .periode {
            margin-top: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #333;
            padding: 3px 5px;
            text-align: center;
            font-size: 8px;
        }
        th {
            background-color: #333;
            color: white;
            font-weight: bold;
        }
        td.text-left {
            text-align: left;
        }
        td.text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 20px;
            font-size: 8px;
            text-align: right;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $hotel->name ?? 'SIMPANG HOMESTAY & KOZZ' }}</h1>
        <h2>LAPORAN KOST BULANAN</h2>
        <div class="periode">Periode: {{ $month->translatedFormat('F Y') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 25px;">NO</th>
                <th rowspan="2">NAMA</th>
                <th rowspan="2">TYPE</th>
                <th rowspan="2">ROOM</th>
                <th rowspan="2" style="width: 25px;">QTY</th>
                <th colspan="2">PERIODE</th>
                <th rowspan="2">HARGA KOST</th>
                <th rowspan="2">JAMINAN</th>
                <th rowspan="2">TGL</th>
                @foreach($bankAccounts as $account)
                    <th rowspan="2">{{ strtoupper($account->name) }}</th>
                @endforeach
            </tr>
            <tr>
                <th>IN</th>
                <th>OUT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td class="text-left">{{ $row['nama'] }}</td>
                <td>{{ $row['type'] }}</td>
                <td>{{ $row['room'] }}</td>
                <td>{{ $row['qty'] }}</td>
                <td>{{ $row['check_in'] }}</td>
                <td>{{ $row['check_out'] }}</td>
                <td class="text-right">{{ number_format($row['harga_kost'], 0, ',', '.') }}</td>
                <td class="text-right">{{ $row['jaminan'] > 0 ? number_format($row['jaminan'], 0, ',', '.') : '-' }}</td>
                <td>{{ $row['tgl_bayar'] ?: '-' }}</td>
                @foreach($bankAccounts as $account)
                    <td class="text-right">
                        {{ ($row['payments_by_account'][$account->id] ?? 0) > 0 ? number_format($row['payments_by_account'][$account->id], 0, ',', '.') : '-' }}
                    </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="7" class="text-right">TOTAL:</td>
                <td class="text-right">{{ number_format($totals['harga_kost'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totals['jaminan'], 0, ',', '.') }}</td>
                <td></td>
                @foreach($bankAccounts as $account)
                    <td class="text-right">
                        {{ ($totals['payments'][$account->id] ?? 0) > 0 ? number_format($totals['payments'][$account->id], 0, ',', '.') : '-' }}
                    </td>
                @endforeach
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
