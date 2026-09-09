<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleaningTaskLog extends Model
{
    protected $fillable = [
        'cleaning_task_id',
        'cleaning_checklist_item_id',
        'user_id',
        'action',
        'role',
        'note',
        'photo_path',
        'photo_url',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(CleaningTask::class, 'cleaning_task_id');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(CleaningChecklistItem::class, 'cleaning_checklist_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
