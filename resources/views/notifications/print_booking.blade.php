<!DOCTYPE html>
<html>
<head>
    <title>Booking Confirmation - #{{ $booking->id }}</title>
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
        <p>Tel: {{ $booking->hotel->phone ?? '' }}</p>
    </div>
    
    <div class="divider"></div>
    <div class="header">
        <strong>KONFIRMASI BOOKING</strong>
    </div>
    <div class="divider"></div>

    <div class="details">
        <p><span class="label">ID:</span> #{{ $booking->id }}</p>
        <p><span class="label">Tamu:</span> {{ $booking->guest->name }}</p>
        <p><span class="label">Kamar:</span> {{ $booking->room ? $booking->room->room_number . ' (' . ($booking->room->roomType->name ?? '-') . ')' : ($booking->custom_room_name ?? 'N/A') }}</p>
        <p><span class="label">Check-in:</span> {{ $booking->check_in->format('d/m/Y') }}</p>
        <p><span class="label">Check-out:</span> {{ $booking->check_out->format('d/m/Y') }}</p>
        <p><span class="label">Total:</span> Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
    </div>

    <div class="divider"></div>
    <div class="footer">
        <p>Terima kasih atas kunjungan Anda!</p>
        <p>{{ date('d/m/Y H:i') }}</p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()">Print Again</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
