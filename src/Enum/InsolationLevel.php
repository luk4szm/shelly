<?php

namespace App\Enum;

enum InsolationLevel: int
{
    case IndoorLightsOn   = 80;
    case IndoorLightsOff  = 85;
    case OutdoorLightsOn  = 30;
    case OutdoorLightsOff = 40;
}
