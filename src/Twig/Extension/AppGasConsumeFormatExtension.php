<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\AppGasConsumeFormatExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppGasConsumeFormatExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_gas_consume', [AppGasConsumeFormatExtensionRuntime::class, 'formatGasConsume'], ['is_safe' => ['html']]),
        ];
    }
}
