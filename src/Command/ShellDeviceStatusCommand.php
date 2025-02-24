<?php

namespace App\Command;

use App\Model\Status;
use App\Service\Hook\DeviceStatusHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'shelly:device:status',
    description: 'Show the current status of the device and provide information',
)]
class ShellDeviceStatusCommand extends Command
{
    public function __construct(
        private readonly DeviceStatusHelper $statusHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('device', InputArgument::REQUIRED, 'Device name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $device = $input->getArgument('device');

        if (null === $deviceStatus = $this->statusHelper->getStatus($device)) {
            $io->warning('No device information found');

            return self::SUCCESS;
        }

        $io->title(sprintf('[%s] Checking status of the "%s" device', (new \DateTime())->format('H:i:s'), $device));

        switch ($device) {
            case 'piec':
                $deviceStatus->getStatus() === Status::ACTIVE
                    ? $output->writeln(sprintf('Device status: <info>RUNNING</info> (%s)', $deviceStatus->getLastValueReadable()))
                    : $output->writeln(sprintf('Device status: <info>STANDBY</info> (%s)', $deviceStatus->getLastValueReadable()));
        }

        $output->writeln([sprintf('Current status duration: <info>%s</info>', $deviceStatus->getStatusDurationReadable()), '']);

        return Command::SUCCESS;
    }
}
