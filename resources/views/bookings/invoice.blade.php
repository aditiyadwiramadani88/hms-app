<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $booking->id }} - {{ $booking->guest->name }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.4; margin: 0; padding: 20px; background-color: #f9f9f9; }
        .invoice-box { max-width: 780px; margin: auto; border: 1px solid #eee; padding: 30px; box-shadow: 0 0 10px rgba(0, 0, 0, .05); background-color: #fff; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #405189; padding-bottom: 20px; margin-bottom: 20px; }
        .hotel-info h2 { margin: 0; color: #405189; font-size: 20px; }
        .hotel-info p { margin: 3px 0; font-size: 13px; color: #666; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { margin: 0; color: #405189; font-size: 24px; }
        .invoice-title p { margin: 3px 0; font-size: 13px; font-weight: bold; }
        
        .info-grid { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 30px; }
        .info-section { flex: 1; }
        .info-section h3 { border-bottom: 1px solid #eee; padding-bottom: 5px; font-size: 14px; text-transform: uppercase; color: #405189; margin-bottom: 10px; }
        .info-section p { margin: 3px 0; font-size: 13px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th { background: #f8f9fa; text-align: left; padding: 8px 10px; border-bottom: 2px solid #eee; font-size: 12px; text-transform: uppercase; }
        table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; }
        .text-right { text-align: right; }
        
        .totals { margin-left: auto; width: 280px; margin-top: 20px; }
        .totals-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; }
        .totals-row.grand-total { border-top: 2px solid #405189; margin-top: 10px; padding-top: 10px; font-weight: bold; font-size: 16px; color: #405189; }
        .totals-row.balance { color: #f06548; font-weight: bold; }
        
        .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
        
        @media print {
            @page { size: A4; margin: 8mm; }
            body { padding: 0; margin: 0; background-color: #fff; font-size: 10px; }
            .invoice-box { border: none; box-shadow: none; width: 100% !important; max-width: 100% !important; padding: 5mm; box-sizing: border-box; }
            .no-print { display: none; }
            table { width: 100% !important; table-layout: fixed; word-wrap: break-word; }
            table th { background: #f8f9fa !important; -webkit-print-color-adjust: exact; }
            table th, table td { overflow: hidden; text-overflow: ellipsis; }
            .header { border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 8px; }
            .hotel-info h2 { font-size: 15px; }
            .hotel-info p { font-size: 10px; }
            .invoice-title h1 { font-size: 16px; }
            .invoice-title p { font-size: 10px; }
            .grand-total { border-top: 2px solid #000 !important; color: #000 !important; }
            .info-grid { margin-bottom: 10px; gap: 10px; }
            .info-section h3 { font-size: 11px; margin-bottom: 4px; }
            .info-section p { font-size: 10px; margin: 2px 0; }
            table th { padding: 4px 5px; font-size: 9px; }
            table td { padding: 4px 5px; font-size: 10px; }
            .totals { width: 200px; margin-top: 8px; }
            .totals-row { padding: 2px 0; font-size: 10px; }
            .totals-row.grand-total { font-size: 13px; padding-top: 6px; margin-top: 6px; }
            .footer { margin-top: 15px; padding-top: 8px; font-size: 9px; }
        }
        
        .btn-print { background: #0ab39c; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500; }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px; max-width: 780px; margin-left: auto; margin-right: auto;">
        <button onclick="window.print()" class="btn-print">Print Invoice</button>
        <button onclick="window.close()" class="btn-print" style="background: #6c757d; margin-left: 5px;">Close Window</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div class="hotel-info">
                <h2>{{ $booking->hotel->name ?? 'Simpang Homestay & Kozz' }}</h2>
                <p>{{ $booking->hotel->address ?? '-' }}</p>
                <p>Phone: {{ $booking->hotel->phone ?? '-' }}</p>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <p>#INV-{{ date('Ymd') }}-{{ $booking->id }}</p>
                <p>Date: {{ date('d M Y') }}</p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-section">
                <h3>Bill To:</h3>
                <p><strong>{{ $booking->guest->name }}</strong></p>
                <p>{{ $booking->guest->phone ?? '-' }}</p>
                <p>{{ $booking->guest->address ?? '-' }}</p>
            </div>
            <div class="info-section">
                <h3>Booking Info:</h3>
                <p><strong>Room:</strong> {{ $booking->room?->room_number ?? 'N/A' }} ({{ $booking->room?->roomType?->name ?? 'N/A' }})</p>
                <p><strong>Check-in:</strong> {{ $booking->check_in->format('d M Y') }}</p>
                <p><strong>Check-out:</strong> {{ $booking->check_out->format('d M Y') }}</p>
                <p><strong>Nights:</strong> {{ $booking->check_in->diffInDays($booking->check_out) }}</p>
                <p><strong>Guests:</strong> {{ $booking->adults }} Adult(s) @if($booking->children > 0), {{ $booking->children }} Child(ren) @endif</p>
                @if($booking->include_breakfast)
                <p><strong>Breakfast:</strong> ✓ Include</p>
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @if($showRoom)
                <tr>
                    <td>Room Charges ({{ $booking->check_in->diffInDays($booking->check_out) }} nights)</td>
                    <td class="text-right">Rp {{ number_format(($booking->base_price + $roomMarkupTotal) / max(1, $booking->check_in->diffInDays($booking->check_out)), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($booking->base_price + $roomMarkupTotal, 0, ',', '.') }}</td>
                </tr>
                @if($booking->include_breakfast)
                @php
                    $breakfastTotal = $booking->pricing_breakdown['breakfast_total'] ?? 0;
                    $bfNights = $booking->check_in->diffInDays($booking->check_out);
                    $bfPerNight = $bfNights > 0 ? $breakfastTotal / $bfNights : $breakfastTotal;
                @endphp
                <tr>
                    <td>Breakfast ({{ $bfNights }} malam × {{ $booking->adults }} pax)</td>
                    <td class="text-right">Rp {{ number_format($bfPerNight, 0, ',', '.') }} / malam</td>
                    <td class="text-right">Rp {{ number_format($breakfastTotal, 0, ',', '.') }}</td>
                </tr>
                @endif
                @endif

                @if($showExtra)
                    @foreach($extraCharges as $charge)
                    <tr>
                        <td>{{ $charge->description }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">Rp {{ number_format($charge->amount, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @endif

                @if($showPos)
                    @foreach($posOrders as $order)
                        @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->item_name }} (x{{ $item->quantity }})</td>
                            <td class="text-right">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    @endforeach
                @endif

                @if($showRoom && $booking->tax_amount > 0)
                <tr>
                    <td>Tax</td>
                    <td class="text-right">-</td>
                    <td class="text-right">Rp {{ number_format($booking->tax_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if($showRoom && $booking->discount_amount > 0)
                <tr style="color: #0ab39c;">
                    <td>Discount @if($booking->voucher_code)({{ $booking->voucher_code }})@endif</td>
                    <td class="text-right">-</td>
                    <td class="text-right">- Rp {{ number_format($booking->discount_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        @if($payments->count() > 0)
        <h3 style="font-size: 14px; color: #405189; margin-bottom: 10px; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 5px;">Payment History:</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Account</th>
                    <th class="text-right">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                <tr>
                    <td>{{ $p->created_at->format('d M Y, H:i') }}</td>
                    <td>{{ ucfirst($p->payment_method) }}</td>
                    <td>{{ $p->bankAccount->name ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="totals">
            <div class="totals-row">
                <span>Subtotal Charges:</span>
                <span>Rp {{ number_format($totalCharges, 0, ',', '.') }}</span>
            </div>
            @if(($booking->deposit_amount ?? 0) > 0)
            <div class="totals-row">
                <span>Security Deposit (Refundable):</span>
                <span>Rp {{ number_format($booking->deposit_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="totals-row">
                <span>Total Paid:</span>
                <span>Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
            </div>
            <div class="totals-row grand-total">
                <span>Grand Total:</span>
                <span>Rp {{ number_format($totalCharges, 0, ',', '.') }}</span>
            </div>
            @if($balance > 0)
            <div class="totals-row balance">
                <span>Remaining Balance:</span>
                <span>Rp {{ number_format($balance, 0, ',', '.') }}</span>
            </div>
            @endif
        </div>

        <div class="footer">
            <p>Thank you for staying with us!</p>
            <p>{{ $booking->hotel->name ?? 'Simpang Homestay & Kozz' }}</p>
        </div>
    </div>
</body>
</html>
