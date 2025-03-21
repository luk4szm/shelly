<?php

namespace App\Command;

use App\Entity\DeviceDailyStats;
use App\Repository\DeviceDailyStatsRepository;
use App\Service\Device\DeviceFinder;
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
class StatusHelperDeviceDailyStatsCommand extends DailyStatsCommand
{
    public function __construct(
        #[AutowireIterator('app.shelly.daily_stats')]
        iterable                                    $dailyStatsCalculators,
        DeviceFinder                                $deviceFinder,
        private readonly DeviceDailyStatsRepository $dailyStatsRepository,
    ) {
        parent::__construct($dailyStatsCalculators, $deviceFinder);
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
        $io         = new SymfonyStyle($input, $output);
        $device     = $this->getDevice($input, $output);
        $calculator = $this->getDeviceDailyStatsCalculator($device);
        $date       = $input->getArgument('date')
            ? new \DateTimeImmutable($input->getArgument('date'))
            : new \DateTimeImmutable();

        $dailyStats = $input->getArgument('date')
            ? $this->dailyStatsRepository->findForDeviceAndMonth($device, $date)
            : $this->dailyStatsRepository->findForDeviceFromLastDays($device);

        if (empty($dailyStats)) {
            $io->warning('No device daily stats found for given time period');

            return self::SUCCESS;
        }

        if (
            $input->getArgument('date') === null
            && end($dailyStats)->getDate() !== $date
        ) {
            try {
                $dailyStats[] = $calculator->calculateDailyStats(new \DateTime());
            } catch (\Exception $e) {}
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
