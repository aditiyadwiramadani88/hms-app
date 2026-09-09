<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftHandoverItem extends Model
{
    protected $fillable = [
        'shift_handover_id', 'checklist_template_id', 'item_name',
        'is_required', 'is_checked', 'value', 'notes',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_checked' => 'boolean',
    ];

    public function handover()
    {
        return $this->belongsTo(ShiftHandover::class, 'shift_handover_id');
    }

    public function template()
    {
        return $this->belongsTo(HandoverChecklistTemplate::class, 'checklist_template_id');
    }
}
