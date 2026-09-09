<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Parkir - {{ $date->format('Y-m-d') }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; margin: 20px; }
        h3 { text-align: center; margin: 0 0 5px; font-size: 14pt; }
        .subtitle { text-align: center; color: #666; margin-bottom: 15px; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: center; vertical-align: middle; }
        th { background: #eaeaea; font-weight: 700; }
        .total { font-weight: 700; background: #f0f0f0; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .hotel-name { text-align: center; font-size: 10pt; margin-bottom: 2px; }
        .plat { font-family: 'Courier New', monospace; font-size: 7.5pt; }
    </style>
</head>
<body>
    <div class="hotel-name">{{ $hotel->name ?? 'SIMPANG HOMESTAY' }}</div>
    <h3>LAPORAN PARKIR HARIAN</h3>
    <div class="subtitle">{{ $date->translatedFormat('l, d F Y') }}</div>

    <table>
        <thead>
            <tr>
                <th style="width:30px">NO</th>
                <th style="width:60px">KAMAR</th>
                <th style="width:120px">NAMA TAMU</th>
                <th>MOBIL HARIAN</th>
                <th>MOBIL KOST</th>
                <th>MOTOR HARIAN</th>
                <th>MOTOR KOST</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 0; @endphp
            @forelse($grouped as $room => $data)
            @php
                $rowCount = max(1, count($data['mobil_harian']), count($data['mobil_kost']), count($data['motor_harian']), count($data['motor_kost']));
            @endphp
            @for($i = 0; $i < $rowCount; $i++)
            <tr>
                @if($i === 0)
                <td rowspan="{{ $rowCount }}">{{ ++$no }}</td>
                <td rowspan="{{ $rowCount }}">{{ $room }}</td>
                <td class="text-left" rowspan="{{ $rowCount }}">{{ $data['guest_name'] }}</td>
                @endif
                <td class="plat">{{ $data['mobil_harian'][$i]->plate_number ?? '' }}</td>
                <td class="plat">{{ $data['mobil_kost'][$i]->plate_number ?? '' }}</td>
                <td class="plat">{{ $data['motor_harian'][$i]->plate_number ?? '' }}</td>
                <td class="plat">{{ $data['motor_kost'][$i]->plate_number ?? '' }}</td>
            </tr>
            @endfor
            @empty
            <tr><td colspan="7">Tidak ada data</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="3" class="text-right">TOTAL</td>
                <td>{{ $totals['mobil_harian'] }}</td>
                <td>{{ $totals['mobil_kost'] }}</td>
                <td>{{ $totals['motor_harian'] }}</td>
                <td>{{ $totals['motor_kost'] }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px; font-size: 8pt; color: #666;">
        Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
