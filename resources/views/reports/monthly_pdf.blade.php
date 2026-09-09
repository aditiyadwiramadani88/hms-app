<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Bulanan - {{ $month->format('Y-m') }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 7px; margin: 12px; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { margin: 0; font-size: 12px; font-weight: bold; }
        .header h2 { margin: 3px 0; font-size: 10px; font-weight: normal; }
        .header .date { font-size: 9px; font-weight: bold; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #333; padding: 2px 3px; font-size: 7px; }
        th { background-color: #333; color: white; font-weight: bold; text-align: center; }
        td.text-right { text-align: right; }
        td.text-center { text-align: center; }
        .date-group-header { background-color: #b8d4fe; font-weight: bold; }
        .subtotal-row { font-weight: bold; background-color: #f0f0f0; }
        .grandtotal-row { font-weight: bold; background-color: #333; color: white; }
        .footer { margin-top: 15px; font-size: 7px; text-align: right; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $hotel->name ?? 'SIMPANG HOMESTAY & KOZZ' }}</h1>
        <h2>LAPORAN KEUANGAN ONLINE DAN TRANSFER</h2>
        <div class="date">Periode: {{ $month->translatedFormat('F Y') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 18px;">NO</th>
                <th style="width: 22px;">TGL<br>LAP</th>
                <th>NAMA</th>
                <th style="width: 30px;">ROOM</th>
                <th style="width: 25px;">BOOK<br>ID</th>
                <th style="width: 30px;">CHECK IN</th>
                <th style="width: 30px;">CHECK OUT</th>
                <th style="width: 40px;">AGODA</th>
                <th style="width: 40px;">REDD</th>
                <th style="width: 40px;">TRAVEL</th>
                <th style="width: 40px;">ONLINE TF</th>
                <th style="width: 30px;">TGL TF</th>
                <th style="width: 30px;">JENIS<br>ANGGOTA</th>
                <th style="width: 30px;">SALES</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData['dateGroups'] as $dateGroup)
                {{-- Date Group Header --}}
                <tr class="date-group-header">
                    <td colspan="14">Hari ke-{{ $dateGroup['day'] }} — {{ $dateGroup['formatted_date'] }}</td>
                </tr>

                {{-- Data Rows --}}
                @foreach($dateGroup['rows'] as $row)
                <tr>
                    <td class="text-center">{{ $row['no'] }}</td>
                    <td class="text-center">{{ $row['tgl_laporan'] }}</td>
                    <td>{{ $row['nama'] }}</td>
                    <td class="text-center">{{ $row['room'] }}</td>
                    <td class="text-center">{{ $row['booking_id'] }}</td>
                    <td class="text-center">{{ $row['check_in'] }}</td>
                    <td class="text-center">{{ $row['check_out'] }}</td>
                    <td class="text-right">{{ $row['agoda'] > 0 ? number_format($row['agoda'], 0, ',', '.') : '' }}</td>
                    <td class="text-right">{{ $row['redd'] > 0 ? number_format($row['redd'], 0, ',', '.') : '' }}</td>
                    <td class="text-right">{{ $row['travel'] > 0 ? number_format($row['travel'], 0, ',', '.') : '' }}</td>
                    <td class="text-right">{{ $row['online_tf'] > 0 ? number_format($row['online_tf'], 0, ',', '.') : '' }}</td>
                    <td class="text-center">{{ $row['tgl_tf'] }}</td>
                    <td class="text-center">{{ $row['jenis_anggota'] }}</td>
                    <td class="text-center">{{ $row['sales'] }}</td>
                </tr>
                @endforeach

                {{-- Subtotal Row --}}
                <tr class="subtotal-row">
                    <td colspan="7" class="text-right">Subtotal {{ $dateGroup['formatted_date'] }}</td>
                    <td class="text-right">{{ number_format($dateGroup['subtotal']['agoda'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($dateGroup['subtotal']['redd'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($dateGroup['subtotal']['travel'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($dateGroup['subtotal']['online_tf'], 0, ',', '.') }}</td>
                    <td colspan="3"></td>
                </tr>
            @endforeach
        </tbody>
        @if(count($reportData['dateGroups']) > 0)
        <tfoot>
            <tr class="grandtotal-row">
                <td colspan="7" class="text-right">GRAND TOTAL</td>
                <td class="text-right">{{ number_format($reportData['grandTotals']['agoda'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($reportData['grandTotals']['redd'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($reportData['grandTotals']['travel'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($reportData['grandTotals']['online_tf'], 0, ',', '.') }}</td>
                <td colspan="3">Total: {{ number_format($reportData['grandTotal'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
