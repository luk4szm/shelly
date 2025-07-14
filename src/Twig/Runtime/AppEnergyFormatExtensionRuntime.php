<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class AppEnergyFormatExtensionRuntime implements RuntimeExtensionInterface
{
    public function formatEnergy(float $energy, string $unit = 'Wh'): string
    {
        $decimals = match (true) {
            $energy == 0, $energy >= 100 => 0,
            $energy >= 10                => 1,
            default                      => 2,
        };

        return sprintf("%.{$decimals}f %s", $energy, $unit);
    }
}
