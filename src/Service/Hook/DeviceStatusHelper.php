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

    /**
     * Calculates the duration (seconds) of a given measurement by comparing it with the next
     * If the next one is null, we calculate it until midnight, unless we calculate the statistics of the current day
     * Then we count to the current hour
     *
     * @param Hook  $current
     * @param ?Hook $next
     * @return int #seconds
     * @throws \DateMalformedStringException
     */
    public function calculateHookDuration(Hook $current, ?Hook $next): int
    {
        $today = new \DateTime('today');

        if (null !== $next) {
            // If the next hook is not a null, we calculate the difference between measures
            $interval = $current->getCreatedAt()->diff($next->getCreatedAt());
        } elseif ($current->getCreatedAt()->format('Y-z') === $today->format('Y-z')) {
            // If hook is from the day that is currently going on, we calculate the time to actual datetime
            $interval = $current->getCreatedAt()->diff(new \DateTime());
        } else {
            // calculate the time to the midnight of the measurement day
            $interval = $current->getCreatedAt()->diff(
                (new \DateTime($current->getCreatedAt()->format('Y-m-d')))
                    ->setTime(23, 59, 59)
            );
        }

        return $interval->h * 3600 + $interval->i * 60 + $interval->s;
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
