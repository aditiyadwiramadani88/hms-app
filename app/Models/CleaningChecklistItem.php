<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleaningChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cleaning_task_id',
        'name',
        'is_done',
        'sort_order',
        'photo_url',
        'photo_path',
        'comment',
        'verification_status',
        'checker_note',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function cleaningTask(): BelongsTo
    {
        return $this->belongsTo(CleaningTask::class);
    }
}
