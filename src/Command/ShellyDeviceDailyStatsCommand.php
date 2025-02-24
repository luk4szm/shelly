<?php

namespace App\Command;

use App\Entity\DeviceDailyStats;
use App\Repository\DeviceDailyStatsRepository;
use App\Service\DeviceDailyStatsCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'shelly:device:daily-stats',
    description: 'Displays daily device statistics for a given period',
)]
class ShellyDeviceDailyStatsCommand extends Command
{
    public function __construct(
        private readonly DeviceDailyStatsRepository $dailyStatsRepository,
        private readonly DeviceDailyStatsCalculator $dailyStatsCalculator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('device', InputArgument::REQUIRED, 'Device name')
            ->addArgument('date', InputArgument::OPTIONAL, 'The day you want to see statistics (YYYY-MM)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $device = $input->getArgument('device');
        $date   = $input->getArgument('date')
            ? new \DateTimeImmutable($input->getArgument('date'))
            : new \DateTimeImmutable();

        $dailyStats = $this->dailyStatsRepository->findForDeviceAndMonth($device, $date);

        if (end($dailyStats)->getDate() !== $date) {
            $dailyStats[] = $this->dailyStatsCalculator->process($device, new \DateTime());
        }

        $io->table(
            ['date', 'energy', 'inclusions', 'running time', 'longest run', 'longest pause'],
            array_map(function (DeviceDailyStats $stats) {
                return [
                    $stats->getDate()->format('Y-m-d'),
                    sprintf('%.1f Wh', $stats->getEnergy()),
                    $stats->getInclusions(),
                    $stats->getTotalActiveTimeReadable(),
                    $stats->getLongestRunTimeReadable(),
                    $stats->getLongestPauseTimeReadable(),
                ];
            }, $dailyStats)
        );

        return Command::SUCCESS;
    }
}
