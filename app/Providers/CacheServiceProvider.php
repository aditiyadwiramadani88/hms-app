<?php

namespace App\Providers;

use App\Models\BookingSource;
use App\Models\GuestCategory;
use App\Models\Hotel;
use App\Models\RoomStatus;
use App\Models\RoomType;
use App\Models\TransactionCategory;
use App\Observers\CacheInvalidationObserver;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $observer = CacheInvalidationObserver::class;

        RoomType::observe($observer);
        RoomStatus::observe($observer);
        Hotel::observe($observer);
        BookingSource::observe($observer);
        GuestCategory::observe($observer);
        TransactionCategory::observe($observer);
    }
}
