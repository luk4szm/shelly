<?php

namespace App\Service\Hook;

use App\Entity\Hook;

class DeviceStatusHelper
{
    public const BOUNDARY_POWER_CENTRAL_HEATING = 8;

    public function isActive(string $device, Hook $lastHook): bool
    {
        return match ($device) {
            'piec'  => (float)$lastHook->getValue() > self::BOUNDARY_POWER_CENTRAL_HEATING,
            default => throw new \InvalidArgumentException("Invalid device {$device}"),
        };
    }

    public function getDeviceStatusUnchangedDuration(Hook $lastHook): float
    {
        $interval     = (new \DateTime())->diff($lastHook->getCreatedAt());
        $totalSeconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;

        return (float)$totalSeconds / 60;
    }
}
