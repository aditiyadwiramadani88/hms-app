<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToHotel;

class MaintenanceRecord extends Model
{
    use BelongsToHotel;

    protected $fillable = [
        'hotel_id', 'room_id', 'category_id', 'maintenance_date',
        'description', 'actions', 'technician_name', 'cost',
        'notes', 'status', 'created_by',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'actions' => 'array',
        'cost' => 'decimal:2',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function category()
    {
        return $this->belongsTo(MaintenanceCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
