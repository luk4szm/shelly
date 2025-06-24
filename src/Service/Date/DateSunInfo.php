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
}
