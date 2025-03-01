<?php

namespace App\Command;

use App\Service\Device\DeviceFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class DeviceCommand extends Command
{
    public function __construct(private readonly DeviceFinder $deviceFinder)
    {
        parent::__construct();
    }

    protected function getDevice(InputInterface $input, OutputInterface $output): string
    {
        if (
            (null !== $device = $input->getArgument('device'))
            && in_array($device, $this->deviceFinder->getDeviceNames(), true)
        ) {
            return $device;
        }

        $io = new SymfonyStyle($input, $output);

        return $io->choice('Please select the device', $this->deviceFinder->getDeviceNames(), 0);
    }
}
