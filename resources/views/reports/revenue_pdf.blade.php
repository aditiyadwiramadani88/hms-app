<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #405189; padding-bottom: 10px; margin-bottom: 20px; }
        .summary-box { width: 100%; margin-bottom: 30px; }
        .summary-card { width: 23%; display: inline-block; padding: 10px; border: 1px solid #eee; text-align: center; }
        .label { font-size: 10px; text-transform: uppercase; color: #777; margin-bottom: 5px; }
        .value { font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f8f9fa; text-align: left; padding: 8px; border: 1px solid #eee; }
        td { padding: 8px; border: 1px solid #eee; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $title }}</h2>
        <p>{{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}</p>
    </div>

    <div class="summary-box">
        <div class="summary-card">
            <div class="label">Room Revenue</div>
            <div class="value">Rp {{ number_format($data['room_revenue']['total'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">POS Revenue</div>
            <div class="value">Rp {{ number_format($data['pos_revenue']['total'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Expenses</div>
            <div class="value">Rp {{ number_format($data['expenses'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card" style="background: #eef1ff;">
            <div class="label">Net Profit</div>
            <div class="value">Rp {{ number_format($data['net_profit'] ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>

    <h3>Revenue Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Category/Source</th>
                <th class="text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="2"><strong>Room Revenue</strong></td></tr>
            @foreach($data['room_revenue']['by_source'] ?? [] as $source => $row)
            <tr>
                <td style="padding-left: 20px;">{{ ucfirst($source) }}</td>
                <td class="text-right">Rp {{ number_format($row['total_revenue'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            
            <tr><td colspan="2"><strong>POS Revenue</strong></td></tr>
            @foreach($data['pos_revenue']['by_method'] ?? [] as $method => $row)
            <tr>
                <td style="padding-left: 20px;">{{ ucfirst($method) }}</td>
                <td class="text-right">Rp {{ number_format($row['total_revenue'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            
            <tr style="background: #f8f9fa;">
                <td><strong>GROSS REVENUE</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($data['gross_revenue'] ?? 0, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <h3 style="margin-top: 30px;">Uang yang Disetor (By Account)</h3>
    <table>
        <thead>
            <tr>
                <th>Account Name</th>
                <th class="text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['payments']['by_account'] ?? [] as $acc)
            <tr>
                <td>{{ $acc->account_name }}</td>
                <td class="text-right">Rp {{ number_format($acc->total_amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="2" style="text-align: center; color: #777;">No payments recorded.</td>
            </tr>
            @endforelse
            <tr style="background: #f8f9fa;">
                <td><strong>TOTAL UANG MASUK RIIL</strong></td>
                <td class="text-right" style="color: green;"><strong>Rp {{ number_format($data['payments']['total'] ?? 0, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>
    <p style="font-size: 9px; color: #777; margin-top: -10px; font-style: italic;">* Menampilkan total uang tunai/transfer yang benar-benar diterima (tidak termasuk angka Mark-up).</p>

    <div class="footer">
        Generated at: {{ \Carbon\Carbon::parse($generated_at)->format('d M Y, H:i') }}
    </div>
</body>
</html>
