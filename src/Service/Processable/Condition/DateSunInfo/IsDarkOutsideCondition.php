<?php

namespace App\Service\Processable\Condition\DateSunInfo;

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
        return DateSunInfo::isDarkOutside();
    }
}
