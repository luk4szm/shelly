<?php

namespace App\Service\Location;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class LocationFinder
{
    public function __construct(
        #[AutowireIterator('app.shelly.locations')]
        private iterable $locations,
    ) {
    }

    public function getLocations(string $group = null): array
    {
        foreach ($this->locations as $location) {
            if (
                $group !== null
                && !in_array($group, $location->getGroups(), true)
            ) {
                continue;
            }

            $locations[] = $location->getName();
        }

        return $locations ?? [];
    }
}
