<?php

namespace App\Utils\Hook\GraphHandler;

use App\Entity\Hook;

class PressureGraphHandler extends GraphHandler
{
    public static function serialize(Hook $hook): array
    {
        return [
            'datetime' => $hook->getCreatedAt()->format('Y-m-d H:i:s'),
            'value'    => round(((float)$hook->getValue()) / 100, 2),
        ];
    }
}
