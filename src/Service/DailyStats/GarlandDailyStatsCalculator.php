<?php

declare(strict_types=1);

namespace App\Service\DailyStats;

use App\Model\Device\Relay\Garland;

final class GarlandDailyStatsCalculator extends DeviceDailyStatsCalculator
{
    protected function getDevice(): string
    {
        return Garland::class;
    }
}
