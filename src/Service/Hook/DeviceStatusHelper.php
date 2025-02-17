<?php

namespace App\Service\Hook;

use App\Entity\Hook;

class DeviceStatusHelper
{
    public const BOUNDARY_POWER_CENTRAL_HEATING = 8;

    public function isActive(string $device, Hook $hook): bool
    {
        return match ($device) {
            'piec'  => (float)$hook->getValue() > self::BOUNDARY_POWER_CENTRAL_HEATING,
            default => throw new \InvalidArgumentException("Invalid device {$device}"),
        };
    }

    public function getDeviceStatusUnchangedDuration(array $hooks): float
    {
        $firstHook    = $this->getFirstHookOfCurrentStatus($hooks);
        $interval     = (new \DateTime())->diff($firstHook->getCreatedAt());
        $totalSeconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;

        return (float)$totalSeconds / 60;
    }

    private function getFirstHookOfCurrentStatus(array $hooks): Hook
    {
        $currentStatus = $this->isActive('piec', $hooks[0]);

        for ($i = 1; $i < count($hooks); $i++) {
            if ($currentStatus !== $this->isActive('piec', $hooks[$i])) {
                return $hooks[$i - 1];
            }
        }
    }
}
