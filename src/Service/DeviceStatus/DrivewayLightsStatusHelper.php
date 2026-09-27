<?php

declare(strict_types=1);

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\Device\Relay\DrivewayLights;

final class DrivewayLightsStatusHelper extends DeviceStatusHelper implements DeviceStatusHelperInterface
{
    public function getDeviceClass(): string
    {
        return DrivewayLights::class;
    }

    public function supports(string $device): bool
    {
        return $device === DrivewayLights::NAME;
    }

    public function getDeviceName(): string
    {
        return DrivewayLights::NAME;
    }

    public function getDeviceId(): string
    {
        return DrivewayLights::DEVICE_ID;
    }

    public function isActive(Hook $hook): bool
    {
        return (float) $hook->getValue() > DrivewayLights::BOUNDARY_POWER;
    }

    public function showOnDashboard(): bool
    {
        return false;
    }
}
