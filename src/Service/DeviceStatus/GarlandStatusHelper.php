<?php

declare(strict_types=1);

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\Relay\Garland;

final class GarlandStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function getDeviceClass(): string
    {
        return Garland::class;
    }

    public function supports(string $device): bool
    {
        return $device === Garland::NAME;
    }

    public function getDeviceName(): string
    {
        return Garland::NAME;
    }

    public function getDeviceId(): string
    {
        return Garland::DEVICE_ID;
    }

    public function isActive(Hook $hook): bool
    {
        return (float) $hook->getValue() > Garland::BOUNDARY_POWER;
    }

    public function showOnDashboard(): bool
    {
        return false;
    }
}
