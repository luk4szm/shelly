<?php

namespace App\Command;

use App\Entity\Hook;
use App\Repository\HookRepository;
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
        private readonly HookRepository     $hookRepository,
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
        $hooks  = $this->hookRepository->findCurrentPowerByDevice($device);

        $io->title(sprintf('Checking status of the "%s" device', $device));

        if (count($hooks) === 0) {
            $io->warning('No device information found');

            return self::SUCCESS;
        }

        /** @var Hook $lastHook */
        $lastHook = $hooks[0];
        $isActive = $this->statusHelper->isActive($device, $lastHook);

        switch ($device) {
            case 'piec':
                $isActive
                    ? $output->writeln(sprintf('Device status: <info>RUNNING</info> (%.1f W)', $lastHook->getValue()))
                    : $output->writeln(sprintf('Device status: <info>STANDBY</info> (%.1f W)', $lastHook->getValue()));
        }

        $duration = $this->statusHelper->getDeviceStatusUnchangedDuration($hooks);

        $output->writeln([sprintf('Last change of status %d minutes ago', $duration), '']);

        return Command::SUCCESS;
    }
}
