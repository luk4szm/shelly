<?php

namespace App\Service\Hydration;

use App\Model\Device\Hydration\ValveDevice;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class HydrationDeviceFinder
{
    public function __construct(
        #[AutowireIterator('app.shelly.devices.valve', defaultPriorityMethod: 'getPriority')] private iterable $valveDevices,
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
