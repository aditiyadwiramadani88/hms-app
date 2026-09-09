<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $transaction->transaction_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; width: 58mm; margin: 0 auto; padding: 4mm; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 4px 0; }
        .header { margin-bottom: 8px; }
        .header h2 { font-size: 14px; margin-bottom: 2px; }
        .header p { font-size: 10px; color: #333; }
        .info { font-size: 10px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        .item-name { font-size: 11px; }
        .item-detail { font-size: 10px; padding-left: 8px; }
        .total-row td { font-size: 13px; font-weight: bold; padding-top: 4px; }
        .footer { text-align: center; font-size: 10px; margin-top: 8px; }
        @media print {
            @page { size: 58mm auto; margin: 2mm; }
            body { width: 58mm; }
            .no-print { display: none !important; }
        }
        .no-print { text-align: center; margin: 20px 0; }
        .no-print button { padding: 8px 16px; font-size: 14px; cursor: pointer; background: #0ab39c; color: white; border: none; border-radius: 4px; margin: 0 4px; }
        .no-print button.secondary { background: #6c757d; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Print</button>
        <button class="secondary" onclick="printBluetooth()" id="btnBluetooth">📱 Bluetooth</button>
        <button class="secondary" onclick="window.close()">✕ Close</button>
        <p id="btStatus" style="font-size:11px; margin-top:8px; color:#666;"></p>
    </div>

    <div class="header center">
        <h2>{{ $tenant->name ?? 'TENANT' }}</h2>
        @if($tenant->location_description ?? false)
        <p>{{ $tenant->location_description }}</p>
        @endif
        @if($tenant->phone ?? false)
        <p>Tel: {{ $tenant->phone }}</p>
        @endif
    </div>

    <div class="divider"></div>

    <div class="info">
        No: {{ $transaction->transaction_number }}<br>
        {{ $transaction->created_at->format('d/m/Y H:i') }}<br>
        Kasir: {{ $transaction->creator->name ?? '-' }}
    </div>

    <div class="divider"></div>

    <table>
        @foreach($transaction->items as $item)
        <tr>
            <td class="item-name" colspan="2">{{ $item->product_name }}</td>
        </tr>
        <tr>
            <td class="item-detail">{{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($item->addons_total > 0 && $item->addons_json)
            @foreach(json_decode($item->addons_json, true) as $addon)
            <tr>
                <td class="item-detail" style="padding-left: 12px;">+ {{ $addon['name'] }}</td>
                <td class="right">{{ number_format($addon['price'] * $item->quantity, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        @endif
        @endforeach
    </table>

    <div class="divider"></div>

    <table>
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="right">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="font-size: 11px;">Bayar ({{ strtoupper($transaction->payment_method) }})</td>
            <td class="right" style="font-size: 11px;">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="footer">
        Terima Kasih<br>
        Selamat Datang Kembali
    </div>

    <script src="{{ asset('js/thermal-printer.js') }}?v={{ filemtime(public_path('js/thermal-printer.js')) }}"></script>
    <script>
        window.onload = function() { window.print(); };

        function printBluetooth() {
            if (!ThermalPrinter.isSupported()) {
                alert('Web Bluetooth hanya tersedia di Chrome/Edge desktop atau Android.\n\nSilakan gunakan tombol Print biasa, atau buka halaman ini di Chrome.');
                return;
            }
            const btn = document.getElementById('btnBluetooth');
            btn.disabled = true;
            btn.textContent = 'Mencari...';

            const receipt = {
                tenant_name: "{{ addslashes($tenant->name ?? 'TENANT') }}",
                tenant_address: "{{ addslashes($tenant->location_description ?? '') }}",
                tenant_phone: "{{ addslashes($tenant->phone ?? '') }}",
                transaction_number: "{{ addslashes($transaction->transaction_number) }}",
                datetime: "{{ addslashes($transaction->created_at->format('d/m/Y H:i')) }}",
                cashier: "{{ addslashes($transaction->creator->name ?? '-') }}",
                payment_method: "{{ addslashes($transaction->payment_method) }}",
                total: "{{ number_format($transaction->total_amount, 0, ',', '.') }}",
                items: {!! json_encode($btItems) !!}
            };

            ThermalPrinter.print(receipt, {
                onStatus: function(msg) {
                    document.getElementById('btStatus').textContent = msg;
                }
            }).finally(function() {
                btn.disabled = false;
                btn.textContent = '📱 Bluetooth';
            });
        }
    </script>
</body>
</html>
