<?php

namespace App\Service\DailyStats;

use App\Model\Device\Solars;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\SolarsStatusHelper;

final class SolarsDailyStatsCalculator extends DeviceDailyStatsCalculator implements DailyStatsCalculatorInterface
{
    public function __construct(
        HookRepository     $hookRepository,
        SolarsStatusHelper $solarsHelper,
    ) {
        parent::__construct($hookRepository, $solarsHelper);
    }

    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return Solars::NAME;
    }
}
