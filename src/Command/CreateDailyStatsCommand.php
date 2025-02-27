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
    name: 'app:create:daily-stats',
    description: 'Downloads data from a given day and calculates statistics',
)]
class CreateDailyStatsCommand extends ShellyCommand
{
    public function __construct(
        private readonly DeviceDailyStatsRepository $statsRepository,
        #[AutowireIterator('app.shelly.device_status_helper')]
        iterable $statusHelpers,
    ) {
        parent::__construct($statusHelpers);
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
        $date         = $input->getArgument('date')
            ? new \DateTimeImmutable($input->getArgument('date'))
            : new \DateTimeImmutable('yesterday');

        try {
            $newDailyStats = $statusHelper->calculateDailyStats($device, $date);
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
