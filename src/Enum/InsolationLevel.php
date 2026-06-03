<?php

namespace App\Enum;

enum InsolationLevel: int
{
    case IndoorLightsOn   = 75;
    case IndoorLightsOff  = 80;
    case OutdoorLightsOn  = 40;
    case OutdoorLightsOff = 50;
}
