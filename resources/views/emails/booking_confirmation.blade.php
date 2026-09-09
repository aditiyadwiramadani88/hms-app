<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px solid #f4f4f4; padding-bottom: 10px; }
        .details { margin: 20px 0; }
        .footer { margin-top: 30px; font-size: 0.8em; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
        .button { background: #4a90e2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Booking Confirmation</h1>
            <h2>{{ $booking->hotel->name ?? 'Our Homestay' }}</h2>
        </div>
        <div class="content">
            <p>Dear {{ $booking->guest->name }},</p>
            <p>Thank you for choosing {{ $booking->hotel->name ?? 'Our Homestay' }}. Your booking has been confirmed.</p>
            
            <div class="details">
                <h3>Booking Details:</h3>
                <p><strong>Booking ID:</strong> #{{ $booking->id }}</p>
                <p><strong>Room:</strong> {{ $booking->room->room_number }} ({{ $booking->room->roomType->name }})</p>
                <p><strong>Check-in:</strong> {{ $booking->check_in->format('d M Y') }}</p>
                <p><strong>Check-out:</strong> {{ $booking->check_out->format('d M Y') }}</p>
                <p><strong>Total Price:</strong> Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
            </div>

            <p>We look forward to welcoming you!</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $booking->hotel->name ?? 'Our Homestay' }}. All rights reserved.</p>
            <p>{{ $booking->hotel->address ?? '' }}</p>
        </div>
    </div>
</body>
</html>
