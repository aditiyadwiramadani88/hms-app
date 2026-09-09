<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px solid #f4f4f4; padding-bottom: 10px; }
        .content { margin: 20px 0; }
        .footer { margin-top: 30px; font-size: 0.8em; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="color: #d9534f;">Booking Cancellation</h1>
            <h2>{{ $booking->hotel->name ?? 'Our Homestay' }}</h2>
        </div>
        <div class="content">
            <p>Dear {{ $booking->guest->name }},</p>
            <p>Your booking with ID #{{ $booking->id }} has been cancelled.</p>
            
            <p><strong>Booking Details:</strong></p>
            <ul>
                <li><strong>Room:</strong> {{ $booking->room->room_number }} ({{ $booking->room->roomType->name }})</li>
                <li><strong>Dates:</strong> {{ $booking->check_in->format('d M Y') }} - {{ $booking->check_out->format('d M Y') }}</li>
            </ul>

            <p>If you have any questions or would like to re-book, please don't hesitate to contact us.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $booking->hotel->name ?? 'Our Homestay' }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
