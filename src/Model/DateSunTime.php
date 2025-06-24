<?php

namespace App\Model;

enum DateSunTime: string
{
    case SUNRISE                     = 'sunrise';
    case SUNSET                      = 'sunset';
    case TRANSIT                     = 'transit';
    case CIVIL_TWILIGHT_BEGIN        = 'civil_twilight_begin';
    case CIVIL_TWILIGHT_END          = 'civil_twilight_end';
    case NAUTICAL_TWILIGHT_BEGIN     = 'nautical_twilight_begin';
    case NAUTICAL_TWILIGHT_END       = 'nautical_twilight_end';
    case ASTRONOMICAL_TWILIGHT_BEGIN = 'astronomical_twilight_begin';
    case ASTRONOMICAL_TWILIGHT_END   = 'astronomical_twilight_end';
}
