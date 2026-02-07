<?php

namespace App\Service\Processable\Condition\DateSunInfo;

use App\Service\Date\DateSunInfo;

class IsBrightOutsideCondition extends DateSunCondition
{
    public const NAME = 'is_bright_outside_condition';

    /**
     * Checks if it is currently bright outside (i.e., before civil twilight begins or after civil twilight ends).
     *
     * @return bool True if it is bright outside.
     */
    public function isSatisfied(): bool
    {
        return DateSunInfo::isBrightOutside();
    }
}
