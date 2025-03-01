<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\Boiler;

final class BoilerDeviceStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return Boiler::NAME;
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > Boiler::BOUNDARY_POWER;
    }
}
