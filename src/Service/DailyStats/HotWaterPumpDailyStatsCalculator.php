<?php

namespace App\Service\DailyStats;

use App\Model\Device\HotWaterPump;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\HotWaterPumpStatusHelper;

final class HotWaterPumpDailyStatsCalculator extends DeviceDailyStatsCalculator implements DailyStatsCalculatorInterface
{
    public function __construct(
        HookRepository           $hookRepository,
        HotWaterPumpStatusHelper $hotWaterPumpStatusHelper,
    ) {
        parent::__construct($hookRepository, $hotWaterPumpStatusHelper);
    }

    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function isDeviceInstalledOn(\DateTimeInterface $date): bool
    {
        return new \DateTime(HotWaterPump::INSTALLATION_DATE) <= $date;
    }

    public function getDeviceName(): string
    {
        return HotWaterPump::NAME;
    }
}
