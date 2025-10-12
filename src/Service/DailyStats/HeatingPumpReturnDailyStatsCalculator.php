<?php

namespace App\Service\DailyStats;

use App\Model\Device\HeatingPumpReturn;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\HeatingPumpReturnStatusHelper;

final class HeatingPumpReturnDailyStatsCalculator extends DeviceDailyStatsCalculator implements DailyStatsCalculatorInterface
{
    public function __construct(
        HookRepository                $hookRepository,
        HeatingPumpReturnStatusHelper $heatingPumpStatusHelper,
    ) {
        parent::__construct($hookRepository, $heatingPumpStatusHelper);
    }

    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function isDeviceInstalledOn(\DateTimeInterface $date): bool
    {
        return new \DateTime(HeatingPumpReturn::INSTALLATION_DATE) <= $date;
    }

    public function getDeviceName(): string
    {
        return HeatingPumpReturn::NAME;
    }
}
