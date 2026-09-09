<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Shift - {{ $selectedShift->name ?? '' }} - {{ $date->format('d/m/Y') }}</title>
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
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header .shift-title {
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        .info {
            margin-bottom: 10px;
            font-size: 10px;
        }
        .info td {
            padding: 2px 5px;
        }
        .info .label {
            font-weight: bold;
            width: 120px;
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
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
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
            border-collapse: collapse;
        }
        .signature-section td {
            text-align: center;
            padding: 5px 10px;
            vertical-align: top;
            width: 33%;
            border: none;
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
        <h1>{{ $hotel->name ?? 'SIMPANG HOMESTAY & KOZZ' }}</h1>
        <div class="shift-title">{{ strtoupper($selectedShift->name ?? 'SHIFT') }}</div>
    </div>

    <table class="info">
        <tr>
            <td class="label">Tanggal</td>
            <td>: {{ $date->format('d/m/Y') }} ({{ $date->translatedFormat('l') }})</td>
        </tr>
        <tr>
            <td class="label">Jam Shift</td>
            <td>: {{ \Carbon\Carbon::parse($selectedShift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($selectedShift->end_time)->format('H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Petugas</td>
            <td>: {{ $employeesOnShift->pluck('name')->join(', ') ?: '-' }}</td>
        </tr>
    </table>

    @if($confirmedHandovers->count() > 0)
    <table class="main" style="margin-bottom: 5px;">
        <thead>
            <tr>
                <th>DARI SHIFT</th>
                <th>PETUGAS KELUAR</th>
                <th>UANG DISERAHKAN</th>
                <th>DIKONFIRMASI</th>
            </tr>
        </thead>
        <tbody>
            @foreach($confirmedHandovers as $handover)
            <tr>
                <td>{{ $handover->outgoingShift->name ?? '-' }}</td>
                <td>{{ $handover->outgoingEmployee->name ?? '-' }}</td>
                <td class="text-right">{{ number_format($handover->cash_amount, 0, ',', '.') }}</td>
                <td>{{ $handover->confirmed_at?->format('d/m/Y H:i') ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <table class="main">
        <thead>
            <tr>
                <th rowspan="2" style="width: 25px;">NO</th>
                <th rowspan="2">ROOM</th>
                <th rowspan="2">HARI</th>
                <th colspan="2">PEMASUKAN</th>
                <th rowspan="2">PENGELUARAN</th>
                <th rowspan="2">KETERANGAN</th>
                <th rowspan="2">CARA BAYAR</th>
                <th rowspan="2">P/M</th>
            </tr>
            <tr>
                <th>ROOM (TF)</th>
                <th>ROOM (CASH)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['room'] }}</td>
                <td>{{ $row['hari'] }}</td>
                <td class="text-right">{{ $row['pemasukan_room_transfer'] > 0 ? number_format($row['pemasukan_room_transfer'], 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ $row['pemasukan_room_cash'] > 0 ? number_format($row['pemasukan_room_cash'], 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ $row['pengeluaran'] > 0 ? number_format($row['pengeluaran'], 0, ',', '.') : '' }}</td>
                <td>{{ $row['description'] ?? '' }}</td>
                <td>{{ strtoupper($row['payment_method'] ?? '-') }}</td>
                <td>{{ $row['pengeluaran'] > 0 ? 'M' : 'P' }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL</td>
                <td class="text-right">{{ number_format($totals['pemasukan_room_transfer'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totals['pemasukan_room_cash'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totals['pengeluaran'], 0, ',', '.') }}</td>
                <td colspan="3"></td>
            </tr>
            {{-- Empty rows to fill the page --}}
            @for($i = count($reportData) + 2; $i <= 35; $i++)
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
            </tr>
            @endfor
        </tbody>
    </table>

    {{-- Summary Footer --}}
    <table class="main" style="width: 250px; margin-top: 5px;">
        <tr>
            <td class="text-left" style="font-weight: bold;">UANG MASUK</td>
            <td class="text-right">{{ number_format($totals['pemasukan_room'] + $totals['pemasukan_lain'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="text-left" style="font-weight: bold;">UANG KELUAR</td>
            <td class="text-right">{{ number_format($totals['pengeluaran'], 0, ',', '.') }}</td>
        </tr>
        <tr style="font-weight: bold; background-color: #eee;">
            <td class="text-left">SISA</td>
            <td class="text-right">{{ number_format($totals['pemasukan_room'] + $totals['pemasukan_lain'] - $totals['pengeluaran'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="text-left" style="font-weight: bold;">PETUGAS</td>
            <td class="text-left">{{ $employeesOnShift->pluck('name')->join(', ') ?: '-' }}</td>
        </tr>
    </table>

    {{-- Signature Section --}}
    <table class="signature-section">
        <tr>
            <td>
                <p>Petugas Shift</p>
                <div class="signature-line"></div>
                <p>( {{ $employeesOnShift->first()?->name ?? '........................' }} )</p>
            </td>
            <td>
                <p>Mengetahui</p>
                <div class="signature-line"></div>
                <p>( ........................ )</p>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
