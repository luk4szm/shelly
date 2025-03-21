<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\AppHookDurationTimeRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppHookDurationTimeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('readableDurationTime', [AppHookDurationTimeRuntime::class, 'getHookDurationTimeReadable']),
        ];
    }
}
