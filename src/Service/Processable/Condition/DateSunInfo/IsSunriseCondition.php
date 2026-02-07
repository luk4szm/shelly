<?php

namespace App\Service\Processable\Condition\DateSunInfo;

use App\Model\DateSunTime;
use App\Service\Date\DateSunInfo;

class IsSunriseCondition extends DateSunCondition
{
    public const NAME = 'is_sunrise_condition';

    /**
     * Checks if it's sunrise
     *
     * @return bool
     */
    public function isSatisfied(): bool
    {
        return DateSunInfo::isTimeWindow(DateSunTime::SUNRISE);
    }
}
