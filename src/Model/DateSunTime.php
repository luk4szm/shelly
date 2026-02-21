<?php

namespace App\Model;

/**
 * Sample hours for 2026-02-16
 */
enum DateSunTime: string
{
    case SUNRISE                     = 'sunrise'; // 07:06:52
    case SUNSET                      = 'sunset'; // 17:07:49
    case TRANSIT                     = 'transit'; // 12:07:21
    case CIVIL_TWILIGHT_BEGIN        = 'civil_twilight_begin'; // 06:31:48
    case CIVIL_TWILIGHT_END          = 'civil_twilight_end'; // 17:42:54
    case NAUTICAL_TWILIGHT_BEGIN     = 'nautical_twilight_begin'; // 05:52:00
    case NAUTICAL_TWILIGHT_END       = 'nautical_twilight_end'; // 18:22:41
    case ASTRONOMICAL_TWILIGHT_BEGIN = 'astronomical_twilight_begin'; // 05:12:38
    case ASTRONOMICAL_TWILIGHT_END   = 'astronomical_twilight_end'; // 19:02:04
}
