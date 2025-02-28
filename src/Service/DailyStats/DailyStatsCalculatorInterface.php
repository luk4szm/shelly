<?php

namespace App\Service\DailyStats;

use App\Entity\DeviceDailyStats;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.daily_stats')]
interface DailyStatsCalculatorInterface
{
    public function getDeviceName(): string;

    public function calculateDailyStats(\DateTimeInterface $date): DeviceDailyStats;
}
