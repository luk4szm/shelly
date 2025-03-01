<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\Solars;

final class SolarsStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return Solars::NAME;
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > Solars::BOUNDARY_POWER;
    }
}
