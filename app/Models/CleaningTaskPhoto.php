<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleaningTaskPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'cleaning_task_id',
        'type',
        'file_path',
        'original_name',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function cleaningTask(): BelongsTo
    {
        return $this->belongsTo(CleaningTask::class);
    }

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}
