<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetHistory extends Model
{
    protected $fillable = [
        'asset_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
