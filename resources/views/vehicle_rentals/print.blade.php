<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Sewa Motor - {{ $vehicleRental->vehicle_plate_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0 0 5px 0;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
        }
        .content-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .label {
            width: 180px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .colon {
            width: 10px;
        }
        .value {
            border-bottom: 1px dotted #000;
            text-transform: uppercase;
        }
        .signature-section {
            margin-top: 50px;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            text-align: center;
            margin-top: 20px;
        }
        .signature-table td {
            width: 33.33%;
            padding-bottom: 80px;
            font-weight: bold;
        }
        .signature-line {
            display: inline-block;
            width: 80%;
            border-bottom: 1px solid #000;
        }
        .flex-row {
            display: flex;
            justify-content: space-between;
        }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h1>SIMPANG HOMESTAY</h1>
        <p>JL.SIMPANG BOROBUDUR NO.41 MALANG</p>
        <p>TELP.0341-474970 &nbsp;&nbsp;&nbsp; WA. 0852-8182-3535</p>
    </div>

    <table class="content-table">
        <tr>
            <td class="label">ROOM</td>
            <td class="colon">:</td>
            <td class="value" colspan="4">{{ $vehicleRental->booking->room->room_number ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NAMA PENYEWA</td>
            <td class="colon">:</td>
            <td class="value">{{ $vehicleRental->renter_name }}</td>
            <td class="label" style="width:100px; padding-left:20px;">NO.TELP</td>
            <td class="colon">:</td>
            <td class="value">{{ $vehicleRental->booking->guest->phone ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">NAMA PERUSAHAAN</td>
            <td class="colon">:</td>
            <td class="value">{{ $vehicleRental->company_name ?? '-' }}</td>
            <td class="label" style="width:100px; padding-left:20px;">NO.TELP</td>
            <td class="colon">:</td>
            <td class="value"></td>
        </tr>
        <tr>
            <td class="label">N.I.K</td>
            <td class="colon">:</td>
            <td class="value" colspan="4">{{ $vehicleRental->nik ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NO.SIM</td>
            <td class="colon">:</td>
            <td class="value" colspan="4">{{ $vehicleRental->sim_number ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NO.POL KENDARAAN</td>
            <td class="colon">:</td>
            <td class="value" colspan="4"><strong>{{ $vehicleRental->vehicle_plate_number }}</strong></td>
        </tr>
        <tr>
            <td class="label">KM KELUAR</td>
            <td class="colon">:</td>
            <td class="value">{{ $vehicleRental->start_km ?? '' }}</td>
            <td class="label" style="width:100px; padding-left:20px;">KM KEMBALI</td>
            <td class="colon">:</td>
            <td class="value"></td>
        </tr>
        <tr>
            <td class="label">LAMA SEWA</td>
            <td class="colon">:</td>
            <td class="value" style="border:none;">Rp. {{ number_format($vehicleRental->daily_price, 0, ',', '.') }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; X</td>
            <td class="label" style="width:100px; padding-left:20px;">{{ $vehicleRental->rental_days }} HARI</td>
            <td class="colon">:</td>
            <td class="value">Rp. {{ number_format($vehicleRental->daily_price * $vehicleRental->rental_days, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">DEPOSIT</td>
            <td class="colon">:</td>
            <td class="value" colspan="4">{{ $vehicleRental->deposit_amount > 0 ? 'Rp. ' . number_format($vehicleRental->deposit_amount, 0, ',', '.') : '-' }}</td>
        </tr>
    </table>

    <div class="signature-section">
        <div style="text-align: right; padding-right: 50px; margin-bottom: 20px;">
            MALANG, {{ date('d F Y') }}
        </div>
        
        <table class="signature-table">
            <tr>
                <td>KASIR</td>
                <td>PENGELOLAH</td>
                <td>PENYEWA</td>
            </tr>
            <tr>
                <td>( <span class="signature-line" style="width:150px;"></span> )</td>
                <td>( <span class="signature-line" style="width:150px;"></span> )</td>
                <td>( <span class="signature-line" style="width:150px;"></span> )</td>
            </tr>
        </table>
    </div>

</body>
</html>
