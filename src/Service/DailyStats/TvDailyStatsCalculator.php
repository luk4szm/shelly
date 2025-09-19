<?php

namespace App\Service\DailyStats;

use App\Model\Device\Tv;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\TvStatusHelper;

final class TvDailyStatsCalculator extends DeviceDailyStatsCalculator implements DailyStatsCalculatorInterface
{
    public function __construct(
        HookRepository $hookRepository,
        TvStatusHelper $tvStatusHelper,
    ) {
        parent::__construct($hookRepository, $tvStatusHelper);
    }

    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return Tv::NAME;
    }
}
