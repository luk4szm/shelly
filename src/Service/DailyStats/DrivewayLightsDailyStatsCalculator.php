<?php

declare(strict_types=1);

namespace App\Service\DailyStats;

use App\Model\Device\Relay\DrivewayLights;

final class DrivewayLightsDailyStatsCalculator extends DeviceDailyStatsCalculator
{
    protected function getDevice(): string
    {
        return DrivewayLights::class;
    }
}
