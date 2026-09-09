<?php

namespace App\Services;

use App\Models\MaintenanceLog;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;

class RoomService
{
    /**
     * Valid room statuses.
     */
    public const STATUSES = [
        "Available",
        "In-House",
        "Checkin",
        "Checkout",
        "Room Refresh",
        "dirty",
        "Out of Order",
        "maintenance",
    ];

    /**
     * Update room status and log to audit.
     *
     * @throws \Exception
     */
    public function updateRoomStatus(
        Room $room,
        string $status,
        ?string $reason = null,
        ?int $assignedStaffId = null,
    ): Room {
        if (!in_array($status, self::STATUSES)) {
            throw new \Exception("Invalid room status: {$status}");
        }

        $previousStatus = $room->status;

        $updateData = [
            "status" => $status,
        ];

        if ($assignedStaffId !== null) {
            $updateData["assigned_staff_id"] = $assignedStaffId;
        }

        $room->update($updateData);

        \App\Models\AuditLog::log(
            "room.status_changed",
            "Room {$room->room_number} status changed: {$previousStatus} -> {$status}" .
                ($reason ? ". Reason: {$reason}" : "") .
                ($assignedStaffId ? ". Staff assigned." : ""),
            $room,
        );

        return $room->refresh();
    }

    /**
     * Get all rooms grouped by status with counts.
     *
     * @return array {
     *               rooms: Collection,
     *               summary: array {available: int, occupied: int, dirty: int, cleaning: int, out_of_order: int, maintenance: int},
     *               total: int,
     *               occupancy_rate: float
     *               }
     */
    public function getRoomStatuses(): array
    {
        $today = date("Y-m-d");
        $rooms = Room::with([
            "roomType",
            "bookings" => function ($q) use ($today) {
                $q->whereIn("status", ["confirmed", "pending"])
                    ->whereDate("check_in", "<=", $today)
                    ->whereDate("check_out", ">", $today);
            },
            "latestCleaningTask.assignedUser",
        ])->get();

        $rooms->each(function ($room) {
            $room->is_reserved_today = $room->bookings->isNotEmpty();
        });

        $summary = [];
        foreach (self::STATUSES as $status) {
            $summary[$status] = $rooms->where("status", $status)->count();
        }

        $total = $rooms->count();
        $occupied = ($summary["In-House"] ?? 0) + ($summary["Checkin"] ?? 0);
        $available = $summary["Available"] ?? 0;

        // Occupancy rate based on available + occupied (excluding out_of_order and maintenance)
        $operationalRooms =
            $total -
            ($summary["Out of Order"] ?? 0) -
            ($summary["maintenance"] ?? 0);
        $occupancyRate =
            $operationalRooms > 0
                ? round(($occupied / $operationalRooms) * 100, 2)
                : 0;

        $categorizedStatuses = [
            "Available", "Checkout", "dirty", "Room Refresh",
            "In-House", "Checkin", "Out of Order", "maintenance",
        ];

        return [
            "rooms" => $rooms,
            "summary" => $summary,
            "total" => $total,
            "occupancy_rate" => $occupancyRate,
            "cleanRooms" => $rooms->where("status", "Available"),
            "dirtyRooms" => $rooms->whereIn("status", ["Checkout", "dirty"]),
            "cleaningRooms" => $rooms->where("status", "Room Refresh"),
            "occupiedRooms" => $rooms->whereIn("status", ["In-House", "Checkin"]),
            "maintenanceRooms" => $rooms->whereIn("status", [
                "Out of Order",
                "maintenance",
            ]),
            "outOfOrderRooms" => $rooms->where("status", "Out of Order"),
            "otherRooms" => $rooms->whereNotIn("status", $categorizedStatuses),
            "allRooms" => $rooms,
        ];
    }

    /**
     * Mark a room as dirty (typically called after checkout).
     */
    public function markRoomDirty(Room $room): Room
    {
        if ($room->status === "In-House") {
            return $this->updateRoomStatus(
                $room,
                "Checkout",
                "Auto-marked checkout after guest leaves",
            );
        }

        return $room;
    }

    /**
     * Mark a room as clean (called by housekeeping after cleaning).
     *
     * @throws \Exception
     */
    public function markRoomClean(Room $room): Room
    {
        if (!in_array($room->status, ["Checkout", "Room Refresh"])) {
            throw new \Exception(
                "Room {$room->room_number} is not checkout or room refresh. Current status: {$room->status}",
            );
        }

        return $this->updateRoomStatus(
            $room,
            "Available",
            "Room cleaned and ready",
        );
    }

    /**
     * Toggle room maintenance status.
     */
    public function toggleRoomMaintenance(
        Room $room,
        bool $isMaintenance,
        ?string $reason = null,
    ): Room {
        if ($isMaintenance) {
            $targetStatus = "maintenance";
            $reasonText = $reason ?: "Room placed under maintenance";
        } else {
            $targetStatus = "Available";
            $reasonText = $reason ?: "Maintenance completed";
        }

        $previousStatus = $room->status;

        $room->update([
            "status" => $targetStatus,
        ]);

        \App\Models\AuditLog::log(
            "room.maintenance_toggle",
            "Room {$room->room_number}: {$previousStatus} -> {$targetStatus}. {$reasonText}",
            $room,
        );

        return $room->refresh();
    }

    /**
     * Get dynamic price for a room on a specific date.
     * Applies weekend and holiday surcharges.
     *
     * @param  string|\Carbon\Carbon  $date
     */
    public function getDynamicPrice(Room $room, $date): float
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        $basePrice = $room->price_override ?? $room->roomType->base_price;

        // Weekend surcharge (Saturday = 6, Sunday = 0)
        $dayOfWeek = $date->dayOfWeek;
        $weekendSurcharge = 0;

        if ($dayOfWeek === 5 || $dayOfWeek === 6) {
            // Friday and Saturday
            $weekendSurcharge = $basePrice * 0.15; // 15% weekend surcharge
        } elseif ($dayOfWeek === 0) {
            // Sunday
            $weekendSurcharge = $basePrice * 0.1; // 10% Sunday surcharge
        }

        // Holiday surcharge - check against configured holidays
        $holidaySurcharge = 0;
        $holidays = config("app.holidays", []);

        if (is_array($holidays) && isset($holidays[$date->format("m-d")])) {
            $holidaySurcharge = $basePrice * 0.25; // 25% holiday surcharge
        }

        // Peak season surcharge (December 15 - January 15)
        $peakSeasonSurcharge = 0;
        $monthDay = (int) $date->format("md");
        if ($monthDay >= 1215 || $monthDay <= 115) {
            $peakSeasonSurcharge = $basePrice * 0.2; // 20% peak season surcharge
        }

        return round(
            $basePrice +
                $weekendSurcharge +
                $holidaySurcharge +
                $peakSeasonSurcharge,
            2,
        );
    }

    /**
     * Assign the next available room of a specific type.
     */
    public function assignAvailableRoom(int $roomTypeId): ?Room
    {
        return Room::where("room_type_id", $roomTypeId)
            ->whereIn("status", ["Available", "available"])
            ->first();
    }

    /**
     * Get rooms by floor with their statuses.
     */
    public function getRoomsByFloor(): array
    {
        $rooms = Room::with("roomType")
            ->orderBy("floor")
            ->orderBy("room_number")
            ->get();

        $byFloor = [];
        foreach ($rooms as $room) {
            $floor = $room->floor;
            if (!isset($byFloor[$floor])) {
                $byFloor[$floor] = [
                    "floor" => $floor,
                    "rooms" => [],
                    "available" => 0,
                    "occupied" => 0,
                    "other" => 0,
                ];
            }

            $byFloor[$floor]["rooms"][] = $room;

            if (in_array($room->status, ["Available", "available"])) {
                $byFloor[$floor]["available"]++;
            } elseif ($room->status === "occupied") {
                $byFloor[$floor]["occupied"]++;
            } else {
                $byFloor[$floor]["other"]++;
            }
        }

        return array_values($byFloor);
    }

    /**
     * Log a maintenance issue for a room.
     *
     * @param  string  $priority  low, medium, high, urgent
     */
    public function logMaintenanceIssue(
        Room $room,
        string $issue,
        string $priority = "medium",
    ): MaintenanceLog {
        $log = MaintenanceLog::create([
            "room_id" => $room->id,
            "reported_by" => Auth::id(),
            "issue" => $issue,
            "priority" => $priority,
            "status" => "open",
        ]);

        // Auto-set room to maintenance for urgent issues
        if ($priority === "urgent") {
            $this->toggleRoomMaintenance(
                $room,
                true,
                "Urgent maintenance: {$issue}",
            );
        }

        \App\Models\AuditLog::log(
            "room.maintenance_logged",
            "Maintenance issue logged for room {$room->room_number}: {$issue} (priority: {$priority})",
            $log,
        );

        return $log;
    }

    /**
     * Complete a maintenance log entry.
     */
    public function completeMaintenance(
        MaintenanceLog $log,
        ?string $notes = null,
        ?float $cost = null,
    ): MaintenanceLog {
        $log->update([
            "status" => "completed",
            "completed_at" => now(),
            "notes" => $notes,
            "cost" => $cost,
        ]);

        // Return room to available if it was in maintenance
        if ($log->room->status === "maintenance") {
            $this->toggleRoomMaintenance(
                $log->room,
                false,
                "Maintenance completed",
            );
        }

        \App\Models\AuditLog::log(
            "room.maintenance_completed",
            "Maintenance completed for room {$log->room->room_number}",
            $log,
        );

        return $log->refresh();
    }
}
