<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\HotWaterPump;

final class HotWaterPumpStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function supports(string $device): bool
    {
        return $device === self::getDeviceName();
    }

    public function getDeviceName(): string
    {
        return HotWaterPump::NAME;
    }

    public function getDeviceId(): string
    {
        return HotWaterPump::DEVICE_ID;
    }

    public function isActive(Hook $hook): bool
    {
        return (float)$hook->getValue() > HotWaterPump::BOUNDARY_POWER;
    }

    public function showOnDashboard(): bool
    {
        return true;
    }
}
