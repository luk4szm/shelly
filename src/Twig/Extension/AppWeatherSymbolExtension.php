<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\AppWeatherSymbolRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppWeatherSymbolExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('weather_symbol', [AppWeatherSymbolRuntime::class, 'getWeatherSymbolImage']),
        ];
    }
}
