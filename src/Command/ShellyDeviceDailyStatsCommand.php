<?php

namespace App\Command;

use App\Entity\DeviceDailyStats;
use App\Repository\DeviceDailyStatsRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'shelly:device:daily-stats',
    description: 'Displays daily device statistics for a given period',
)]
class ShellyDeviceDailyStatsCommand extends ShellyCommand
{
    public function __construct(
        private readonly DeviceDailyStatsRepository $dailyStatsRepository,
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable $statusHelpers,
    ) {
        parent::__construct($statusHelpers);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('device', InputArgument::OPTIONAL, 'Device name')
            ->addArgument('date', InputArgument::OPTIONAL, 'The day you want to see statistics (YYYY-MM)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io           = new SymfonyStyle($input, $output);
        $device       = $this->getDevice($input, $output);
        $statusHelper = $this->getDeviceHelper($device);
        $date         = $input->getArgument('date')
            ? new \DateTimeImmutable($input->getArgument('date'))
            : new \DateTimeImmutable();

        $dailyStats = $this->dailyStatsRepository->findForDeviceAndMonth($device, $date);

        if (end($dailyStats)->getDate() !== $date) {
            $dailyStats[] = $statusHelper->calculateDailyStats($device, new \DateTime());
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
