<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Serah Terima Shift - {{ $handover->handover_date->format('d/m/Y') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 20px 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 2px 5px;
            font-size: 10px;
        }
        .info-table .label {
            font-weight: bold;
            width: 120px;
        }
        table.main {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.main th, table.main td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: center;
            font-size: 9px;
        }
        table.main th {
            background-color: #333;
            color: white;
            font-weight: bold;
        }
        table.main td.text-right {
            text-align: right;
        }
        table.main td.text-left {
            text-align: left;
        }
        .summary-table {
            width: 300px;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .summary-table td {
            padding: 4px 8px;
            border: 1px solid #333;
            font-size: 10px;
        }
        .summary-table .label {
            font-weight: bold;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            padding-bottom: 3px;
            border-bottom: 2px solid #333;
        }
        .notes {
            margin-top: 10px;
            padding: 8px;
            border: 1px solid #ccc;
            font-size: 10px;
            min-height: 40px;
        }
        .footer {
            margin-top: 30px;
            font-size: 8px;
            text-align: right;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $hotel->name ?? 'SIMPANG HOMESTAY & KOZZ' }}</h1>
        <h2>SERAH TERIMA SHIFT</h2>
    </div>

    <table class="info-table">
        <tr><td class="label">Tanggal</td><td>: {{ $handover->handover_date->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Shift Keluar</td><td>: {{ $handover->outgoingShift->name ?? '-' }} ({{ $handover->outgoingShift ? \Carbon\Carbon::parse($handover->outgoingShift->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($handover->outgoingShift->end_time)->format('H:i') : '' }})</td></tr>
        <tr><td class="label">Shift Masuk</td><td>: {{ $handover->incomingShift->name ?? '-' }} ({{ $handover->incomingShift ? \Carbon\Carbon::parse($handover->incomingShift->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($handover->incomingShift->end_time)->format('H:i') : '' }})</td></tr>
        <tr><td class="label">Petugas Keluar</td><td>: {{ $handover->outgoingEmployee->name ?? '-' }}</td></tr>
        <tr><td class="label">Petugas Masuk</td><td>: {{ $handover->incomingEmployee->name ?? '-' }}</td></tr>
        <tr><td class="label">Status</td><td>: {{ strtoupper($handover->status) }}</td></tr>
        @if($handover->submitted_at)
        <tr><td class="label">Waktu Submit</td><td>: {{ $handover->submitted_at->format('d/m/Y H:i') }}</td></tr>
        @endif
        @if($handover->confirmed_at)
        <tr><td class="label">Waktu Konfirmasi</td><td>: {{ $handover->confirmed_at->format('d/m/Y H:i') }}</td></tr>
        @endif
    </table>

    <div class="section-title">RINGKASAN KEUANGAN</div>
    <table class="summary-table">
        <tr><td class="label">Pemasukan Room</td><td class="text-right">{{ number_format($handover->financial_summary['pemasukan_room'] ?? 0, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Pemasukan Lain</td><td class="text-right">{{ number_format($handover->financial_summary['pemasukan_lain'] ?? 0, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Total Pemasukan</td><td class="text-right">{{ number_format($handover->financial_summary['total_income'] ?? 0, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Pengeluaran</td><td class="text-right">{{ number_format($handover->financial_summary['pengeluaran'] ?? 0, 0, ',', '.') }}</td></tr>
        <tr style="font-weight: bold; background: #f0f0f0;"><td class="label">Sisa Kas (Net Balance)</td><td class="text-right">{{ number_format($handover->financial_summary['net_balance'] ?? 0, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Kas Fisik Diserahkan</td><td class="text-right">{{ $handover->cash_amount ? number_format($handover->cash_amount, 0, ',', '.') : '-' }}</td></tr>
        @if($handover->cash_discrepancy)
        <tr><td class="label">Selisih Kas</td><td class="text-right text-danger">{{ number_format(($handover->cash_amount ?? 0) - ($handover->financial_summary['net_balance'] ?? 0), 0, ',', '.') }}</td></tr>
        @endif
    </table>

    <div class="section-title">CHECKLIST SERAH TERIMA</div>
    <table class="main">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Item</th>
                <th style="width: 80px;">Status</th>
                <th style="width: 100px;">Nilai</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($handover->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="text-left">{{ $item->item_name }}</td>
                <td>{{ $item->is_checked ? 'CHECKED' : 'UNCHECKED' }}</td>
                <td>{{ $item->value ?? '-' }}</td>
                <td class="text-left">{{ $item->notes ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align: center;">Tidak ada item checklist</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($handover->notes)
    <div class="section-title">CATATAN PETUGAS KELUAR</div>
    <div class="notes">{{ $handover->notes }}</div>
    @endif

    @if($handover->incoming_notes)
    <div class="section-title">CATATAN PETUGAS MASUK</div>
    <div class="notes">{{ $handover->incoming_notes }}</div>
    @endif

    @if($handover->discrepancy_notes)
    <div class="section-title">CATATAN SELISIH KAS</div>
    <div class="notes">{{ $handover->discrepancy_notes }}</div>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
