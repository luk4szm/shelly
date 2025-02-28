<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\Boiler;

final class BoilerDeviceStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    protected const DEVICE_NAME    = Boiler::NAME;
    private const   BOUNDARY_POWER = 10;

    public function supports(string $device): bool
    {
        return $device === Boiler::NAME;
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > self::BOUNDARY_POWER;
    }
}
