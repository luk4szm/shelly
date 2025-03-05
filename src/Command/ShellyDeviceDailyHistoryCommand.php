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
    name: 'shelly:device:history',
    description: 'Returns the history of starting the device on a given day',
)]
class ShellyDeviceDailyHistoryCommand extends StatusHelperCommand
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
            ->addArgument('device', InputArgument::OPTIONAL, 'Device name')
            ->addArgument('date', InputArgument::OPTIONAL, 'The day you want to see statistics (YYYY-MM-DD)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io           = new SymfonyStyle($input, $output);
        $device       = $this->getDevice($input, $output);
        $statusHelper = $this->getDeviceHelper($device);

        if (null === $deviceHistory = $statusHelper->getHistory()) {
            $io->warning('No device information found');

            return self::SUCCESS;
        }

        $io->title(sprintf('[%s] Retrieving history of the "%s" device', (new \DateTime())->format('H:i:s'), $device));

        dump(array_map(function (DeviceStatus $deviceStatus) {
            return $deviceStatus->getStatus()->value . ' ' .$deviceStatus->getStatusDurationReadable();
        }, $deviceHistory->toArray()));

        // TODO: handle and return results

        return Command::SUCCESS;
    }
}
