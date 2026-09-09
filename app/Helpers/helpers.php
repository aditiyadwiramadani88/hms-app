<?php

if (!function_exists('active_hotel_id')) {
    function active_hotel_id()
    {
        $hotelId = session('active_hotel_id');
        if (!$hotelId) {
            $defaultHotel = \App\Models\Hotel::where('is_active', true)->first();
            return $defaultHotel ? $defaultHotel->id : null;
        }
        return $hotelId;
    }
}

if (!function_exists('current_hotel')) {
    function current_hotel()
    {
        if (active_hotel_id()) {
            return \App\Models\Hotel::find(active_hotel_id());
        }
        return null;
    }
}

if (!function_exists('get_hotel_date')) {
    /**
     * Get current hotel business date.
     * If before 12:00 PM, it's still considered the previous day.
     */
    function get_hotel_date()
    {
        $now = now();
        if ($now->hour < 12) {
            return $now->subDay()->format('Y-m-d');
        }
        return $now->format('Y-m-d');
    }
}

/**
 * Polyfill for array_all used by spatie/laravel-permission 7.3.0
 */
if (!function_exists('array_all')) {
    function array_all(array $array, callable $callback)
    {
        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }
        return true;
    }
}

