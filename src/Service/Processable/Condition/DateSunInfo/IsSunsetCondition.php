<?php

namespace App\Service\Processable\Condition\DateSunInfo;

use App\Model\DateSunTime;
use App\Service\Date\DateSunInfo;

class IsSunsetCondition extends DateSunCondition
{
    public const NAME = 'is_sunset_condition';

    /**
     * Checks if it's sunset
     *
     * @return bool
     */
    public function isSatisfied(): bool
    {
        return DateSunInfo::isTimeWindow(DateSunTime::SUNSET);
    }
}
