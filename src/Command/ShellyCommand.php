<?php

namespace App\Command;

use App\Service\DeviceStatusHelper\DeviceStatusHelperInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

abstract class ShellyCommand extends Command
{
    public function __construct(
        #[AutowireIterator('app.shelly.device_status_helper')]
        private readonly iterable $statusHelpers,
    ) {
        parent::__construct();
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
