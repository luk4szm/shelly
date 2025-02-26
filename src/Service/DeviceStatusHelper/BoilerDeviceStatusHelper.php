<?php

namespace App\Service\DeviceStatusHelper;

use App\Entity\Hook;

final class BoilerDeviceStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    protected const DEVICE_NAME    = 'piec';
    private const   BOUNDARY_POWER = 10;

    public function supports(string $device): bool
    {
        return $device === 'piec';
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > self::BOUNDARY_POWER;
    }
}
