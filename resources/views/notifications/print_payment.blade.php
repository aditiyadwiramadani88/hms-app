<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt - #{{ $transaction->id }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; width: 80mm; margin: 0; padding: 5mm; font-size: 12px; }
        .header { text-align: center; margin-bottom: 5mm; }
        .divider { border-top: 1px dashed #000; margin: 2mm 0; }
        .details { margin-bottom: 5mm; }
        .label { font-weight: bold; }
        .footer { text-align: center; margin-top: 5mm; font-size: 10px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h3>{{ $booking->hotel->name ?? 'Our Homestay' }}</h3>
        <p>{{ $booking->hotel->address ?? '' }}</p>
    </div>
    
    <div class="divider"></div>
    <div class="header">
        <strong>BUKTI PEMBAYARAN</strong>
    </div>
    <div class="divider"></div>

    <div class="details">
        <p><span class="label">Booking ID:</span> #{{ $booking->id }}</p>
        <p><span class="label">Tamu:</span> {{ $booking->guest->name }}</p>
        <p><span class="label">Tanggal:</span> {{ $transaction->created_at->format('d/m/Y H:i') }}</p>
        <p><span class="label">Metode:</span> {{ ucfirst($transaction->payment_method) }}</p>
        <p><span class="label">Keterangan:</span> {{ $transaction->description ?? 'Pembayaran' }}</p>
        <div class="divider"></div>
        <p style="font-size: 14px;"><span class="label">JUMLAH:</span> Rp {{ number_format($transaction->amount, 0, ',', '.') }}</p>
    </div>

    <div class="divider"></div>
    <div class="footer">
        <p>LUNAS</p>
        <p>Terima kasih!</p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()">Print Again</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
