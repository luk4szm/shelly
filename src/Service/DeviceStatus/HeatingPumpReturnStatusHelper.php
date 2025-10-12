<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\HeatingPumpReturn;

final class HeatingPumpReturnStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return HeatingPumpReturn::NAME;
    }

    public function getDeviceId(): string
    {
        return HeatingPumpReturn::DEVICE_ID;
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > HeatingPumpReturn::BOUNDARY_POWER;
    }

    public function showOnDashboard(): bool
    {
        return false;
    }
}
