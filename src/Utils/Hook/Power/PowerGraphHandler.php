<?php

namespace App\Utils\Hook\Power;

use App\Entity\Hook;
use App\Utils\Hook\HookGrouper;

class PowerGraphHandler
{
    /**
     * @param Hook[] $hooks
     * @return array
     */
    public function prepareGroupedHooks(array $hooks): array
    {
        if (empty($hooks)) {
            return [];
        }

        $start = $hooks[0]->getCreatedAt();
        $end   = end($hooks)->getCreatedAt();

        foreach (HookGrouper::byDevice($hooks) as $location => $data) {
            $this->addBoundaryHooks($data, $start, $end);

            $grouped[$location] = array_map(static function (Hook $hook) {
                return self::serialize($hook);
            }, $data);
        }

        return $grouped ?? [];
    }

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

    private function addBoundaryHooks(array &$hooks, \DateTimeInterface $start, \DateTimeInterface $end): void
    {
        if ($hooks[0]->getCreatedAt() !== $start) {
            $clone = clone $hooks[0];
            $clone->setCreatedAt($start);
            array_unshift($hooks, $clone);
        }

        if (end($hooks)->getCreatedAt() !== $end) {
            $clone = clone end($hooks);
            $clone->setCreatedAt($end);
            $hooks[] = $clone;
        }
    }
}
