<?php

namespace App\Service\DailyStats;

use App\Model\Device\Relay\HeatingPumpSupply;

final class HeatingPumpSupplyDailyStatsCalculator extends DeviceDailyStatsCalculator
{
    protected function getDevice(): string
    {
        return HeatingPumpSupply::class;
    }
}
