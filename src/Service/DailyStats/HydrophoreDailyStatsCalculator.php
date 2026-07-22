<?php

namespace App\Service\DailyStats;

use App\Model\Device\Relay\Hydrophore;

final class HydrophoreDailyStatsCalculator extends DeviceDailyStatsCalculator
{
    protected function getDevice(): string
    {
        return Hydrophore::class;
    }
}
