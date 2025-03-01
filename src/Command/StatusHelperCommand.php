<?php

namespace App\Command;

use App\Service\Device\DeviceFinder;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

abstract class StatusHelperCommand extends DeviceCommand
{
    public function __construct(
        #[AutowireIterator('app.shelly.device_status_helper')]
        private readonly iterable $statusHelpers,
        DeviceFinder $deviceFinder,
    ) {
        parent::__construct($deviceFinder);
    }

    protected function getDeviceHelper(string $deviceName): DeviceStatusHelperInterface
    {
        /** @var DeviceStatusHelperInterface $helper */
        foreach ($this->statusHelpers as $helper) {
            if ($helper->supports($deviceName)) {
                return $helper->getStatusHelperInstance();
            }
        }

        throw new \RuntimeException(sprintf('There is no configured helper for the device called "%s"', $deviceName));
    }
}
