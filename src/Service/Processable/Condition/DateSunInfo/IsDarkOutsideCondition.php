<?php

namespace App\Service\Processable\Condition\DateSunInfo;

use App\Model\DateSunTime;
use App\Service\Date\DateSunInfo;

class IsDarkOutsideCondition extends DateSunCondition
{
    public const NAME = 'is_dark_outside_condition';

    /**
     * Checks if it is currently dark outside (i.e., before civil twilight begins or after civil twilight ends).
     *
     * @return bool True if it is dark outside.
     */
    public function isSatisfied(): bool
    {
        $now  = new \DateTime();
        $dawn = DateSunInfo::get($now, DateSunTime::CIVIL_TWILIGHT_BEGIN);
        $dusk = DateSunInfo::get($now, DateSunTime::CIVIL_TWILIGHT_END);

        // It is dark when:
        // A) The current time is before dawn (morning)
        $isBeforeDawn = $now < $dawn;

        // OR
        // B) The current time is after dusk (evening)
        $isAfterDusk = $now > $dusk;

        return $isBeforeDawn || $isAfterDusk;
    }
}
