<?php

namespace App\Service\DailyStats;

use App\Model\Device\Relay\FireplacePump;

final class FireplaceDailyStatsCalculator extends DeviceDailyStatsCalculator
{
    protected function getDevice(): string
    {
        return FireplacePump::class;
    }
}
