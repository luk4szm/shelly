<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\AppEnergyFormatExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppEnergyFormatExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_energy', [AppEnergyFormatExtensionRuntime::class, 'formatEnergy']),
        ];
    }
}
