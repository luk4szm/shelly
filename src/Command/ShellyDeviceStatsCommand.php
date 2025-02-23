<?php

namespace App\Command;

use App\Service\DeviceDailyStatsCalculator;
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
        private readonly DeviceDailyStatsCalculator $deviceStats,
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

        try {
            $dailyStats = $this->deviceStats->process($device, $date);
        } catch (\Exception $e) {
            $io->warning($e->getMessage());

            return Command::SUCCESS;
        }

        $output->writeln([
            sprintf('Total time of active work: <info>%s</info>', $dailyStats->getTotalActiveTimeReadable()),
            sprintf('Longest run time: <info>%s</info>', $dailyStats->getLongestRunTimeReadable()),
            sprintf('Longest pause time: <info>%s</info>', $dailyStats->getLongestPauseTimeReadable()),
            sprintf('Total used energy: <info>%.1f Wh</info>', $dailyStats->getEnergy()),
            sprintf('Number of active cycles: <info>%d</info>', $dailyStats->getInclusions()),
            ''
        ]);

        return Command::SUCCESS;
    }
}
