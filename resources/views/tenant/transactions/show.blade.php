@extends('layouts.master')
@section('title')
    {{ $transaction->transaction_number }}
@endsection
@section('css')
<style>
    @media print {
        body * { visibility: hidden !important; }
        #printReceipt, #printReceipt * { visibility: visible !important; }
        #printReceipt { position: absolute; left: 0; top: 0; width: 58mm; font-size: 11px; font-family: 'Courier New', monospace; }
        .no-print { display: none !important; }
        @page { size: 58mm auto; margin: 2mm; }
    }
    #printReceipt { display: none; }
    @media print { #printReceipt { display: block !important; } }
</style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            <a href="{{ route('tenant.transactions.index') }}">Transaksi</a>
        @endslot
        @slot('title')
            Detail Transaksi
        @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">{{ $transaction->transaction_number }}</h5>
                        <p class="text-muted mb-0 fs-12">
                            {{ $transaction->tenant->name ?? '' }} — {{ $transaction->created_at->format('d M Y, H:i') }}
                        </p>
                    </div>
                    <div class="flex-shrink-0 d-flex gap-2 align-items-center">
                        @if($transaction->status === 'paid')
                            <span class="badge bg-success fs-12 px-3 py-2">Paid</span>
                        @elseif($transaction->status === 'cancelled')
                            <span class="badge bg-danger fs-12 px-3 py-2">Cancelled</span>
                        @else
                            <span class="badge bg-warning fs-12 px-3 py-2">Unpaid</span>
                        @endif
                        <span class="badge bg-{{ $transaction->payment_method === 'cash' ? 'success' : ($transaction->payment_method === 'qris' ? 'info' : 'primary') }} fs-12 px-3 py-2">
                            <i class="ri-{{ $transaction->payment_method === 'cash' ? 'money-dollar-circle' : ($transaction->payment_method === 'qris' ? 'qr-code' : 'bank-card') }}-line me-1"></i>
                            {{ strtoupper($transaction->payment_method) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Items Table --}}
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end pe-3">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transaction->items as $item)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $item->product_name }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end text-muted">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="text-end pe-3 fw-semibold">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                </tr>
                                @if($item->addons_total > 0 && $item->addons_json)
                                    @foreach(json_decode($item->addons_json, true) as $addon)
                                    <tr>
                                        <td class="ps-5 text-muted fs-12">+ {{ $addon['name'] }}</td>
                                        <td></td>
                                        <td class="text-end text-muted fs-12">Rp {{ number_format($addon['price'], 0, ',', '.') }}</td>
                                        <td class="text-end pe-3 text-muted fs-12">Rp {{ number_format($addon['price'] * $item->quantity, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Total --}}
                    <div class="border-top mt-3 pt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted">{{ $transaction->items_count }} item</span>
                                @if($transaction->notes)
                                    <span class="text-muted ms-3"><i class="ri-chat-3-line me-1"></i>{{ $transaction->notes }}</span>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="text-muted fs-13">Total</span>
                                <h3 class="mb-0 text-primary">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Dibuat oleh: {{ $transaction->creator->name ?? '-' }}</small>
                        <div class="d-flex gap-2">
                            <a href="{{ route('tenant.transactions.index') }}" class="btn btn-soft-secondary btn-sm">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            <button type="button" class="btn btn-soft-info btn-sm" onclick="window.print()">
                                <i class="ri-printer-line me-1"></i> Print Nota
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" id="btnBtShow" onclick="printShowBluetooth()">
                                <i class="ri-bluetooth-line me-1"></i> Bluetooth
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden Print Receipt (58mm thermal) --}}
    <div id="printReceipt">
        <div style="text-align: center; margin-bottom: 8px;">
            <strong style="font-size: 14px;">{{ $transaction->tenant->name ?? 'TENANT' }}</strong><br>
            <span style="font-size: 10px;">{{ $transaction->tenant->location_description ?? '' }}</span><br>
            <span style="font-size: 10px;">{{ $transaction->tenant->phone ?? '' }}</span>
        </div>
        <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
        <div style="font-size: 10px;">
            No: {{ $transaction->transaction_number }}<br>
            {{ $transaction->created_at->format('d/m/Y H:i') }}<br>
            Kasir: {{ $transaction->creator->name ?? '-' }}
        </div>
        <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
        <table style="width: 100%; font-size: 11px;">
            @foreach($transaction->items as $item)
            <tr>
                <td colspan="2">{{ $item->product_name }}</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;{{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($item->addons_total > 0 && $item->addons_json)
                @foreach(json_decode($item->addons_json, true) as $addon)
                <tr>
                    <td style="padding-left: 16px; font-size: 10px;">+ {{ $addon['name'] }}</td>
                    <td style="text-align: right; font-size: 10px;">{{ number_format($addon['price'] * $item->quantity, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @endif
            @endforeach
        </table>
        <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
        <table style="width: 100%; font-size: 12px;">
            <tr>
                <td><strong>TOTAL</strong></td>
                <td style="text-align: right;"><strong>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td>Bayar ({{ strtoupper($transaction->payment_method) }})</td>
                <td style="text-align: right;">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
            </tr>
        </table>
        <div style="border-top: 1px dashed #000; margin: 4px 0;"></div>
        <div style="text-align: center; font-size: 10px; margin-top: 8px;">
            Terima Kasih<br>
            Selamat Datang Kembali
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/thermal-printer.js') }}?v={{ filemtime(public_path('js/thermal-printer.js')) }}"></script>
    <script>
        function printShowBluetooth() {
            if (!ThermalPrinter.isSupported()) {
                alert('Web Bluetooth hanya tersedia di Chrome/Edge desktop atau Android.');
                return;
            }
            const btn = document.getElementById('btnBtShow');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mencari...';

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
                    btn.innerHTML = '<i class="ri-bluetooth-line me-1"></i> ' + msg;
                }
            }).finally(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-bluetooth-line me-1"></i> Bluetooth';
            });
        }
    </script>
@endsection
