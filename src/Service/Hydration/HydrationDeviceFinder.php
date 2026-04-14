<?php

namespace App\Service\Hydration;

use App\Model\Device\Hydration\ValveDevice;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

readonly class HydrationDeviceFinder
{
    public function __construct(
        #[TaggedIterator('app.shelly.devices.valve')] private iterable $valveDevices,
    ) {}

    public function getValves(): iterable
    {
        return $this->valveDevices;
    }

    public function getByName(string $name): ?ValveDevice
    {
        foreach ($this->valveDevices as $valve) {
            if ($valve::NAME === $name) {
                return $valve;
            }
        }

        throw new \RuntimeException(sprintf('There is no valve called "%s"', $name));
    }
}
