<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class AppEnergyFormatExtensionRuntime implements RuntimeExtensionInterface
{
    private const KWH_CONVERSION_THRESHOLD = 1000;

    public function formatEnergy(float $energy, string $unit = 'Wh'): string
    {
        if ($energy >= self::KWH_CONVERSION_THRESHOLD) {
            $energy /= self::KWH_CONVERSION_THRESHOLD;
            $unit = 'kWh';
        }

        $decimals = match (true) {
            $energy == 0, $energy >= 100 => 0,
            $energy >= 10                => 1,
            default                      => 2,
        };

        return sprintf("%.{$decimals}f %s", $energy, $unit);
    }
}
