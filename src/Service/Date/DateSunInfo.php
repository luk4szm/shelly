<?php

namespace App\Service\Date;

use App\Model\DateSunTime;

class DateSunInfo
{
    public static function get(\DateTime $date, DateSunTime $time): ?\DateTimeInterface
    {
        $dateSunInfo = date_sun_info($date->format('U'), (float)$_ENV['LATITUDE'], (float)$_ENV['LONGITUDE']);

        if (!is_numeric($dateSunInfo[$time->value])) {
            return null;
        }

        return (new \DateTime())->setTimestamp($dateSunInfo[$time->value]);
    }

    public static function isDarkOutside(): bool
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
