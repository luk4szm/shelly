<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Enum\SeasonMode;
use App\Model\Device\PowerMeter\Fireplace;
use App\Model\Device\Relay\FireplacePump;

final class FireplaceStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function getDeviceClass(): string
    {
        return FireplacePump::class;
    }

    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return Fireplace::NAME;
    }

    public function getDeviceId(): string
    {
        return FireplacePump::DEVICE_ID;
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > FireplacePump::BOUNDARY_POWER;
    }

    public function isHeatingAppliance(): bool
    {
        return true;
    }

    public function showOnDashboard(): bool
    {
        $season = $this->configRepository->getValueByName('season_mode');

        return $season !== SeasonMode::Summer->value;
    }

    public function getPriority(): int
    {
        return 80;
    }
}
