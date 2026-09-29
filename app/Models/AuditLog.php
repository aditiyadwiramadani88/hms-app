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

    public function getDescriptionAttribute($value)
    {
        if ($value && str_contains($value, 'User #')) {
            static $cachedUsers = [];
            return preg_replace_callback('/User #(\d+)/', function ($matches) use (&$cachedUsers) {
                $userId = $matches[1];
                if (!array_key_exists($userId, $cachedUsers)) {
                    $cachedUsers[$userId] = User::find($userId)?->name;
                }
                return $cachedUsers[$userId] ?: $matches[0];
            }, $value);
        }
        return $value;
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
