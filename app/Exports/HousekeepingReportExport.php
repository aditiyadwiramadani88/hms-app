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
    protected Carbon $periodStart;
    protected Carbon $periodEnd;
    protected ?int $staffId;
    protected ?int $roomId;
    protected int $hotelId;

    private const BONUS_CATEGORY_LABELS = [
        'pk' => 'PK (Perintah Khusus)',
        'sales' => 'Sales',
        'sales_cat' => 'Sales',
        'umum' => 'Umum',
        'online' => 'Online',
        'kos' => 'Kost',
        'kost' => 'Kost',
        'kosong' => 'Kosong',
    ];

    public function __construct($periodStart, $periodEnd = null, ?int $staffId = null, ?int $roomId = null)
    {
        if ($periodStart instanceof Carbon) {
            $this->periodStart = $periodStart;
            $this->periodEnd = ($periodEnd instanceof Carbon) ? $periodEnd : $periodStart->copy()->endOfMonth();
        } else {
            $this->periodStart = Carbon::parse($periodStart)->startOfMonth();
            $this->periodEnd = Carbon::parse($periodStart)->endOfMonth();
            if (is_numeric($periodEnd)) {
                $staffId = (int) $periodEnd;
            }
        }
        $this->staffId = $staffId;
        $this->roomId = $roomId;
        $this->hotelId = active_hotel_id();
    }

    public function array(): array
    {
        $tasks = CleaningTask::where('hotel_id', $this->hotelId)
            ->where('status', CleaningTask::STATUS_SELESAI)
            ->where(function ($q) {
                $q->whereBetween('completed_at', [$this->periodStart->copy()->startOfDay(), $this->periodEnd->copy()->endOfDay()])
                  ->orWhere(function ($q2) {
                      $q2->whereNull('completed_at')
                         ->whereBetween('updated_at', [$this->periodStart->copy()->startOfDay(), $this->periodEnd->copy()->endOfDay()]);
                  });
            })
            ->when($this->staffId, fn($q) => $q->where('assigned_to', $this->staffId))
            ->when($this->roomId, fn($q) => $q->where('room_id', $this->roomId))
            ->with(['room.roomType', 'assignedUser'])
            ->orderByRaw('COALESCE(completed_at, updated_at) desc')
            ->get();

        $roomIds = $tasks->pluck('room_id')->filter()->unique();
        $bookingsByRoom = \App\Models\Booking::whereIn('room_id', $roomIds)
            ->with(['bookingSource', 'guest.guestCategory'])
            ->orderBy('check_out', 'desc')
            ->get()
            ->groupBy('room_id');

        $workOrders = \App\Models\WorkOrder::where('hotel_id', $this->hotelId)
            ->where('type', 'cleaning')
            ->whereBetween('completed_at', [$this->periodStart->copy()->startOfDay(), $this->periodEnd->copy()->endOfDay()])
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
            $roomBookings = $bookingsByRoom->get($task->room_id, collect());
            $booking = $roomBookings->first(function ($b) use ($task) {
                if (!$task->completed_at) return true;
                return $b->check_out <= $task->completed_at || ($b->check_in <= $task->completed_at && $b->check_out >= $task->completed_at);
            }) ?? $roomBookings->first();

            $rawSource = null;
            if ($booking) {
                if ($booking->bookingSource) {
                    $rawSource = $booking->bookingSource->name;
                } elseif ($booking->source) {
                    $rawSource = $booking->source;
                }

                $guestCategoryName = $booking->guest?->guestCategory?->name;
                if ($guestCategoryName && (str_contains(strtolower($guestCategoryName), 'sales') || !empty($booking->guest?->company_name))) {
                    if (!$rawSource || in_array(strtolower(trim($rawSource)), ['walk_in', 'walk in', 'langsung / walk-in', 'umum', ''])) {
                        $rawSource = 'Sales';
                    }
                }

                if ($booking->guest_type === 'sales' && (!$rawSource || in_array(strtolower(trim($rawSource)), ['walk_in', 'walk in', 'langsung / walk-in', 'umum', '']))) {
                    $rawSource = 'Sales';
                }
            }

            if (!$rawSource || in_array(strtolower(trim($rawSource)), ['walk_in', 'walk in', 'langsung / walk-in', ''])) {
                $source = 'UMUM';
            } else {
                $source = strtoupper(trim($rawSource));
            }

            $bonusCategoryKey = $bonusCategoryByTaskId[$task->id] ?? null;
            if (!$bonusCategoryKey || $bonusCategoryKey === 'umum') {
                if ($source === 'SALES' || ($booking && $booking->guest_type === 'sales')) {
                    $bonusCategoryKey = 'sales';
                } elseif ($source === 'KOST' || ($booking && $booking->guest_type === 'kos') || ($booking && in_array($booking->stay_type, ['monthly', 'yearly']))) {
                    $bonusCategoryKey = 'kos';
                } elseif (in_array($source, ['TRAVELOKA', 'TIKET.COM', 'AGODA', 'BOOKING.COM', 'AIRBNB', 'ONLINE']) || ($booking && $booking->guest_type === 'online')) {
                    $bonusCategoryKey = 'online';
                }
            }

            $rows[] = [
                $i + 1,
                $task->completed_at ? $task->completed_at->format('d/m/Y H:i') : '-',
                $room ? ('Kamar ' . $room->room_number . ($room->roomType ? ' (' . $room->roomType->name . ')' : '')) : '-',
                $booking?->guest?->name ?? '-',
                $source,
                $task->assignedUser ? $task->assignedUser->name : '-',
                'Selesai / Approved',
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return ['NO', 'TANGGAL', 'KAMAR', 'NAMA TAMU', 'SUMBER BOOKING', 'NAMA STAFF', 'STATUS VERIFIKASI KAMAR'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '333333']], 'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]],
        ];
    }
}
