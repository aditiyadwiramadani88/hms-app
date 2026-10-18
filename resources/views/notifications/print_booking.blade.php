<!DOCTYPE html>
<html>
<head>
    <title>Booking Confirmation - #{{ $booking->id }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            width: 80mm; 
            max-width: 100%; 
            margin: 0 auto; 
            padding: 3mm 4mm; 
            font-size: 11px; 
            line-height: 1.3; 
            color: #000; 
            background: #fff; 
        }
        .header { text-align: center; margin-bottom: 2mm; }
        .header h3 { font-size: 13px; font-weight: bold; margin-bottom: 1mm; }
        .header p { font-size: 10px; margin: 0.5mm 0; }
        .divider { border-top: 1px dashed #000; margin: 2mm 0; }
        .details { margin-bottom: 2mm; }
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
    <div class="receipt-box">
        <div class="header">
            <h3>{{ $booking->hotel->name ?? 'Our Homestay' }}</h3>
            <p>{{ $booking->hotel->address ?? '' }}</p>
            <p>Tel: {{ $booking->hotel->phone ?? '' }}</p>
        </div>
        
        <div class="divider"></div>
        <div class="header">
            <strong>KONFIRMASI BOOKING</strong>
        </div>
        <div class="divider"></div>

        <div class="details">
            <p class="info-line"><span class="label">ID:</span> #{{ $booking->id }}</p>
            <p class="info-line"><span class="label">Tamu:</span> {{ $booking->guest->name }}</p>
            <p class="info-line"><span class="label">Kamar:</span> {{ $booking->room ? $booking->room->room_number . ' (' . ($booking->room->roomType->name ?? '-') . ')' : ($booking->custom_room_name ?? 'N/A') }}</p>
            <p class="info-line"><span class="label">Check-in:</span> {{ $booking->check_in->format('d/m/Y') }}</p>
            <p class="info-line"><span class="label">Check-out:</span> {{ $booking->check_out->format('d/m/Y') }}</p>
            
            <div class="divider"></div>

            @php
                $hasExtra = (($manualExtraTotal ?? 0) > 0) || (($posTotal ?? 0) > 0);
                $finalTotal = $grandTotal ?? ($booking->total_price + ($manualExtraTotal ?? 0) + ($posTotal ?? 0));
                $paid = $totalPaid ?? ($booking->transactions ? $booking->transactions->where('type', 'payment')->where('status', 'success')->sum('amount') : 0);
                $remaining = $remainingBalance ?? max(0, $finalTotal - $paid);
            @endphp

            @if($hasExtra)
                <p class="info-line"><span class="label">Sewa Kamar:</span> Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                @if(($manualExtraTotal ?? 0) > 0)
                <p class="info-line"><span class="label">Biaya Tambahan:</span> Rp {{ number_format($manualExtraTotal, 0, ',', '.') }}</p>
                @endif
                @if(($posTotal ?? 0) > 0)
                <p class="info-line"><span class="label">Pesanan POS:</span> Rp {{ number_format($posTotal, 0, ',', '.') }}</p>
                @endif
                <div class="divider"></div>
                <p class="info-line" style="font-weight: bold;"><span class="label">TOTAL:</span> Rp {{ number_format($finalTotal, 0, ',', '.') }}</p>
            @else
                <p class="info-line"><span class="label">Total Sewa:</span> Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
            @endif

            @php
                $paymentList = $booking->transactions
                    ? $booking->transactions->where('type', 'payment')->where('status', 'success')
                    : collect();
            @endphp

            @if($paymentList->isNotEmpty())
                <div class="divider"></div>
                @foreach($paymentList as $pay)
                    @php
                        $payDesc = !empty(trim($pay->description ?? '')) && !in_array(strtolower(trim($pay->description)), ['booking payment', 'pembayaran'])
                            ? $pay->description
                            : 'Sudah Dibayar';
                    @endphp
                    <p class="info-line">
                        <span class="label">{{ $payDesc }}:</span> Rp {{ number_format($pay->amount, 0, ',', '.') }}
                    </p>
                @endforeach
                <p class="info-line"><span class="label">Sisa Pembayaran:</span> Rp {{ number_format($remaining, 0, ',', '.') }}</p>
            @endif
        </div>

        <div class="divider"></div>
        <div class="footer">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p>{{ date('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div class="no-print" style="margin-top: 15px; text-align: center;">
        <button onclick="window.print()">Print Again</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
