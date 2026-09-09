<?php

namespace App\Traits;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToHotel
{
    public static bool $isScoped = true;

    public static function bootBelongsToHotel()
    {
        static::addGlobalScope('hotel', function (Builder $builder) {
            if (self::$isScoped && active_hotel_id()) {
                $builder->where($builder->getModel()->getTable() . '.' . config('permission.column_names.team_foreign_key', 'hotel_id'), active_hotel_id());
            }
        });

        static::creating(function ($model) {
            if (self::$isScoped && active_hotel_id() && !$model->hotel_id) {
                $model->hotel_id = active_hotel_id();
            }
        });
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, config('permission.column_names.team_foreign_key', 'hotel_id'));
    }
}
