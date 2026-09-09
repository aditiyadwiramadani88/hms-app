@php
    $firstDay = \Carbon\Carbon::create($year, $month, 1)->dayOfWeek;
    $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;
    $monthName = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
@endphp
<div class="calendar-header">
    <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
</div>
<div class="calendar-grid">
    @for($i = 0; $i < $firstDay; $i++)
        <div class="calendar-day disabled"></div>
    @endfor
    @for($d = 1; $d <= $daysInMonth; $d++)
        @php
            $dateStr = \Carbon\Carbon::create($year, $month, $d)->format('Y-m-d');
            $att = $attendances[$dateStr] ?? null;
            $colorClass = 'bg-light';
            if ($att) {
                $colorClass = match($att->status) {
                    'present' => 'bg-success',
                    'late' => 'bg-warning',
                    'absent' => 'bg-danger',
                    'late_and_early_leave' => 'bg-danger',
                    'early_leave' => 'bg-info',
                    default => 'bg-light',
                };
            }
        @endphp
        <div class="calendar-day {{ $colorClass }}">{{ $d }}</div>
    @endfor
</div>
<div class="row g-2 mt-3">
    <div class="col-3 summary-stat"><div class="value text-success">{{ $summary['total_present'] }}</div><div class="label">Hadir</div></div>
    <div class="col-3 summary-stat"><div class="value text-warning">{{ $summary['total_late'] }}</div><div class="label">Telat</div></div>
    <div class="col-3 summary-stat"><div class="value text-danger">{{ $summary['total_absent'] }}</div><div class="label">Absen</div></div>
    <div class="col-3 summary-stat"><div class="value text-info">{{ $summary['total_overtime_hours'] }}</div><div class="label">Lembur</div></div>
</div>
