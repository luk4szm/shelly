<?php

namespace App\Utils\Hook;

use App\Entity\Hook;

class HookDurationUtil
{
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
    public static function calculateHookDuration(Hook $current, ?Hook $next): int
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
}
