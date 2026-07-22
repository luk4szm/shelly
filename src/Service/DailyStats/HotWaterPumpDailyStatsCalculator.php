<?php

namespace App\Service\DailyStats;

use App\Model\Device\Relay\HotWaterPump;

final class HotWaterPumpDailyStatsCalculator extends DeviceDailyStatsCalculator
{
    protected function getDevice(): string
    {
        return HotWaterPump::class;
    }
}
