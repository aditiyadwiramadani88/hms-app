<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $booking->booking_number ?? $booking->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #405189;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .hotel-name {
            font-size: 24px;
            font-weight: bold;
            color: #405189;
            margin-bottom: 5px;
        }
        .hotel-details {
            font-size: 11px;
            color: #666;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            font-size: 28px;
            color: #405189;
            margin-bottom: 5px;
        }
        .invoice-number {
            color: #666;
            font-size: 12px;
        }
        .info-section {
            margin-bottom: 25px;
        }
        .info-grid {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .info-block {
            flex: 1;
        }
        .info-block h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #405189;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-block p {
            margin-bottom: 3px;
        }
        .label {
            color: #666;
            font-size: 11px;
        }
        .value {
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-unpaid {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-partial {
            background-color: #fef3c7;
            color: #92400e;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background-color: #405189;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-table {
            width: 250px;
            margin-left: auto;
        }
        .totals-table td {
            padding: 5px 10px;
            border-bottom: none;
        }
        .totals-table tr.total-row td {
            border-top: 2px solid #405189;
            font-size: 14px;
            font-weight: bold;
            padding-top: 10px;
        }
        .payment-history {
            margin-top: 20px;
        }
        .payment-history h3 {
            font-size: 12px;
            color: #405189;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 11px;
        }
        .footer .thank-you {
            font-size: 14px;
            font-weight: bold;
            color: #405189;
            margin-bottom: 5px;
        }
        .notes-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9fafb;
            border-left: 3px solid #405189;
        }
        .notes-section h4 {
            font-size: 11px;
            color: #405189;
            margin-bottom: 5px;
        }
        .notes-section p {
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="header-top">
            <div>
                <div class="hotel-name">{{ config('app.name', 'Hotel Management System') }}</div>
                <div class="hotel-details">
                    {{ config('hotel.address', '123 Hotel Street, City, Country') }}<br>
                    Phone: {{ config('hotel.phone', '+1 234 567 8900') }}<br>
                    Email: {{ config('hotel.email', 'info@hotel.com') }}
                </div>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <div class="invoice-number">
                    Invoice #: <strong>{{ $booking->booking_number ?? 'INV-' . $booking->id }}</strong><br>
                    Date: <strong>{{ now()->format('d M Y') }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Guest & Booking Info --}}
    <div class="info-section">
        <div class="info-grid">
            <div class="info-block">
                <h3>Guest Information</h3>
                <p><span class="label">Name:</span> <span class="value">{{ $booking->guest->name ?? 'N/A' }}</span></p>
                <p><span class="label">Email:</span> {{ $booking->guest->email ?? 'N/A' }}</p>
                <p><span class="label">Phone:</span> {{ $booking->guest->phone ?? 'N/A' }}</p>
                @if($booking->guest->id_number)
                <p><span class="label">ID Number:</span> {{ $booking->guest->id_number }}</p>
                @endif
            </div>
            <div class="info-block">
                <h3>Booking Details</h3>
                <p><span class="label">Room:</span> <span class="value">{{ $booking->room->room_number ?? 'N/A' }}</span></p>
                <p><span class="label">Room Type:</span> {{ $booking->room->roomType->name ?? 'N/A' }}</p>
                <p><span class="label">Check-in:</span> <span class="value">{{ $booking->check_in ? $booking->check_in->format('d M Y') : 'N/A' }}</span></p>
                <p><span class="label">Check-out:</span> <span class="value">{{ $booking->check_out ? $booking->check_out->format('d M Y') : 'N/A' }}</span></p>
                <p><span class="label">Nights:</span> {{ $booking->nights ?? 'N/A' }}</p>
            </div>
            <div class="info-block">
                <h3>Payment Status</h3>
                @if($booking->payment_status === 'paid')
                    <span class="status-badge status-paid">Paid</span>
                @elseif($booking->payment_status === 'unpaid')
                    <span class="status-badge status-unpaid">Unpaid</span>
                @elseif($booking->payment_status === 'partial')
                    <span class="status-badge status-partial">Partial</span>
                @else
                    <span class="status-badge">{{ ucfirst($booking->payment_status ?? 'N/A') }}</span>
                @endif
                <p style="margin-top: 10px;"><span class="label">Booking Status:</span> <span class="value">{{ ucfirst(str_replace('_', ' ', $booking->status ?? 'N/A')) }}</span></p>
            </div>
        </div>
    </div>

    {{-- Pricing Breakdown --}}
    <h3 style="font-size: 12px; color: #405189; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
        Pricing Breakdown
    </h3>
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>Description</th>
                <th class="text-center" style="width: 80px;">Qty</th>
                <th class="text-right" style="width: 100px;">Rate</th>
                <th class="text-right" style="width: 100px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>
                    <strong>Room Charge - {{ $booking->room->room_number ?? 'N/A' }}</strong><br>
                    <span style="color: #666; font-size: 11px;">{{ $booking->room->roomType->name ?? '' }}</span>
                </td>
                <td class="text-center">{{ $booking->nights ?? 0 }}</td>
                <td class="text-right">{{ number_format($booking->room->price_override ?? $booking->room->roomType->base_price ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($booking->subtotal ?? 0, 2) }}</td>
            </tr>
            @if($booking->posCharges ?? 0 > 0)
            <tr>
                <td>2</td>
                <td>
                    <strong>POS Charges</strong><br>
                    <span style="color: #666; font-size: 11px;">Food, beverages, and other services</span>
                </td>
                <td class="text-center">1</td>
                <td class="text-right">{{ number_format($booking->pos_charges ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($booking->pos_charges ?? 0, 2) }}</td>
            </tr>
            @endif
            @if($booking->additional_charges ?? 0 > 0)
            <tr>
                <td>3</td>
                <td>
                    <strong>Additional Charges</strong>
                </td>
                <td class="text-center">1</td>
                <td class="text-right">{{ number_format($booking->additional_charges ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($booking->additional_charges ?? 0, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ number_format($booking->subtotal ?? 0, 2) }}</td>
        </tr>
        @if($booking->discount_amount ?? 0 > 0)
        <tr>
            <td>Discount @if($booking->voucher_code)({{ $booking->voucher_code }})@endif</td>
            <td class="text-right" style="color: #065f46;">- {{ number_format($booking->discount_amount ?? 0, 2) }}</td>
        </tr>
        @endif
        @if($booking->tax_amount ?? 0 > 0)
        <tr>
            <td>Tax</td>
            <td class="text-right">{{ number_format($booking->tax_amount ?? 0, 2) }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>Total Amount</td>
            <td class="text-right">{{ number_format($booking->total_price ?? 0, 2) }}</td>
        </tr>
    </table>

    {{-- Payment History --}}
    @if(count($booking->transactions ?? []) > 0)
    <div class="payment-history">
        <h3>Payment History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($booking->transactions ?? [] as $transaction)
                <tr>
                    <td>{{ $transaction->created_at ? $transaction->created_at->format('d M Y, H:i') : 'N/A' }}</td>
                    <td>{{ ucfirst($transaction->payment_method ?? 'N/A') }}</td>
                    <td>{{ $transaction->reference ?? '-' }}</td>
                    <td class="text-right">{{ number_format($transaction->amount ?? 0, 2) }}</td>
                    <td class="text-center">{{ ucfirst($transaction->status ?? 'N/A') }}</td>
                </tr>
                @endforeach
                <tr style="background-color: #f9fafb;">
                    <td colspan="3" class="text-right"><strong>Total Paid:</strong></td>
                    <td class="text-right" style="color: #065f46;"><strong>{{ number_format($booking->total_paid ?? 0, 2) }}</strong></td>
                    <td></td>
                </tr>
                <tr style="background-color: #f9fafb;">
                    <td colspan="3" class="text-right"><strong>Balance Due:</strong></td>
                    <td class="text-right" style="color: #991b1b;"><strong>{{ number_format(($booking->total_price ?? 0) - ($booking->total_paid ?? 0), 2) }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    {{-- Notes --}}
    @if($booking->special_requests || config('hotel.invoice_notes'))
    <div class="notes-section">
        @if($booking->special_requests)
        <h4>Special Requests</h4>
        <p>{{ $booking->special_requests }}</p>
        @endif
        @if(config('hotel.invoice_notes'))
        <h4>Notes</h4>
        <p>{{ config('hotel.invoice_notes', 'Thank you for staying with us!') }}</p>
        @endif
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="thank-you">Thank you for choosing {{ config('app.name', 'our hotel') }}!</div>
        <p>We hope you enjoyed your stay and look forward to welcoming you again.</p>
        <p style="margin-top: 10px; font-size: 10px; color: #999;">
            This is a computer-generated invoice. No signature required.
        </p>
    </div>
</body>
</html>
