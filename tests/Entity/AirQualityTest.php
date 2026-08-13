<?php

namespace App\Tests\Entity;

use App\Entity\AirQuality;
use PHPUnit\Framework\TestCase;

final class AirQualityTest extends TestCase
{
    public function testCalculateSeaLevelPressureAppliesCorrection(): void
    {
        $airQuality = (new AirQuality())
            ->setPressure(1000.0)
            ->setTemperature(20.0);

        $airQuality->calculateSeaLevelPressure(91);

        $expectedPressure = round(1000.0 * exp((0.03416 * 91) / (20.0 + 273.15)), 2) - 1.0;

        $this->assertSame($expectedPressure, $airQuality->getSeaLevelPressure());
    }
}
