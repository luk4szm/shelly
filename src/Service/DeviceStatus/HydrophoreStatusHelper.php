<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Enum\SeasonMode;
use App\Model\Device\Relay\Hydrophore;

final class HydrophoreStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return
            Hydrophore::NAME;
    }

    public function getDeviceId(): string
    {
        return Hydrophore::DEVICE_ID;
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > Hydrophore::BOUNDARY_POWER;
    }

    public function showOnDashboard(): bool
    {
        $season = $this->configRepository->getValueByName('season_mode');

        return $season !== SeasonMode::Winter->value;
    }

    public function getPriority(): int
    {
        return 40;
    }
}
