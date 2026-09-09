<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Maintenance - {{ $hotelName }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0 0 5px 0; font-size: 16px; }
        .header p { margin: 2px 0; color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f5f5f5; font-weight: bold; font-size: 10px; padding: 6px 4px; border: 1px solid #ccc; text-align: center; }
        td { padding: 5px 4px; border: 1px solid #ccc; text-align: center; font-size: 10px; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .check { color: green; font-weight: bold; }
        .empty { color: #ccc; }
        .footer { margin-top: 20px; text-align: right; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN MAINTENANCE</h2>
        <p>{{ $hotelName }}</p>
        <p>Periode: {{ $dateFrom }} - {{ $dateTo }}</p>
        <p>Total Record: {{ $records->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>TGL</th>
                <th>KAMAR</th>
                <th>KATEGORI</th>
                <th>SERVICE</th>
                <th>GANTI MODUL</th>
                <th>TAMBAH FREON</th>
                <th>PENGGANTIAN ALAT</th>
                <th>TUKANG SERVIS</th>
                <th>BIAYA</th>
                <th>STATUS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $rec)
            <tr>
                <td>{{ $rec->maintenance_date->format('d/m/Y') }}</td>
                <td>{{ $rec->room?->room_number ?? 'Umum' }}</td>
                <td>{{ $rec->category?->name ?? '-' }}</td>
                <td class="{{ in_array('Service', $rec->actions ?? []) ? 'check' : 'empty' }}">
                    {{ in_array('Service', $rec->actions ?? []) ? 'V' : '-' }}
                </td>
                <td class="{{ in_array('Ganti Modul', $rec->actions ?? []) ? 'check' : 'empty' }}">
                    {{ in_array('Ganti Modul', $rec->actions ?? []) ? 'V' : '-' }}
                </td>
                <td class="{{ in_array('Tambah Freon', $rec->actions ?? []) ? 'check' : 'empty' }}">
                    {{ in_array('Tambah Freon', $rec->actions ?? []) ? 'V' : '-' }}
                </td>
                <td class="{{ in_array('Penggantian Alat', $rec->actions ?? []) ? 'check' : 'empty' }}">
                    {{ in_array('Penggantian Alat', $rec->actions ?? []) ? 'V' : '-' }}
                </td>
                <td>{{ $rec->technician_name ?? '-' }}</td>
                <td class="text-right">{{ $rec->cost > 0 ? 'Rp '.number_format($rec->cost, 0, ',', '.') : '-' }}</td>
                <td>
                    @if($rec->status === 'completed') Selesai
                    @elseif($rec->status === 'in_progress') Proses
                    @else Jadwal
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center; padding:20px; color:#999;">Tidak ada data</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak: {{ now()->format('d M Y, H:i') }}
    </div>
</body>
</html>
