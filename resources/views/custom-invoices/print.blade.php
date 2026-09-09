<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $customInvoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; color: #333; line-height: 1.5; }
        .invoice-container { max-width: 800px; margin: 0 auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #2c3e50; padding-bottom: 20px; margin-bottom: 20px; }
        .company-info h1 { font-size: 22px; color: #2c3e50; margin-bottom: 5px; }
        .company-info p { color: #666; font-size: 12px; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { font-size: 28px; color: #2c3e50; }
        .invoice-title p { color: #666; font-size: 12px; }
        .invoice-meta { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .meta-box { flex: 1; }
        .meta-box h4 { font-size: 11px; text-transform: uppercase; color: #999; margin-bottom: 5px; letter-spacing: 1px; }
        .meta-box p { font-size: 13px; }
        .meta-box strong { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2c3e50; color: #fff; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .totals { margin-top: 20px; display: flex; justify-content: flex-end; }
        .totals table { width: 300px; }
        .totals tr:last-child td { border-top: 2px solid #2c3e50; font-weight: bold; font-size: 15px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #999; font-size: 11px; }
        .badge-status { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-draft { background: #e9ecef; color: #495057; }
        .badge-sent { background: #cce5ff; color: #004085; }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .notes-box { background: #f8f9fa; padding: 12px 15px; border-radius: 6px; margin-top: 15px; }
        .notes-box h5 { font-size: 11px; color: #999; text-transform: uppercase; margin-bottom: 5px; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .invoice-container { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="company-info">
                <h1>{{ $hotel->name ?? 'Hotel Name' }}</h1>
                <p>{{ $hotel->address ?? '' }}</p>
                <p>Phone: {{ $hotel->phone ?? '' }}</p>
            </div>
            <div class="invoice-title">
                <h2>INVOICE</h2>
                <p><strong>{{ $customInvoice->invoice_number }}</strong></p>
                <p>Date: {{ $customInvoice->created_at->format('d M Y') }}</p>
                <p><span class="badge-status badge-{{ $customInvoice->status }}">{{ ucfirst($customInvoice->status) }}</span></p>
            </div>
        </div>

        <div class="invoice-meta">
            <div class="meta-box">
                <h4>Bill To</h4>
                <p><strong>{{ $customInvoice->guest->name ?? '-' }}</strong></p>
                <p>{{ $customInvoice->guest->email ?? '' }}</p>
                <p>{{ $customInvoice->guest->phone ?? '' }}</p>
            </div>
            <div class="meta-box">
                <h4>Source / Agent</h4>
                <p><strong>{{ $customInvoice->source }}</strong></p>
            </div>
            <div class="meta-box">
                <h4>Stay Details</h4>
                <p>Check-in: <strong>{{ \Carbon\Carbon::parse($customInvoice->check_in)->format('d M Y') }}</strong></p>
                <p>Check-out: <strong>{{ \Carbon\Carbon::parse($customInvoice->check_out)->format('d M Y') }}</strong></p>
                <p>Nights: <strong>{{ $customInvoice->nights }}</strong></p>
                <p>Guests: <strong>{{ $customInvoice->adults }} Adults, {{ $customInvoice->children }} Children</strong></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Room / Service</th>
                    <th class="text-right">Nights</th>
                    <th class="text-right">Price/Night</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $customInvoice->room_name }}</td>
                    <td class="text-right">{{ $customInvoice->nights }}</td>
                    <td class="text-right">Rp {{ number_format($customInvoice->sell_price / max(1, $customInvoice->nights), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($customInvoice->sell_price, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">Rp {{ number_format($customInvoice->sell_price, 0, ',', '.') }}</td>
                </tr>
                @if($customInvoice->agent_commission > 0)
                <tr>
                    <td>Agent Commission</td>
                    <td class="text-right">Rp {{ number_format($customInvoice->agent_commission, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td>Total</td>
                    <td class="text-right">Rp {{ number_format($customInvoice->sell_price, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        @if($customInvoice->notes)
        <div class="notes-box">
            <h5>Notes</h5>
            <p>{{ $customInvoice->notes }}</p>
        </div>
        @endif

        <div class="footer">
            <p>This is a computer-generated invoice. Thank you for your business.</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 14px; background: #2c3e50; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
            Print Invoice
        </button>
    </div>
</body>
</html>
