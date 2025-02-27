<?php

namespace App\Command;

use App\Service\DeviceStatusHelper\DeviceStatusHelperInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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

    protected function getDevice(InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');

        if (null !== $device = $input->getArgument('device')) {
            return $device;
        }

        return $helper->ask(
            $input,
            $output,
            new ChoiceQuestion('Please select the device', self::getDevices())
        );
    }

    private function getDevices(): array
    {
        /** @var DeviceStatusHelperInterface $helper */
        foreach ($this->statusHelpers as $helper) {
            $devices[] = $helper->getDeviceName();
        }

        return $devices ?? [];
    }
}
