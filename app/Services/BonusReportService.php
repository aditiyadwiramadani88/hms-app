<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CleaningTask;
use App\Models\ObBonusSetting;
use App\Models\User;
use Carbon\Carbon;

class BonusReportService
{
    /**
     * Calculate monthly bonus for all active OBs.
     */
    public function calculateMonthlyBonus(Carbon $month): array
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        // Get all OBs who are active (have work orders in this month)
        $activeOBs = $this->getActiveOBsForMonth($startOfMonth, $endOfMonth);

        $bonusReport = [];
        $totalBonusPool = 0;

        foreach ($activeOBs as $ob) {
            $bonusDetail = $this->getOBBonusDetailModel($ob['id'], $startOfMonth, $endOfMonth);
            $bonusDetail['ob_name'] = $ob['name'];
            $bonusReport[] = $bonusDetail;
            $totalBonusPool += $bonusDetail['total_bonus'];
        }

        return [
            'month' => $month->format('Y-m'),
            'active_ob_count' => count($activeOBs),
            'bonus_details' => $bonusReport,
            'total_bonus_pool' => $totalBonusPool,
        ];
    }

    /**
     * Get active OBs for a given month.
     */
    protected function getActiveOBsForMonth(Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $activeIds = CleaningTask::where('status', CleaningTask::STATUS_SELESAI)
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->pluck('assigned_to')
            ->filter()
            ->unique();

        return User::whereIn('id', $activeIds)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'like', '%ob%')
                    ->orWhere('name', 'like', '%cleaner%')
                    ->orWhere('name', 'like', '%housekeeping%');
            })
            ->get()
            ->toArray();
    }

    /**
     * Preload bookings for the rooms touched by a set of cleaning tasks, indexed
     * by room_id, so classifyRoomBonusCategory() can run without N+1 queries.
     */
    protected function preloadBookingsByRoom(\Illuminate\Support\Collection $tasks, Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $roomIds = $tasks->pluck('room_id')->filter()->unique()->values();
        $hotelIds = $tasks->pluck('hotel_id')->filter()->unique()->values();

        if ($roomIds->isEmpty()) {
            return [];
        }

        $bookings = Booking::with('bookingSource')
            ->whereIn('room_id', $roomIds)
            ->whereIn('hotel_id', $hotelIds)
            ->where('check_in', '<=', $endOfMonth)
            ->where('check_out', '>=', $startOfMonth->copy()->subMonth())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get();

        $bookingsByRoom = [];
        foreach ($bookings as $booking) {
            $bookingsByRoom[$booking->room_id][] = $booking;
        }

        return $bookingsByRoom;
    }

    /**
     * Map classifyRoomBonusCategory()'s category names to ObBonusSetting's
     * rate categories (sales_cat/kost -> sales/kos; others pass through).
     */
    protected function toRateCategory(string $category): string
    {
        return match ($category) {
            'sales_cat' => 'sales',
            'kost' => 'kos',
            default => $category, // pk, online, umum, kosong
        };
    }

    /**
     * Bucket an "online" booking into its specific OTA for the detail breakdown.
     * All online bookings still bonus at the single online_rate -- this split
     * is for visibility only (REVISI Bonus Report: online belum dibagi per OTA).
     */
    protected function onlineSourceBucket(?\App\Models\Booking $booking): string
    {
        $sourceName = strtolower(trim($booking?->bookingSource?->name ?? $booking?->source ?? ''));

        return match (true) {
            str_contains($sourceName, 'redd') => 'reddoors',
            str_contains($sourceName, 'traveloka') => 'traveloka',
            str_contains($sourceName, 'agoda') => 'agoda',
            default => 'lainnya',
        };
    }

    /**
     * Get core bonus detail model for an OB.
     *
     * Category is classified live via CleaningTask::classifyRoomBonusCategory()
     * (booking source at task completion time), and the task set itself is
     * read directly from CleaningTask -- same source Performa OB uses -- not
     * from WorkOrder. WorkOrder rows are a secondary mirror kept in sync by a
     * string-matched event hook (CleaningTask::updated(), matching on
     * "Task ID: X" in WorkOrder.notes) that silently drops sync on any status
     * transition it doesn't dirty-check for, so WorkOrder undercounts
     * completed tasks against CleaningTask -- that's why this report used to
     * show fewer/zeroed-out tasks and disagreed with Performa OB.
     */
    protected function getOBBonusDetailModel(int $obId, Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $completedTasks = CleaningTask::where('assigned_to', $obId)
            ->where('status', CleaningTask::STATUS_SELESAI)
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->get();

        $bookingsByRoom = $this->preloadBookingsByRoom($completedTasks, $startOfMonth, $endOfMonth);

        $categories = ['sales', 'umum', 'online', 'kos', 'pk', 'kosong'];
        $counts = array_fill_keys($categories, 0);
        $onlineSources = ['reddoors' => 0, 'traveloka' => 0, 'agoda' => 0, 'lainnya' => 0];

        foreach ($completedTasks as $task) {
            $classified = CleaningTask::classifyRoomBonusCategory($task->room_id, $task->completed_at, $task->hotel_id, $bookingsByRoom);
            $rateCategory = $this->toRateCategory($classified['category']);
            $counts[$rateCategory]++;

            if ($rateCategory === 'online') {
                $onlineSources[$this->onlineSourceBucket($classified['booking'])]++;
            }
        }

        $breakdown = [];
        $totalBonus = 0;

        foreach ($categories as $category) {
            $count = $counts[$category];
            $rate = ObBonusSetting::getBonusRate($category);
            $amount = $count * $rate;

            $breakdown[$category] = [
                'count' => $count,
                'rate' => $rate,
                'amount' => $amount,
            ];

            $totalBonus += $amount;
        }

        $breakdown['online']['sources'] = $onlineSources;

        return [
            'ob_id' => $obId,
            'breakdown' => $breakdown,
            'total_bonus' => $totalBonus,
            'completed_tasks' => $completedTasks->count(),
        ];
    }

    /**
     * Get detailed bonus report for a specific OB in a given month.
     */
    public function getOBBonusDetail(User $ob, Carbon $month): array
    {
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $detail = $this->getOBBonusDetailModel($ob->id, $startOfMonth, $endOfMonth);
        $detail['ob_name'] = $ob->name;
        $detail['month'] = $month->format('Y-m');

        $completedTasks = CleaningTask::where('assigned_to', $ob->id)
            ->where('status', CleaningTask::STATUS_SELESAI)
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->with(['room.roomType'])
            ->get();

        $bookingsByRoom = $this->preloadBookingsByRoom($completedTasks, $startOfMonth, $endOfMonth);

        $detail['task_list'] = $completedTasks->map(function ($task) use ($bookingsByRoom) {
            $classified = CleaningTask::classifyRoomBonusCategory($task->room_id, $task->completed_at, $task->hotel_id, $bookingsByRoom);
            $rateCategory = $this->toRateCategory($classified['category']);
            $displayCategory = $rateCategory === 'online' ? $this->onlineSourceBucket($classified['booking']) : $rateCategory;

            return [
                'work_order_id' => $task->id,
                'room_number' => $task->room->room_number ?? 'N/A',
                'category' => $displayCategory,
                'completed_at' => $task->completed_at?->format('Y-m-d H:i:s'),
                'bonus_amount' => ObBonusSetting::getBonusRate($rateCategory),
                'keterangan' => $task->notes,
            ];
        });

        return $detail;
    }

    /**
     * Export bonus report to array format for Excel/PDF.
     */
    public function exportBonusReport(Carbon $month): array
    {
        $report = $this->calculateMonthlyBonus($month);

        return [
            'header' => [
                'month' => $report['month'],
                'total_bonus_pool' => $report['total_bonus_pool'],
                'active_ob_count' => $report['active_ob_count'],
            ],
            'details' => $report['bonus_details'],
        ];
    }
}
