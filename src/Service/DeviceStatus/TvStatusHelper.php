<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\Tv;

final class TvStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return Tv::NAME;
    }

    public function getDeviceId(): string
    {
        return Tv::DEVICE_ID;
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > Tv::BOUNDARY_POWER;
    }

    public function showOnDashboard(): bool
    {
        return false;
    }
}
