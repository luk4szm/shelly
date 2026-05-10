<?php

namespace App\Enum;

enum DaylightMode: string
{
    case Day      = 'day';     // Jasno, światła zgaszone
    case Twilight = 'twilight'; // Szarówka, światła nastrojowe
    case Night    = 'night';   // Ciemno, światła pełne/nocne
}
