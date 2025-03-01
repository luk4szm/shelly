<?php

namespace App\Service\Device;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class DeviceFinder
{
    public function __construct(
        #[AutowireIterator('app.shelly.devices')]
        private iterable $devices,
    ) {
    }

    public function getDeviceNames(): array
    {
        foreach ($this->devices as $device) {
            $deviceNames[] = $device->getName();
        }

        return $deviceNames ?? [];
    }
}
