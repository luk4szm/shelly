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
    name: 'app:create:daily-stats',
    description: 'Downloads data from a given day and calculates statistics',
)]
class CreateDailyStatsCommand extends Command
{
    public function __construct(
        private readonly DeviceDailyStatsRepository $statsRepository,
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
            : new \DateTimeImmutable('yesterday');

        try {
            $newDailyStats = $this->deviceStats->process($device, $date);
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
