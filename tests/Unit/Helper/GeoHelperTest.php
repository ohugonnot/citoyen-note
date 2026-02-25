<?php

namespace App\Tests\Unit\Helper;

use App\Helper\GeoHelper;
use PHPUnit\Framework\TestCase;

class GeoHelperTest extends TestCase
{
    public function testHaversineReturnsZeroForSamePoint(): void
    {
        $distance = GeoHelper::haversine(48.8566, 2.3522, 48.8566, 2.3522);
        $this->assertEqualsWithDelta(0.0, $distance, 0.01);
    }

    public function testHaversineParisToLyon(): void
    {
        // Paris → Lyon ≈ 392 km
        $distance = GeoHelper::haversine(48.8566, 2.3522, 45.7640, 4.8357);
        $this->assertEqualsWithDelta(392, $distance, 10);
    }

    public function testHaversineIsSymmetric(): void
    {
        $d1 = GeoHelper::haversine(48.8566, 2.3522, 45.7640, 4.8357);
        $d2 = GeoHelper::haversine(45.7640, 4.8357, 48.8566, 2.3522);
        $this->assertEqualsWithDelta($d1, $d2, 0.001);
    }

    public function testFormatDistanceMeters(): void
    {
        $this->assertSame('500 m', GeoHelper::formatDistance(0.5));
        $this->assertSame('100 m', GeoHelper::formatDistance(0.1));
    }

    public function testFormatDistanceKilometers(): void
    {
        $this->assertSame('5 km', GeoHelper::formatDistance(5.0));
        $this->assertSame('12.3 km', GeoHelper::formatDistance(12.34));
    }
}
