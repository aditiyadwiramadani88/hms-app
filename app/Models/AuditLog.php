<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToHotel;

class AuditLog extends Model
{
    use BelongsToHotel;
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($action, $description, $model = null)
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
