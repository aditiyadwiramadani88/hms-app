<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Absensi</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 0; font-size: 16px; }
        .header p { margin: 3px 0 0 0; color: #555; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px 6px; text-align: left; font-size: 10px; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .badge-success { background: #d4edda; color: #155724; padding: 2px 6px; border-radius: 3px; }
        .badge-warning { background: #fff3cd; color: #856404; padding: 2px 6px; border-radius: 3px; }
        .badge-danger { background: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; }
        .badge-info { background: #d1ecf1; color: #0c5460; padding: 2px 6px; border-radius: 3px; }
        .footer { text-align: right; font-size: 9px; color: #777; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Absensi Karyawan</h2>
        <p>{{ $hotel->name ?? 'Hotel' }}</p>
        <p>Periode: {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : 'Awal' }} - {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : 'Sekarang' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Karyawan</th>
                <th>Tanggal</th>
                <th>Shift</th>
                <th>Check In</th>
                <th>Break</th>
                <th>Check Out</th>
                <th>Status</th>
                <th class="text-center">Telat (mnt)</th>
                <th class="text-center">PA (mnt)</th>
                <th>Lokasi In</th>
                <th>Lokasi Out</th>
            </tr>
        </thead>
        <tbody>
            @php
                $statusMap = ['present' => 'Hadir', 'late' => 'Terlambat', 'early_leave' => 'Pulang Awal', 'late_and_early_leave' => 'Telat & PA', 'absent' => 'Absen'];
                $badgeMap = ['present' => 'success', 'late' => 'warning', 'early_leave' => 'info', 'late_and_early_leave' => 'danger', 'absent' => 'danger'];
            @endphp
            @forelse($attendances as $i => $att)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $att->employee->name ?? '-' }}</td>
                <td>{{ $att->attendance_date->format('d/m/Y') }}</td>
                <td>{{ $att->shift->name ?? '-' }}</td>
                <td>{{ $att->check_in_time?->format('H:i') ?? '-' }}</td>
                <td>
                    @if($att->break_start_time && $att->break_end_time)
                        {{ $att->break_start_time->format('H:i') }}-{{ $att->break_end_time->format('H:i') }}
                    @elseif($att->break_start_time)
                        {{ $att->break_start_time->format('H:i') }}-...
                    @else
                        -
                    @endif
                </td>
                <td>{{ $att->check_out_time?->format('H:i') ?? '-' }}</td>
                <td><span class="badge-{{ $badgeMap[$att->status] ?? 'secondary' }}">{{ $statusMap[$att->status] ?? $att->status }}</span></td>
                <td class="text-center">{{ $att->late_minutes > 0 ? $att->late_minutes : '-' }}</td>
                <td class="text-center">{{ $att->early_leave_minutes > 0 ? $att->early_leave_minutes : '-' }}</td>
                <td>{{ $att->checkInLocation->name ?? '-' }}</td>
                <td>{{ $att->checkOutLocation->name ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="text-center">Tidak ada data absensi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d M Y H:i:s') }} | Total: {{ $attendances->count() }} record
    </div>
</body>
</html>
