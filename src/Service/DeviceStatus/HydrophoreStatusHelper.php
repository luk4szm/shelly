<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\Hydrophore;

final class HydrophoreStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return Hydrophore::NAME;
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
        return false;
    }
}
