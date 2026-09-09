<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px solid #f4f4f4; padding-bottom: 10px; }
        .receipt { margin: 20px 0; background: #f9f9f9; padding: 15px; border-radius: 5px; }
        .footer { margin-top: 30px; font-size: 0.8em; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Receipt</h1>
            <h2>{{ $booking->hotel->name ?? 'Our Homestay' }}</h2>
        </div>
        <div class="content">
            <p>Dear {{ $booking->guest->name }},</p>
            <p>We have successfully received your payment for Booking #{{ $booking->id }}.</p>
            
            <div class="receipt">
                <h3>Payment Details:</h3>
                <p><strong>Amount Paid:</strong> Rp {{ number_format($transaction->amount, 0, ',', '.') }}</p>
                <p><strong>Payment Method:</strong> {{ ucfirst($transaction->payment_method) }}</p>
                <p><strong>Date & Time:</strong> {{ $transaction->created_at->format('d M Y, H:i') }}</p>
                <p><strong>Description:</strong> {{ $transaction->description ?? 'Booking Payment' }}</p>
            </div>

            <p>Thank you for your payment!</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $booking->hotel->name ?? 'Our Homestay' }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
