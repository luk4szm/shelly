<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\HeatingPumpSupply;

final class HeatingPumpSupplyStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return HeatingPumpSupply::NAME;
    }

    public function getDeviceId(): string
    {
        return HeatingPumpSupply::DEVICE_ID;
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > HeatingPumpSupply::BOUNDARY_POWER;
    }

    public function showOnDashboard(): bool
    {
        return false;
    }
}
