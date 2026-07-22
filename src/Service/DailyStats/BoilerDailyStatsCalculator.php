<?php

namespace App\Service\DailyStats;

use App\Model\Device\PowerMeter\Boiler;

final class BoilerDailyStatsCalculator extends DeviceDailyStatsCalculator
{
    protected function getDevice(): string
    {
        return Boiler::class;
    }
}
