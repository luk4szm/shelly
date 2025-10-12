<?php

namespace App\Service\DailyStats;

use App\Model\Device\HeatingPumpSupply;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\HeatingPumpSupplyStatusHelper;

final class HeatingPumpSupplyDailyStatsCalculator extends DeviceDailyStatsCalculator implements DailyStatsCalculatorInterface
{
    public function __construct(
        HookRepository                $hookRepository,
        HeatingPumpSupplyStatusHelper $heatingPumpStatusHelper,
    ) {
        parent::__construct($hookRepository, $heatingPumpStatusHelper);
    }

    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function isDeviceInstalledOn(\DateTimeInterface $date): bool
    {
        return new \DateTime(HeatingPumpSupply::INSTALLATION_DATE) <= $date;
    }

    public function getDeviceName(): string
    {
        return HeatingPumpSupply::NAME;
    }
}
