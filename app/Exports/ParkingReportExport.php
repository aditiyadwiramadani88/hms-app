<?php

namespace App\Exports;

use App\Models\VehicleLog;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ParkingReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected Carbon $date;
    protected array $data;
    protected array $totals;

    public function __construct(Carbon $date)
    {
        $this->date = $date;
        $this->buildData();
    }

    protected function buildData(): void
    {
        $hotelId = active_hotel_id();

        $logs = VehicleLog::where('hotel_id', $hotelId)
            ->whereDate('time_in', $this->date)
            ->with(['booking.room', 'booking.guest', 'guestVehicle'])
            ->get();

        $grouped = [];
        foreach ($logs as $log) {
            $roomNumber = $log->booking?->room?->room_number ?? $log->booking?->custom_room_name ?? 'TANPA KAMAR';
            $stayType = $log->booking?->stay_type ?? 'daily';
            $guestName = $log->booking?->guest?->name ?? ($log->guestVehicle?->guest?->name ?? $log->driver_name);

            if (!isset($grouped[$roomNumber])) {
                $grouped[$roomNumber] = [
                    'room' => $roomNumber,
                    'guest_name' => $guestName,
                    'mobil_harian' => [],
                    'mobil_kost' => [],
                    'motor_harian' => [],
                    'motor_kost' => [],
                ];
            }

            $category = ($stayType === 'monthly') ? 'kost' : 'harian';
            $type = $log->vehicle_type;
            $grouped[$roomNumber]["{$type}_{$category}"][] = $log->plate_number;
        }

        $this->totals = ['mobil_harian' => 0, 'mobil_kost' => 0, 'motor_harian' => 0, 'motor_kost' => 0];

        $rows = [];
        $no = 0;
        foreach ($grouped as $room => $data) {
            $no++;
            $rowCount = max(1, count($data['mobil_harian']), count($data['mobil_kost']), count($data['motor_harian']), count($data['motor_kost']));

            for ($i = 0; $i < $rowCount; $i++) {
                $rows[] = [
                    $i === 0 ? $no : '',
                    $i === 0 ? $room : '',
                    $i === 0 ? $data['guest_name'] : '',
                    $data['mobil_harian'][$i] ?? '',
                    $data['mobil_kost'][$i] ?? '',
                    $data['motor_harian'][$i] ?? '',
                    $data['motor_kost'][$i] ?? '',
                ];
            }

            $this->totals['mobil_harian'] += count($data['mobil_harian']);
            $this->totals['mobil_kost'] += count($data['mobil_kost']);
            $this->totals['motor_harian'] += count($data['motor_harian']);
            $this->totals['motor_kost'] += count($data['motor_kost']);
        }

        // Total row
        $rows[] = ['', '', 'TOTAL:', $this->totals['mobil_harian'], $this->totals['mobil_kost'], $this->totals['motor_harian'], $this->totals['motor_kost']];

        $this->data = $rows;
    }

    public function array(): array
    {
        $hotel = current_hotel();
        $headerRows = [
            [($hotel->name ?? 'SIMPANG HOMESTAY') . ' - LAPORAN PARKIR HARIAN'],
            [$this->date->translatedFormat('l, d F Y')],
            [],
            ['NO', 'KAMAR', 'NAMA TAMU', 'MOBIL HARIAN', 'MOBIL KOST', 'MOTOR HARIAN', 'MOTOR KOST'],
        ];

        return array_merge($headerRows, $this->data);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '333333']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Parkir ' . $this->date->format('d-m-Y');
    }
}
