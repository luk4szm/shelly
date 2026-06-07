<?php

namespace App\Enum;

enum OccupancyMode: string
{
    case Sleeping = 'sleeping';
    case Home     = 'home';
    case Away     = 'away';
    case Vacation = 'vacation';
}
