<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Mutasi {{ $bankAccount->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0 0 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-success { color: green; }
        .text-danger { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Mutasi Akun: {{ $bankAccount->name }}</h2>
        <p>Periode: {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : 'Awal' }} - {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : 'Sekarang' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Referensi / Booking</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th class="text-end">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $trx)
            <tr>
                <td>{{ $trx->created_at->format('d M Y, H:i') }}</td>
                <td>
                    @if($trx->booking_id)
                        #BOOK-{{ $trx->booking_id }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $trx->category->name ?? 'Uncategorized' }}</td>
                <td>
                    <div>{{ $trx->description }}</div>
                    @if($trx->booking)
                        <div style="font-size: 10px; color: #777;">
                            Room {{ $trx->booking->room?->room_number ?? 'N/A' }} | {{ $trx->booking->guest->name ?? 'N/A' }}
                        </div>
                    @endif
                </td>
                <td class="text-end {{ $trx->type === 'payment' ? 'text-success' : 'text-danger' }}">
                    {{ $trx->type === 'payment' ? '+' : '-' }} Rp {{ number_format($trx->amount, 0, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">Tidak ada mutasi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div style="text-align: right; font-size: 10px; color: #777;">
        Dicetak pada: {{ now()->format('d M Y H:i:s') }}
    </div>
</body>
</html>