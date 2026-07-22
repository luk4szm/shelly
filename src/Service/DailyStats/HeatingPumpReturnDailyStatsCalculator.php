<?php

namespace App\Service\DailyStats;

use App\Model\Device\Relay\HeatingPumpReturn;

final class HeatingPumpReturnDailyStatsCalculator extends DeviceDailyStatsCalculator
{
    protected function getDevice(): string
    {
        return HeatingPumpReturn::class;
    }
}
