<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>POS Orders - {{ $dateFrom ?? 'Semua' }} s/d {{ $dateTo ?? 'Semua' }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; margin: 20px 30px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; font-weight: bold; }
        .header .sub { margin-top: 4px; font-size: 11px; }
        table.main { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.main th, table.main td { border: 1px solid #333; padding: 4px 6px; font-size: 9px; }
        table.main th { background-color: #333; color: white; font-weight: bold; text-align: center; }
        table.main td.text-right { text-align: right; }
        table.main td.text-center { text-align: center; }
        tfoot td { font-weight: bold; background-color: #f0f0f0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $hotel->name ?? 'Hotel' }} - POS Orders</h1>
        <div class="sub">Periode: {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Awal' }} s/d {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : 'Sekarang' }}</div>
    </div>

    <table class="main">
        <thead>
            <tr>
                <th>No</th>
                <th>Order #</th>
                <th>Tanggal</th>
                <th>Guest / Room</th>
                <th>Kasir</th>
                <th>Metode Bayar</th>
                <th>Status Bayar</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $i => $order)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $order->order_number }}</td>
                <td class="text-center">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $order->guest->name ?? $order->booking->room->room_number ?? '-' }}</td>
                <td>{{ $order->user->name ?? '-' }}</td>
                <td class="text-center">{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? '-')) }}</td>
                <td class="text-center">{{ ucfirst($order->payment_status) }}</td>
                <td class="text-right">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center">Tidak ada order pada periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="text-right">Total ({{ $orders->count() }} order)</td>
                <td class="text-right">Rp {{ number_format($orders->sum('total_amount'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
