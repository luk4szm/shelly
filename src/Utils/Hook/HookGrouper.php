<?php

namespace App\Utils\Hook;

use App\Entity\Hook;

class HookGrouper
{
    public static function byDevice(array $hooks): array
    {
        /** @var Hook $hook */
        foreach ($hooks as $hook) {
            $grouped[$hook->getDevice()][] = $hook;
        }

        return $grouped ?? [];
    }
}
