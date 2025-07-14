<?php

namespace App\Utils\Hook\GraphHandler;

use App\Entity\Hook;

final class PowerGraphHandler extends GraphHandler
{
    /**
     * Serializuje obiekt Hook do formatu wymaganego przez ApexCharts.
     * Zwraca tablicę [timestamp_w_milisekundach, wartość].
     *
     * @param Hook $hook
     * @return array{int, float}
     */
    public static function serialize(Hook $hook): array
    {
        return [
            $hook->getCreatedAt()->getTimestamp() * 1000,
            (float)$hook->getValue(),
        ];
    }
}
