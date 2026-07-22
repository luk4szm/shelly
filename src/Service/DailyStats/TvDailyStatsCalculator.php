<?php

namespace App\Service\DailyStats;

use App\Model\Device\PowerMeter\Tv;

final class TvDailyStatsCalculator extends DeviceDailyStatsCalculator
{
    protected function getDevice(): string
    {
        return Tv::class;
    }
}
