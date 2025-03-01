<?php

namespace App\Service\DailyStats;

use App\Model\Device\Fireplace;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\FireplaceStatusHelper;

final class FireplaceDailyStatsCalculator extends DeviceDailyStatsCalculator implements DailyStatsCalculatorInterface
{
    public function __construct(
        HookRepository        $hookRepository,
        FireplaceStatusHelper $fireplaceHelper,
    ) {
        parent::__construct($hookRepository, $fireplaceHelper);
    }

    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return Fireplace::NAME;
    }
}
