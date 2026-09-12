<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Housekeeping - {{ $monthObj->format('Y-m') }}</title>
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
        .section-title { font-size: 11px; font-weight: bold; margin: 15px 0 5px; }
        .footer { margin-top: 20px; font-size: 8px; text-align: right; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $hotel->name ?? 'SIMPANG HOMESTAY & KOZZ' }}</h1>
        <h2>LAPORAN TUGAS HOUSEKEEPING</h2>
        <div class="date">Periode: {{ $monthObj->translatedFormat('F Y') }}</div>
    </div>

    {{-- Tasks per staff --}}
    @if(!empty($reportData['grouped_by_staff']) && count($reportData['grouped_by_staff']) > 0)
        @foreach($reportData['grouped_by_staff'] as $staffName => $tasks)
        <div class="section-title">STAFF: {{ strtoupper($staffName) }} ({{ count($tasks) }} Kamar Selesai)</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 25px;">NO</th>
                    <th>TANGGAL</th>
                    <th>KAMAR YANG DIKERJAKAN</th>
                    <th>NAMA STAFF</th>
                    <th>SUMBER BOOKING</th>
                    <th>KATEGORI BONUS</th>
                    <th>STATUS VERIFIKASI</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $i => $d)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $d['tanggal'] }}</td>
                    <td>{{ $d['kamar'] }}</td>
                    <td>{{ $d['staff'] }}</td>
                    <td class="text-center">{{ $d['sumber'] }}</td>
                    <td class="text-center">{{ $d['kategori_bonus'] }}</td>
                    <td class="text-center">{{ $d['status_verifikasi'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
    @else
        <p style="text-align:center; padding: 20px; color:#666;">Tidak ada data tugas housekeeping di periode ini.</p>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
