<?php

namespace App\Service\DailyStats;

use App\Model\Device\PowerMeter\Solars;

final class SolarsDailyStatsCalculator extends DeviceDailyStatsCalculator
{
    protected function getDevice(): string
    {
        return Solars::class;
    }
}
