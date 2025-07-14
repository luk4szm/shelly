<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class AppGasConsumeFormatExtensionRuntime implements RuntimeExtensionInterface
{
    public function formatGasConsume(float $consume): string
    {
        $decimals = match (true) {
            $consume == 0, $consume >= 100 => 0,
            $consume >= 10                 => 1,
            default                        => 2,
        };

        return sprintf("%.{$decimals}f %s", $consume, 'm³');
    }
}
