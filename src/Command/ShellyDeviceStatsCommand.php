<?php

namespace App\Command;

use App\Repository\HookRepository;
use App\Service\Hook\DeviceRunningStats;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'shelly:device:stats',
    description: 'Returns the work statistics of the device for a given day',
)]
class ShellyDeviceStatsCommand extends Command
{
    public function __construct(
        private readonly HookRepository     $repository,
        private readonly DeviceRunningStats $deviceStats,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('device', InputArgument::REQUIRED, 'Device name')
            ->addArgument('date', InputArgument::OPTIONAL, 'The day you want to see statistics (YYYY-MM-DD)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $device = $input->getArgument('device');
        $date   = $input->getArgument('date')
            ? new \DateTimeImmutable($input->getArgument('date'))
            : new \DateTimeImmutable();

        $io->title(sprintf(
            'Retrieving the statistics of the day (%s) for the "%s" device',
            $date->format('Y-m-d'),
            $device
        ));

        $hooks = $this->repository->findHooksByDeviceAndDate($device, $date);

        if (count($hooks) === 0) {
            $io->warning(sprintf('No data found for given date %s', $date->format('Y-m-d')));

            return self::SUCCESS;
        }

        if (null !== $lastHookOfDayBefore = $this->repository->findLastHookOfDay($device, (clone $date)->modify("-1 day"))) {
            array_unshift($hooks, $lastHookOfDayBefore);
        }

        $this->deviceStats->process($date, $hooks);

        $output->writeln([
            sprintf('Total time of active work: <info>%s</info>', $this->deviceStats->getRunningTime()),
            sprintf('Total used energy: <info>%.1f Wh</info>', $this->deviceStats->getEnergy('Wh')),
            sprintf('Number of active cycles: <info>%d</info>', $this->deviceStats->getInclusionsCounter()),
            ''
        ]);

        return Command::SUCCESS;
    }
}
