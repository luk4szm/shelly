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

    public function getLocations(): array
    {
        foreach ($this->locations as $location) {
            $locations[] = $location->getName();
        }

        return $locations ?? [];
    }
}
