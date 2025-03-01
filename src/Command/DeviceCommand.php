<?php

namespace App\Command;

use App\Service\Device\DeviceFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

abstract class DeviceCommand extends Command
{
    public function __construct(private readonly DeviceFinder $deviceFinder,)
    {
        parent::__construct();
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
            new ChoiceQuestion('Please select the device', $this->deviceFinder->getDeviceNames())
        );
    }
}
