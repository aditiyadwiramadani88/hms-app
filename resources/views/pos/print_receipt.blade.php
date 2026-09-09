<!DOCTYPE html>
<html>
<head>
    <title>POS Receipt - {{ $order->order_number }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; width: 80mm; margin: 0; padding: 5mm; font-size: 12px; }
        .header { text-align: center; margin-bottom: 5mm; }
        .divider { border-top: 1px dashed #000; margin: 2mm 0; }
        .details { margin-bottom: 5mm; }
        .item-table { width: 100%; border-collapse: collapse; }
        .item-table td { padding: 1mm 0; }
        .text-right { text-align: right; }
        .label { font-weight: bold; }
        .footer { text-align: center; margin-top: 5mm; font-size: 10px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h3>{{ $order->hotel->name ?? 'Our Homestay' }}</h3>
        <p>{{ $order->hotel->address ?? '' }}</p>
    </div>
    
    <div class="divider"></div>
    <div class="header">
        <strong>POS RECEIPT</strong>
    </div>
    <div class="divider"></div>

    <div class="details">
        <p><span class="label">Order:</span> {{ $order->order_number }}</p>
        <p><span class="label">Tamu:</span> {{ $order->guest->name ?? 'Walk-in' }}</p>
        @if($order->booking_id)
            <p><span class="label">Kamar:</span> {{ $order->booking->room->room_number }}</p>
        @endif
        <p><span class="label">Tanggal:</span> {{ $order->created_at->format('d/m/Y H:i') }}</p>
        <p><span class="label">Status:</span> {{ strtoupper($order->payment_status) }}</p>
    </div>

    <div class="divider"></div>
    
    <table class="item-table">
        @foreach($order->items as $item)
        <tr>
            <td colspan="2">{{ $item->item_name }}</td>
        </tr>
        <tr>
            <td>{{ $item->quantity }} x {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
            <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <table class="item-table">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($order->discount_amount > 0)
        <tr>
            <td>Discount</td>
            <td class="text-right">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr style="font-weight: bold; font-size: 14px;">
            <td>TOTAL</td>
            <td class="text-right">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>
    <div class="footer">
        <p>Terima kasih atas kunjungan Anda!</p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()">Print Again</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
