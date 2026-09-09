<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CleaningTask extends Model
{
    use BelongsToHotel, HasFactory;

    protected $fillable = [
        "hotel_id",
        "room_id",
        "assigned_to",
        "assigned_by",
        "status",
        "notes",
        "started_at",
        "completed_at",
    ];

    protected $casts = [
        "started_at" => "datetime",
        "completed_at" => "datetime",
    ];

    const STATUS_BELUM_MULAI = "belum_mulai";
    const STATUS_SEDANG_DIKERJAKAN = "sedang_dikerjakan";
    const STATUS_MENUNGGU_VERIFIKASI = "menunggu_verifikasi";
    const STATUS_REVISI = "revisi";
    const STATUS_SELESAI = "selesai";

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "assigned_to");
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "assigned_by");
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(CleaningChecklistItem::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CleaningTaskPhoto::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CleaningTaskLog::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where("assigned_to", $userId);
    }

    public function scopeBelumMulai($query)
    {
        return $query->where("status", self::STATUS_BELUM_MULAI);
    }

    public function scopeSedangDikerjakan($query)
    {
        return $query->where("status", self::STATUS_SEDANG_DIKERJAKAN);
    }

    public function scopeSelesai($query)
    {
        return $query->where("status", self::STATUS_SELESAI);
    }

    public function calculateProgress(): float
    {
        $total = $this->checklistItems()->count();
        if ($total === 0) {
            return 0.0;
        }
        $done = $this->checklistItems()->where("is_done", true)->count();

        // For revision tasks, rejected items are not considered done
        // (even if the OB originally marked them as done)
        if ($this->status === self::STATUS_REVISI) {
            $rejected = $this->checklistItems()
                ->where("is_done", true)
                ->where("verification_status", "rejected")
                ->count();
            $done = max(0, $done - $rejected);
        }

        return round(($done / $total) * 100, 1);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($task) {
            $room = $task->room;
            $bonusCategory = 'kosong';
            $notes = "Cleaning - Task ID: " . $task->id;

            if ($room) {
                if (in_array($room->status, ["In-House", "Checkin"])) {
                    $bonusCategory = 'pk';
                    $notes = "PK (Perintah Khusus) Cleaning - Task ID: " . $task->id;
                } else {
                    // Find the last booking for this room to determine category
                    $lastBooking = \App\Models\Booking::where('room_id', $room->id)
                        ->orderByDesc('created_at')
                        ->first();

                    if ($lastBooking && in_array($lastBooking->guest_type, ['sales', 'umum', 'online', 'kos'])) {
                        $bonusCategory = $lastBooking->guest_type;
                    }
                }
            }

            $bonusAmount = \App\Models\ObBonusSetting::getBonusRate($bonusCategory);

            \App\Models\WorkOrder::create([
                "hotel_id" => $task->hotel_id,
                "room_id" => $task->room_id,
                "assigned_to" => $task->assigned_to,
                "type" => "cleaning",
                "status" => "pending",
                "bonus_amount" => $bonusAmount,
                "bonus_category" => $bonusCategory,
                "notes" => $notes,
            ]);
        });

        static::updated(function ($task) {
            if (
                $task->isDirty("status") &&
                $task->status === self::STATUS_SELESAI
            ) {
                \App\Models\WorkOrder::where("assigned_to", $task->assigned_to)
                    ->where("room_id", $task->room_id)
                    ->where("status", "pending")
                    ->where("notes", "like", "%Task ID: " . $task->id . "%")
                    ->update([
                        "status" => "completed",
                        "completed_at" => now(),
                    ]);
            }
        });
    }

    /**
     * Classify a completed room task into a bonus/performance category by
     * looking at the booking that was active/most recent for that room at
     * completion time. Shared by Performa OB (OBPerformanceReportController)
     * and Bonus OB (BonusReportService) so both reports agree on the same
     * category for the same task -- previously Bonus OB trusted the
     * WorkOrder.bonus_category snapshot (set from guest_type at task
     * creation, often null/stale) while Performa OB reclassified live from
     * booking_sources, so the same task could show up differently in each.
     *
     * @param  array<int, iterable>  $bookingsByRoom  Bookings indexed by room_id, preloaded by the caller for the report's date range.
     * @return array{category: string, booking: ?\App\Models\Booking} category is one of: pk, kost, sales_cat, online, umum, kosong
     */
    public static function classifyRoomBonusCategory(?int $roomId, $completedAt, int $hotelId, array $bookingsByRoom = []): array
    {
        if (!$roomId || !$completedAt) {
            return ['category' => 'kosong', 'booking' => null];
        }

        $roomBookings = $bookingsByRoom[$roomId] ?? [];

        // Guest still in-room at completion time -> PK (Perintah Khusus)
        $active = null;
        foreach ($roomBookings as $b) {
            if ($b->check_in <= $completedAt && $b->check_out >= $completedAt) {
                $active = $b;
                break;
            }
        }
        if ($active) {
            return ['category' => 'pk', 'booking' => $active];
        }

        // No active booking -> most recent booking that ended before completion
        $last = null;
        foreach ($roomBookings as $b) {
            if ($b->check_out <= $completedAt) {
                if (!$last || $b->check_out > $last->check_out) {
                    $last = $b;
                }
            }
        }

        if (!$last) {
            $last = \App\Models\Booking::where('room_id', $roomId)
                ->where('hotel_id', $hotelId)
                ->where('check_out', '<=', $completedAt)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->orderByDesc('check_out')
                ->with('bookingSource')
                ->first();
        }

        if (!$last) {
            return ['category' => 'kosong', 'booking' => null];
        }

        $sourceName = strtolower(trim($last->bookingSource?->name ?? $last->source ?? ''));

        if ($last->guest_type === 'kos' || in_array($sourceName, ['kos', 'kost'])) {
            return ['category' => 'kost', 'booking' => $last];
        }

        if ($last->guest_type === 'sales' || $sourceName === 'sales') {
            return ['category' => 'sales_cat', 'booking' => $last];
        }

        if (in_array($sourceName, ['agoda', 'traveloka', 'reddoor', 'reddoors'])) {
            return ['category' => 'online', 'booking' => $last];
        }

        return ['category' => 'umum', 'booking' => $last];
    }
}
