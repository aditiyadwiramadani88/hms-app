<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected ?int $employeeId;
    protected ?string $dateFrom;
    protected ?string $dateTo;
    protected ?string $status;
    protected ?int $shiftId;
    protected int $hotelId;

    public function __construct(?int $employeeId, ?string $dateFrom, ?string $dateTo, ?string $status, ?int $shiftId)
    {
        $this->employeeId = $employeeId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
        $this->shiftId = $shiftId;
        $this->hotelId = active_hotel_id();
    }

    public function query()
    {
        $query = Attendance::query()
            ->where('hotel_id', $this->hotelId)
            ->with(['employee', 'shift', 'checkInLocation', 'checkOutLocation']);

        if ($this->employeeId) {
            $query->where('employee_id', $this->employeeId);
        }
        if ($this->dateFrom) {
            $query->where('attendance_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('attendance_date', '<=', $this->dateTo);
        }
        if ($this->status) {
            $statuses = explode(',', $this->status);
            $query->whereIn('status', $statuses);
        }
        if ($this->shiftId) {
            $query->where('shift_id', $this->shiftId);
        }

        return $query->orderBy('attendance_date', 'desc')->orderBy('employee_id');
    }

    public function headings(): array
    {
        return [
            'Nama Karyawan',
            'Tanggal',
            'Shift',
            'Check In',
            'Break Start',
            'Break End',
            'Check Out',
            'Status',
            'Terlambat (menit)',
            'Pulang Awal (menit)',
            'Overtime (menit)',
            'Lokasi Check-In',
            'Lokasi Check-Out',
        ];
    }

    public function map($attendance): array
    {
        $statusLabels = [
            'present' => 'Hadir',
            'late' => 'Terlambat',
            'early_leave' => 'Pulang Awal',
            'late_and_early_leave' => 'Terlambat & Pulang Awal',
            'absent' => 'Absen',
        ];

        return [
            $attendance->employee->name ?? '-',
            $attendance->attendance_date->format('d/m/Y'),
            $attendance->shift->name ?? '-',
            $attendance->check_in_time?->format('H:i') ?? '-',
            $attendance->break_start_time?->format('H:i') ?? '-',
            $attendance->break_end_time?->format('H:i') ?? '-',
            $attendance->check_out_time?->format('H:i') ?? '-',
            $statusLabels[$attendance->status] ?? $attendance->status,
            $attendance->late_minutes > 0 ? $attendance->late_minutes : '',
            $attendance->early_leave_minutes > 0 ? $attendance->early_leave_minutes : '',
            $attendance->overtime_minutes > 0 ? $attendance->overtime_minutes : '',
            $attendance->checkInLocation->name ?? '-',
            $attendance->checkOutLocation->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
            ],
        ];
    }
}
