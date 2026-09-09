<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Kost Tenant List</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { text-align: center; margin-bottom: 2px; }
        p { text-align: center; color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px 6px; }
        th { background: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>{{ $hotel->name ?? 'Data Kost' }}</h2>
    <p>Data Kost Tenant List - {{ now()->format('d M Y') }}</p>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th class="text-center" style="width: 50px;">Kamar</th>
                <th class="text-center" style="width: 30px;">Isi</th>
                <th>Nama Penghuni</th>
                <th>No Handphone</th>
                <th>Nama Ortu</th>
                <th>Handphone Ortu</th>
                <th class="text-right" style="width: 80px;">Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenantData as $row)
            <tr>
                <td class="text-center">{{ $row['no'] }}</td>
                <td class="text-center"><strong>{{ $row['kamar'] }}</strong></td>
                <td class="text-center">{{ $row['isi'] }}</td>
                <td>{{ $row['nama_penghuni'] }}</td>
                <td>{{ $row['no_handphone'] }}</td>
                <td>{{ $row['nama_ortu'] }}</td>
                <td>{{ $row['handphone_ortu'] }}</td>
                <td class="text-right">
                    @if($row['harga'] > 0)
                        {{ number_format($row['harga'], 0, ',', '.') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        @if(count($tenantData) > 0)
        <tfoot>
            @php
                $totalIsi = collect($tenantData)->sum('isi');
                $totalPenghuni = collect($tenantData)->filter(fn($r) => $r['nama_penghuni'] !== '-')->count();
                $totalHarga = collect($tenantData)->sum('harga');
            @endphp
            <tr style="font-weight: bold; background: #f0f0f0;">
                <td colspan="2" class="text-right">TOTAL</td>
                <td class="text-center">{{ $totalIsi }}</td>
                <td>{{ $totalPenghuni }} penghuni</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right">Rp {{ number_format($totalHarga, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
