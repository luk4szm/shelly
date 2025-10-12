<?php

namespace App\Service\DailyStats;

use App\Model\Device\Boiler;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\BoilerDeviceStatusHelper;

final class BoilerDailyStatsCalculator extends DeviceDailyStatsCalculator implements DailyStatsCalculatorInterface
{
    public function __construct(
        HookRepository           $hookRepository,
        BoilerDeviceStatusHelper $boilerHelper,
    ) {
        parent::__construct($hookRepository,$boilerHelper);
    }

    public function supports(string $device): bool
    {
        return $device === Boiler::NAME;
    }

    public function isDeviceInstalledOn(\DateTimeInterface $date): bool
    {
        return new \DateTime(Boiler::INSTALLATION_DATE) <= $date;
    }

    public function getDeviceName(): string
    {
        return Boiler::NAME;
    }
}
