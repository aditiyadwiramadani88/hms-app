<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CacheInvalidationObserver
{
    /**
     * Handle the model "saved" event.
     */
    public function saved($model): void
    {
        $this->invalidate($model);
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted($model): void
    {
        $this->invalidate($model);
    }

    /**
     * Invalidate the cache for this model.
     */
    protected function invalidate($model): void
    {
        $modelName = Str::plural(Str::snake(class_basename($model)));
        $hotelId = $model->hotel_id ?? (session('active_hotel_id') ?? 'global');
        
        $cacheKey = "hotel:{$hotelId}:{$modelName}";
        Cache::forget($cacheKey);
        
        // Log for debugging (optional)
        // \Log::debug("Cache invalidated: {$cacheKey}");
    }
}
