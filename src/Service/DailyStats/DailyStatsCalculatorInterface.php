<?php

namespace App\Service\DailyStats;

use App\Entity\DeviceDailyStats;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.daily_stats')]
interface DailyStatsCalculatorInterface
{
    public function supports(string $device): bool;

    public function isDeviceInstalledOn(\DateTimeInterface $date): bool;

    public function getDeviceName(): string;

    public function calculateDailyStats(\DateTimeInterface $date): DeviceDailyStats;
}
