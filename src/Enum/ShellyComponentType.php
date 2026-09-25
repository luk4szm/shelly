<?php

declare(strict_types=1);

namespace App\Enum;

enum ShellyComponentType: string
{
    case Switch = 'switch';
    case Light  = 'light';
    case Rgbw   = 'rgbw';
    case Cover  = 'cover';
    case Input  = 'input';
}
