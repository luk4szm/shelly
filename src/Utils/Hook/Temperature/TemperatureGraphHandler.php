<?php

namespace App\Utils\Hook\Temperature;

use App\Entity\Hook;
use App\Utils\Hook\HookGrouper;

class TemperatureGraphHandler
{
    /**
     * @param array{Hook} $hooks
     * @return array
     */
    public function prepareGroupedHooks(array $hooks): array
    {
        $start = $hooks[0]->getCreatedAt();
        $end   = end($hooks)->getCreatedAt();

        foreach (HookGrouper::byDevice($hooks) as $location => $data) {
            $this->addBoundaryHooks($data, $start, $end);

            $grouped[$location] = array_map(static function (Hook $hook) {
                return TemperatureGraphHandler::serialize($hook);
            }, $data);
        }

        return $grouped ?? [];
    }

    public static function serialize(Hook $hook): array
    {
        return [
            'datetime' => $hook->getCreatedAt()->format('Y-m-d H:i:s'),
            'value'    => (float)$hook->getValue(),
        ];
    }

    private function addBoundaryHooks(array &$hooks, \DateTimeInterface $start, \DateTimeInterface $end): void
    {
        if ($hooks[0]->getCreatedAt() !== $start) {
            array_unshift($hooks, (clone $hooks[0])->setCreatedAt($start));
        }

        if (end($hooks)->getCreatedAt() !== $end) {
            $hooks[] = clone (end($hooks))->setCreatedAt($end);
        }
    }
}
