<?php

namespace App\Command;

use App\Entity\DeviceDailyStats;
use App\Repository\DeviceDailyStatsRepository;
use App\Repository\HookRepository;
use App\Service\Hook\DeviceRunningStats;
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
        private readonly HookRepository             $hookRepository,
        private readonly DeviceDailyStatsRepository $statsRepository,
        private readonly DeviceRunningStats         $deviceStats,
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

        $hooks = $this->hookRepository->findHooksByDeviceAndDate($device, $date);

        if (count($hooks) === 0) {
            $io->warning(sprintf('No data found for given date %s', $date->format('Y-m-d')));

            return self::SUCCESS;
        }

        if (null !== $lastHookOfDayBefore = $this->hookRepository->findLastHookOfDay($device, (clone $date)->modify("-1 day"))) {
            array_unshift($hooks, $lastHookOfDayBefore);
        }

        $this->deviceStats->process($date, $hooks);

        $dailyStats = new DeviceDailyStats(
            $device,
            $date,
            $this->deviceStats->getEnergy('Wh'),
            $this->deviceStats->getInclusionsCounter(),
            $this->deviceStats->getLongestRunTime(),
            $this->deviceStats->getLongestPauseTime(),
            $this->deviceStats->getRunningTime()
        );

        $this->statsRepository->save($dailyStats);

        $io->success(sprintf('Statistics for %s of %s were successfully calculated', $device, $date->format('Y-m-d')));

        return Command::SUCCESS;
    }
}
