<?php

namespace App\Helper;

class GeoHelper
{
    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public static function formatDistance(float $distance): string
    {
        return $distance < 1 ? (round($distance * 1000) . ' m') : (round($distance, 1) . ' km');
    }
}
