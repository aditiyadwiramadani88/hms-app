<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Final Cash - {{ $bankAccount->name }}</title>
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
        <h2>Laporan Final Cash: {{ $bankAccount->name }}</h2>
        <p>Periode: {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : 'Awal' }} - {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : 'Sekarang' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Referensi</th>
                <th>Deskripsi</th>
                <th class="text-end">Jumlah</th>
                <th class="text-end">Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mutations as $fcm)
            <tr>
                <td>{{ $fcm->created_at->format('d M Y, H:i') }}</td>
                <td class="{{ $fcm->type === 'in' ? 'text-success' : 'text-danger' }}">
                    {{ $fcm->type === 'in' ? 'Masuk' : 'Keluar' }}
                </td>
                <td>
                    @if($fcm->reference_type === 'booking_checkout')
                        #BOOK-{{ $fcm->reference_id }}
                    @elseif($fcm->reference_type === 'owner_withdrawal')
                        Withdrawal
                    @else
                        Pindah Saldo
                    @endif
                </td>
                <td>
                    <div>{{ $fcm->description }}</div>
                    <div style="font-size: 10px; color: #777;">By: {{ $fcm->user->name ?? 'System' }}</div>
                </td>
                <td class="text-end {{ $fcm->type === 'in' ? 'text-success' : 'text-danger' }}">
                    {{ $fcm->type === 'in' ? '+' : '-' }} Rp {{ number_format($fcm->amount, 0, ',', '.') }}
                </td>
                <td class="text-end">
                    Rp {{ number_format($fcm->balance_after, 0, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Tidak ada riwayat Final Cash pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div style="text-align: right; font-size: 10px; color: #777;">
        Dicetak pada: {{ now()->format('d M Y H:i:s') }}
    </div>
</body>
</html>
