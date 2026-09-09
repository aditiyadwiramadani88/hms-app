<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class Room extends Model
{
    use BelongsToHotel;
    protected $fillable = [
        "room_number",
        "room_type_id",
        "floor",
        "status",
        "assigned_staff_id",
        "price_public",
        "price_sales",
        "price_high_season",
        "price_breakfast_public",
        "price_breakfast_sales",
        "price_breakfast_high_season",
        "is_kos",
        "price_kos",
        "yearly_price",
        "image",
        "price_extra_person",
        "max_occupancy",
        "notes",
    ];

    protected $casts = [
        "status" => "string",
        "price_public" => "decimal:2",
        "price_sales" => "decimal:2",
        "price_high_season" => "decimal:2",
        "price_breakfast_public" => "decimal:2",
        "price_breakfast_sales" => "decimal:2",
        "price_breakfast_high_season" => "decimal:2",
        "is_kos" => "boolean",
        "price_kos" => "decimal:2",
        "yearly_price" => "decimal:2",
        "price_extra_person" => "decimal:2",
        "max_occupancy" => "integer",
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, "assigned_staff_id");
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    public function checklistTemplates()
    {
        return $this->belongsToMany(
            CleaningChecklistTemplate::class,
            "room_checklist_template",
            "room_id",
            "checklist_template_id",
        );
    }

    public function kostPricingTiers()
    {
        return $this->hasMany(RoomKostPricingTier::class)->orderBy(
            "duration_months",
        );
    }

    public function cleaningTasks()
    {
        return $this->hasMany(CleaningTask::class);
    }

    public function latestCleaningTask()
    {
        return $this->hasOne(CleaningTask::class)->orderByDesc("created_at");
    }

    public function scopeAvailable(Builder $query): Builder
    {
        $availableStatuses = \App\Models\RoomStatus::where("is_available", true)
            ->pluck("name")
            ->toArray();
        return $query->whereIn("status", $availableStatuses);
    }
}
