<?php

namespace App\Twig\Runtime;

use App\Utils\TimeUtils;
use Twig\Extension\RuntimeExtensionInterface;

class AppHookDurationTimeRuntime implements RuntimeExtensionInterface
{
    public function getHookDurationTimeReadable(int $seconds): string
    {
        return TimeUtils::getReadableTime($seconds);
    }
}
