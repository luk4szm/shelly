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

    public static function isBrightOutside(): bool
    {
        $now  = new \DateTime();
        $dawn = DateSunInfo::get($now, DateSunTime::CIVIL_TWILIGHT_BEGIN);
        $dusk = DateSunInfo::get($now, DateSunTime::CIVIL_TWILIGHT_END);

        // Handle edge cases where sun info is not available
        if ($dawn === null || $dusk === null) {
            return false;
        }

        // It is bright when:
        // The current time is after dawn AND before dusk
        $isAfterDawn  = $now >= $dawn;
        $isBeforeDusk = $now <= $dusk;

        return $isAfterDawn && $isBeforeDusk;
    }

    public static function isTimeWindow(DateSunTime $sunTime): bool
    {
        $now        = new \DateTime();
        $targetTime = DateSunInfo::get($now, $sunTime);

        // Handle edge case where sun time info is not available
        if ($targetTime === null) {
            return false;
        }

        // Calculate time window: 2.5 minutes before and after target time
        $timeWindowMinutes = 2.5;
        $windowStart       = clone $targetTime;
        $windowStart->modify("-{$timeWindowMinutes} minutes");

        $windowEnd = clone $targetTime;
        $windowEnd->modify("+{$timeWindowMinutes} minutes");

        // Check if current time is within the time window
        return $now >= $windowStart && $now <= $windowEnd;
    }
}
