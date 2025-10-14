<?php

declare(strict_types=1);

namespace App\Service\Location;

use App\Model\Location\LocationInterface;

final class LocationRegistry
{
    /** @var array<string, class-string<LocationInterface>> */
    private array $byName = [];

    /**
     * @param iterable<LocationInterface> $locations
     */
    public function __construct(iterable $locations)
    {
        foreach ($locations as $location) {
            $class = $location::class;

            /** @var class-string $class */
            $name = $class::NAME ?? null;

            if (is_string($name)) {
                $this->byName[$name] = $class;
            }
        }
    }

    /**
     * Returns the fully qualified class name for the given location name (constant NAME).
     */
    public function getClassByName(string $name): ?string
    {
        return $this->byName[$name] ?? null;
    }

    /**
     * Creates a LocationInterface instance for the given name (optionally with DI via factory from outside).
     */
    public function createByName(string $name): ?LocationInterface
    {
        if (null === $class = $this->getClassByName($name)) {
            return null;
        }

        return new $class();
    }
}
