<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Harian - {{ $date->format('d-m-Y') }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; margin: 15px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 14px; font-weight: bold; }
        .header h2 { margin: 5px 0; font-size: 12px; font-weight: normal; }
        .header .date { font-size: 11px; font-weight: bold; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 3px 5px; font-size: 8px; }
        th { background-color: #333; color: white; font-weight: bold; text-align: center; }
        td.text-right { text-align: right; }
        td.text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #f0f0f0; }
        .footer { margin-top: 20px; font-size: 8px; text-align: right; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $hotel->name ?? 'SIMPANG HOMESTAY & KOZZ' }}</h1>
        <h2>Laporan Semua Pendapatan & Pengeluaran</h2>
        <div class="date">Tanggal: {{ $date->format('d-m-Y') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 20px;">NO</th>
                <th rowspan="2" style="width: 50px;">CHECKIN</th>
                <th rowspan="2" style="width: 50px;">CHECKOUT</th>
                <th rowspan="2" style="width: 40px;">STAY</th>
                <th rowspan="2" style="width: 40px;">JENIS</th>
                <th rowspan="2" style="width: 55px;">NAMA ROOM</th>
                <th rowspan="2" style="width: 35px;">ID</th>
                <th rowspan="2">KETERANGAN</th>
                <th rowspan="2" style="width: 55px;">TGL BAYAR</th>
                <th colspan="{{ $bankAccounts->count() ?: 1 }}">KAS MASUK</th>
                <th colspan="{{ $bankAccounts->count() ?: 1 }}">KAS KELUAR</th>
                <th rowspan="2" style="width: 55px;">BAYAR</th>
                <th rowspan="2" style="width: 55px;">MANUAL INCOME</th>
            </tr>
            <tr>
                @forelse($bankAccounts as $wallet)
                    <th>{{ $wallet->name }}</th>
                @empty
                    <th>-</th>
                @endforelse
                @forelse($bankAccounts as $wallet)
                    <th>{{ $wallet->name }}</th>
                @empty
                    <th>-</th>
                @endforelse
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td class="text-center">{{ $row['no'] }}</td>
                <td class="text-center">{{ $row['checkin'] ?? '-' }}</td>
                <td class="text-center">{{ $row['checkout'] ?? '-' }}</td>
                <td class="text-center">{{ $row['lama_stay'] ?? '-' }}</td>
                <td class="text-center">{{ $row['sumber'] ?? '-' }}</td>
                <td>{{ $row['room_name'] ?? '-' }}</td>
                <td class="text-center">#{{ $row['booking_id'] ?? '-' }}</td>
                <td>{{ Str::limit($row['keterangan'], 60) }}</td>
                <td class="text-nowrap">{{ $row['payment_date'] ?? '-' }}</td>
                
                {{-- KAS MASUK sub-columns --}}
                @forelse($bankAccounts as $wallet)
                    <td class="text-right">
                        @if($row['kas_masuk'] > 0 && ($row['bank_account_id'] ?? null) == $wallet->id)
                            {{ number_format($row['kas_masuk'], 0, ',', '.') }}
                        @endif
                    </td>
                @empty
                    <td class="text-right">
                        {{ $row['kas_masuk'] > 0 ? number_format($row['kas_masuk'], 0, ',', '.') : '' }}
                    </td>
                @endforelse

                {{-- KAS KELUAR sub-columns --}}
                @forelse($bankAccounts as $wallet)
                    <td class="text-right">
                        @if($row['kas_keluar'] > 0 && ($row['bank_account_id'] ?? null) == $wallet->id)
                            {{ number_format($row['kas_keluar'], 0, ',', '.') }}
                        @endif
                    </td>
                @empty
                    <td class="text-right">
                        {{ $row['kas_keluar'] > 0 ? number_format($row['kas_keluar'], 0, ',', '.') : '' }}
                    </td>
                @endforelse

                <td class="text-right">{{ $row['bayar'] > 0 ? number_format($row['bayar'], 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ ($row['manual_income'] ?? 0) > 0 ? number_format($row['manual_income'], 0, ',', '.') : '' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="9" class="text-right">Total:</td>
                
                {{-- KAS MASUK totals per wallet --}}
                @forelse($bankAccounts as $wallet)
                    @php
                        $walletMasuk = collect($rows)->where('bank_account_id', $wallet->id)->sum('kas_masuk');
                    @endphp
                    <td class="text-right">{{ $walletMasuk > 0 ? number_format($walletMasuk, 0, ',', '.') : '-' }}</td>
                @empty
                    <td class="text-right">{{ number_format($totals['kas_masuk'], 0, ',', '.') }}</td>
                @endforelse

                {{-- KAS KELUAR totals per wallet --}}
                @forelse($bankAccounts as $wallet)
                    @php
                        $walletKeluar = collect($rows)->where('bank_account_id', $wallet->id)->sum('kas_keluar');
                    @endphp
                    <td class="text-right">{{ $walletKeluar > 0 ? number_format($walletKeluar, 0, ',', '.') : '-' }}</td>
                @empty
                    <td class="text-right">{{ number_format($totals['kas_keluar'], 0, ',', '.') }}</td>
                @endforelse

                <td class="text-right">{{ number_format($totals['bayar'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totals['manual_income'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
