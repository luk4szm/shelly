<?php

namespace App\Service\DailyStats;

use App\Model\Device\Hydrophore;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\HydrophoreStatusHelper;

final class HydrophoreDailyStatsCalculator extends DeviceDailyStatsCalculator implements DailyStatsCalculatorInterface
{
    public function __construct(
        HookRepository         $hookRepository,
        HydrophoreStatusHelper $tvStatusHelper,
    ) {
        parent::__construct($hookRepository, $tvStatusHelper);
    }

    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return Hydrophore::NAME;
    }
}
