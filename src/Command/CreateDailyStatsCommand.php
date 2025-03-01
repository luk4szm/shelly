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
    name: 'app:create:daily-stats',
    description: 'Downloads data from a given day and calculates statistics',
)]
class CreateDailyStatsCommand extends DailyStatsCommand
{
    public function __construct(
        #[AutowireIterator('app.shelly.daily_stats')]
        iterable                                    $dailyStatsCalculators,
        DeviceFinder                                $deviceFinder,
        private readonly DeviceDailyStatsRepository $statsRepository,
    ) {
        parent::__construct($dailyStatsCalculators, $deviceFinder);
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
        $io         = new SymfonyStyle($input, $output);
        $device     = $this->getDevice($input, $output);
        $calculator = $this->getDeviceDailyStatsCalculator($device);
        $date       = $input->getArgument('date')
            ? new \DateTimeImmutable($input->getArgument('date'))
            : new \DateTimeImmutable('yesterday');

        try {
            $newDailyStats = $calculator->calculateDailyStats($date);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::SUCCESS;
        }

        $dailyStats = $this->statsRepository->findForDeviceAndDay($device, $date) ?? new DeviceDailyStats($device, $date);
        $dailyStats->paste($newDailyStats);

        $this->statsRepository->save($dailyStats);

        $io->success(sprintf('Statistics for %s of %s were successfully calculated', $device, $date->format('Y-m-d')));

        return Command::SUCCESS;
    }
}
