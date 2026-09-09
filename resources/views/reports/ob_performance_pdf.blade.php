<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Performa OB - {{ $month->format('Y-m') }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; margin: 15px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 14px; font-weight: bold; }
        .header h2 { margin: 5px 0; font-size: 12px; font-weight: normal; }
        .header .date { font-size: 11px; font-weight: bold; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px 6px; font-size: 9px; }
        th { background-color: #333; color: white; font-weight: bold; text-align: center; }
        td.text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #333; color: white; }
        .footer { margin-top: 20px; font-size: 8px; text-align: right; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $hotel->name ?? 'SIMPANG HOMESTAY & KOZZ' }}</h1>
        <h2>LAPORAN KEBERSIHAN / PERFORMA OB</h2>
        <div class="date">Periode: {{ $month->translatedFormat('F Y') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">NO</th>
                <th>NAMA</th>
                <th>TOTAL</th>
                <th>UMUM</th>
                <th>ONLINE</th>
                <th>KOST</th>
                <th>SALES</th>
                <th>P.K</th>
                <th>KOSONG</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData['rows'] as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $row['nama'] }}</td>
                <td class="text-center">{{ $row['sales'] }}</td>
                <td class="text-center">{{ $row['umum'] }}</td>
                <td class="text-center">{{ $row['online'] }}</td>
                <td class="text-center">{{ $row['kost'] }}</td>
                <td class="text-center">{{ $row['sales_cat'] }}</td>
                <td class="text-center">{{ $row['pk'] }}</td>
                <td class="text-center">{{ $row['kosong'] }}</td>
            </tr>
            @endforeach
        </tbody>
        @if(count($reportData['rows']) > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="text-center">TOTAL</td>
                <td class="text-center">{{ $reportData['totals']['sales'] }}</td>
                <td class="text-center">{{ $reportData['totals']['umum'] }}</td>
                <td class="text-center">{{ $reportData['totals']['online'] }}</td>
                <td class="text-center">{{ $reportData['totals']['kost'] }}</td>
                <td class="text-center">{{ $reportData['totals']['sales_cat'] }}</td>
                <td class="text-center">{{ $reportData['totals']['pk'] }}</td>
                <td class="text-center">{{ $reportData['totals']['kosong'] }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- Detail Table --}}
    @if(count($reportData['details'] ?? []) > 0)
    <div style="margin-top: 20px;">
        <h3 style="font-size: 11px; margin-bottom: 5px;">DETAIL TUGAS HOUSEKEEPING</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 25px;">NO</th>
                    <th>TANGGAL</th>
                    <th>KAMAR</th>
                    <th>STAFF</th>
                    <th>SUMBER BOOKING</th>
                    <th>KATEGORI</th>
                    <th>VERIFIKASI</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['details'] as $i => $d)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $d['tanggal'] }}</td>
                    <td>{{ $d['kamar'] }}</td>
                    <td>{{ $d['staff'] }}</td>
                    <td>{{ $d['sumber'] }}</td>
                    <td class="text-center">{{ $d['kategori'] }}</td>
                    <td class="text-center">{{ $d['status_verifikasi'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
