<?php

namespace App\Services;

use App\Models\AttendanceLocation;

class GeofenceService
{
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function validateLocation(int $hotelId, float $lat, float $lng): ?AttendanceLocation
    {
        $locations = AttendanceLocation::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->get();

        if ($locations->isEmpty()) {
            return null;
        }

        $nearest = null;
        $nearestDistance = null;

        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                $lat, $lng,
                (float) $location->latitude,
                (float) $location->longitude
            );

            if ($distance <= $location->radius_meters) {
                if ($nearest === null || $distance < $nearestDistance) {
                    $nearest = $location;
                    $nearestDistance = $distance;
                }
            }
        }

        return $nearest;
    }

    public function getNearestWithDistance(int $hotelId, float $lat, float $lng): ?array
    {
        $locations = AttendanceLocation::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->get();

        if ($locations->isEmpty()) {
            return null;
        }

        $nearest = null;
        $nearestDistance = null;

        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                $lat, $lng,
                (float) $location->latitude,
                (float) $location->longitude
            );

            if ($nearest === null || $distance < $nearestDistance) {
                $nearest = $location;
                $nearestDistance = $distance;
            }
        }

        return [
            'location' => $nearest,
            'distance' => round($nearestDistance),
            'is_within_radius' => $nearestDistance <= $nearest->radius_meters,
        ];
    }
}
