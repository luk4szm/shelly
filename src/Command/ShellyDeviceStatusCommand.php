<?php

namespace App\Command;

use App\Model\DeviceStatus;
use App\Service\Device\DeviceFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'shelly:device:status',
    description: 'Show the current status of the device and provide information',
)]
class ShellyDeviceStatusCommand extends StatusHelperCommand
{
    public function __construct(
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable     $statusHelpers,
        DeviceFinder $deviceFinder,
    ) {
        parent::__construct($statusHelpers, $deviceFinder);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('device', InputArgument::OPTIONAL, 'Device name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io           = new SymfonyStyle($input, $output);
        $device       = $this->getDevice($input, $output);
        $statusHelper = $this->getDeviceHelper($device);

        if (null === $deviceHistory = $statusHelper->getHistory(2)) {
            $io->warning('No device information found');

            return self::SUCCESS;
        }

        $io->title(sprintf('[%s] Checking status of the "%s" device', (new \DateTime())->format('H:i:s'), $device));

        /** @var DeviceStatus $actualStatus */
        $actualStatus   = $deviceHistory->first();
        $previousStatus = $deviceHistory->next();

        $output->writeln(
            sprintf('Device status: <info>%s</info> (%s)', strtoupper($actualStatus->getStatus()->value), $actualStatus->getLastValueReadable())
        );

        if ($actualStatus->getStatusDuration() !== null) {
            $output->writeln(
                sprintf('Current status duration: <info>%s</info>', $actualStatus->getStatusDurationReadable())
            );
        }

        if ($previousStatus !== null) {
            $output->writeln(
                sprintf('Previous status duration: <info>%s</info>', $previousStatus->getStatusDurationReadable())
            );
        }

        $output->writeln('');

        return Command::SUCCESS;
    }
}
