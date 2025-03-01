<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\Fireplace;

final class FireplaceStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return Fireplace::NAME;
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > Fireplace::BOUNDARY_POWER;
    }
}
