<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\AppDateSunTimeRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppDateSunTimeExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sunrise_hour', [AppDateSunTimeRuntime::class, 'getSunriseHour']),
            new TwigFunction('sunset_hour', [AppDateSunTimeRuntime::class, 'getSunsetHour']),
        ];
    }
}
