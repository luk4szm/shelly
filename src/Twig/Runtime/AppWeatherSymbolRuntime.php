<?php

namespace App\Twig\Runtime;

use App\Model\Weather\SymbolCode;
use Twig\Extension\RuntimeExtensionInterface;

class AppWeatherSymbolRuntime implements RuntimeExtensionInterface
{
    public function getWeatherSymbolImage(string $weatherSymbolCode): string
    {
        return sprintf('/images/weather/%s.svg', SymbolCode::valueFromName($weatherSymbolCode));
    }
}
