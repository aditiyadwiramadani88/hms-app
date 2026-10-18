<!DOCTYPE html>
<html>
<head>
    <title>POS Receipt - {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            width: 80mm; 
            max-width: 100%; 
            margin: 0 auto; 
            padding: 3mm 4mm; 
            font-size: 11px; 
            line-height: 1.25; 
            color: #000; 
            background: #fff; 
        }
        .header { text-align: center; margin-bottom: 2mm; }
        .header h3 { font-size: 13px; font-weight: bold; margin-bottom: 1mm; }
        .header p { font-size: 10px; margin: 0.5mm 0; }
        .divider { border-top: 1px dashed #000; margin: 2mm 0; }
        .details { margin-bottom: 2mm; }
        .item-table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .item-table td { padding: 0.8mm 0; }
        .text-right { text-align: right; }
        .label { font-weight: bold; }
        p.info-line { margin: 1mm 0; font-size: 11px; word-break: break-word; }
        .footer { text-align: center; margin-top: 2mm; font-size: 10px; }
        .footer p { margin: 1mm 0; }
        @page { size: auto; margin: 2mm; }
        @media print {
            body { width: 100%; max-width: 80mm; padding: 1mm 2mm; }
            .no-print { display: none !important; }
            .receipt-box { page-break-inside: avoid; }
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
            <p><span class="label">Kamar:</span> {{ $order->booking?->room?->room_number ?? ($order->booking?->custom_room_name ?? '-') }}</p>
        @endif
        <p><span class="label">Tanggal:</span> {{ $order->created_at->format('d/m/Y H:i') }}</p>
        @if($order->payment_method)
        <p><span class="label">Metode:</span> {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</p>
        @endif
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
        @if(($order->discount_amount ?? 0) > 0)
        <tr>
            <td>Discount</td>
            <td class="text-right">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if(($order->tax_amount ?? 0) > 0)
        <tr>
            <td>Tax</td>
            <td class="text-right">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</td>
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
