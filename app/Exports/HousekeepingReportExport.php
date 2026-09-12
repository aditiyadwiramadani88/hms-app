<?php

namespace App\Exports;

use App\Models\CleaningTask;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HousekeepingReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected string $month;
    protected ?int $staffId;
    protected int $hotelId;

    private const BONUS_CATEGORY_LABELS = [
        'pk' => 'PK (Perintah Khusus)',
        'sales' => 'Sales',
        'umum' => 'Umum',
        'online' => 'Online',
        'kos' => 'Kost',
        'kosong' => 'Kosong',
    ];

    public function __construct(string $month, ?int $staffId = null)
    {
        $this->month = $month;
        $this->staffId = $staffId;
        $this->hotelId = active_hotel_id();
    }

    public function array(): array
    {
        $monthStart = Carbon::parse($this->month)->startOfMonth();
        $monthEnd = Carbon::parse($this->month)->endOfMonth();

        $tasks = CleaningTask::where('hotel_id', $this->hotelId)
            ->where('status', CleaningTask::STATUS_SELESAI)
            ->whereBetween('completed_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()])
            ->when($this->staffId, fn($q) => $q->where('assigned_to', $this->staffId))
            ->with(['room.roomType', 'assignedUser'])
            ->orderBy('completed_at', 'desc')
            ->get();

        $roomIds = $tasks->pluck('room_id')->filter()->unique();
        $lastBookings = \App\Models\Booking::whereIn('room_id', $roomIds)
            ->where('status', 'checked_out')
            ->with('bookingSource')
            ->orderBy('check_out', 'desc')
            ->get()
            ->keyBy('room_id');

        $workOrders = \App\Models\WorkOrder::where('hotel_id', $this->hotelId)
            ->where('type', 'cleaning')
            ->whereBetween('completed_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()])
            ->get();
        $bonusCategoryByTaskId = [];
        foreach ($workOrders as $wo) {
            if ($wo->notes && preg_match('/Task ID: (\d+)/', $wo->notes, $m)) {
                $bonusCategoryByTaskId[(int) $m[1]] = $wo->bonus_category;
            }
        }

        $rows = [];
        foreach ($tasks as $i => $task) {
            $room = $task->room;
            $booking = $lastBookings->get($task->room_id);
            $source = $booking && $booking->bookingSource ? $booking->bookingSource->name : '-';
            $bonusCategoryKey = $bonusCategoryByTaskId[$task->id] ?? null;
            $rows[] = [
                $i + 1,
                $task->completed_at ? $task->completed_at->format('d/m/Y H:i') : '-',
                $room ? ('Kamar ' . $room->room_number . ($room->roomType ? ' (' . $room->roomType->name . ')' : '')) : '-',
                $task->assignedUser ? $task->assignedUser->name : '-',
                $source,
                self::BONUS_CATEGORY_LABELS[$bonusCategoryKey] ?? 'Belum terklasifikasi',
                'Selesai / Approved',
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'Kamar Yang Dikerjakan', 'Nama Staff', 'Sumber Booking', 'Kategori Bonus', 'Status Verifikasi'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '333333']], 'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]],
        ];
    }
}
