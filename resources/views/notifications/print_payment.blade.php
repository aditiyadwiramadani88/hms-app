<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt - #{{ $transaction->id }}</title>
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
        .label { font-weight: bold; }
        .row-item { display: flex; justify-content: space-between; align-items: flex-start; margin: 1mm 0; font-size: 11px; }
        .row-item .val { text-align: right; white-space: nowrap; margin-left: 4px; }
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
        </div>
        
        <div class="divider"></div>
        <div class="header" style="margin-bottom: 1mm;">
            <strong>BUKTI PEMBAYARAN</strong>
        </div>
        <div class="divider"></div>

        <div class="details">
            <p class="info-line"><span class="label">Booking ID:</span> #{{ $booking->id }}</p>
            <p class="info-line"><span class="label">Tamu:</span> {{ $booking->guest->name }}</p>
            <p class="info-line"><span class="label">Kamar:</span> {{ $booking->room ? $booking->room->room_number . ' (' . ($booking->room->roomType->name ?? '-') . ')' : ($booking->custom_room_name ?? 'N/A') }}</p>
            <p class="info-line"><span class="label">Tanggal:</span> {{ $transaction->created_at->format('d/m/Y H:i') }}</p>
            <p class="info-line"><span class="label">Metode:</span> {{ ucfirst($transaction->payment_method) }}</p>
            <p class="info-line"><span class="label">Keterangan:</span> {{ $transaction->description ?? 'Pembayaran' }}</p>
            
            <div class="divider"></div>
            
            <div class="row-item" style="font-size: 12px; font-weight: bold;">
                <span>DIBAYAR:</span>
                <span class="val">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
            </div>
            
            <div class="divider"></div>

            <div class="row-item">
                <span class="label">Total Tagihan:</span>
                <span class="val">Rp {{ number_format($grandTotal ?? $booking->total_price, 0, ',', '.') }}</span>
            </div>
            <div class="row-item">
                <span class="label">Total Terbayar:</span>
                <span class="val">Rp {{ number_format($totalPaid ?? $transaction->amount, 0, ',', '.') }}</span>
            </div>
            <div class="row-item">
                <span class="label">Sisa Tagihan:</span>
                <span class="val">Rp {{ number_format($remainingBalance ?? 0, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="divider"></div>
        <div class="footer">
            @if(($remainingBalance ?? 0) <= 0)
                <p style="font-weight: bold; font-size: 13px; margin: 2px 0;">*** LUNAS ***</p>
            @else
                <p style="font-weight: bold; font-size: 12px; margin: 2px 0;">*** BELUM LUNAS ***</p>
                <p style="font-size: 10px; margin: 1px 0;">Sisa: Rp {{ number_format($remainingBalance, 0, ',', '.') }}</p>
            @endif
            <p style="margin-top: 3px;">Terima kasih atas pembayaran Anda!</p>
        </div>
    </div>

    <div class="no-print" style="margin-top: 15px; text-align: center;">
        <button onclick="window.print()">Print Again</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
