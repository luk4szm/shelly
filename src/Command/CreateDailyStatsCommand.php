<?php

namespace App\Command;

use App\Entity\DeviceDailyStats;
use App\Repository\DeviceDailyStatsRepository;
use App\Service\Device\DeviceFinder;
use App\Service\DeviceStatus\DeviceStatusHelperInterface;
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
            ->addArgument('date', InputArgument::OPTIONAL, 'The day you want to see statistics (YYYY-MM-DD)')
            ->addArgument('device', InputArgument::OPTIONAL, 'Device name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $device = $input->getArgument('device') ? $this->getDevice($input, $output) : null;
        $date   = $input->getArgument('date')
            ? new \DateTimeImmutable($input->getArgument('date'))
            : new \DateTimeImmutable('yesterday');

        /** @var DeviceStatusHelperInterface $helper */
        foreach ($this->dailyStatsCalculators as $statsCalculator) {
            if (
                null !== $device
                && !$statsCalculator->supports($device)
            ) {
                continue;
            }

            try {
                $newDailyStats = $statsCalculator->calculateDailyStats($date);
            } catch (\RuntimeException $e) {
                $io->warning($e->getMessage());

                continue;
            }

            $dailyStats = $this->statsRepository->findForDeviceAndDay($statsCalculator->getDeviceName(), $date)
                          ?? new DeviceDailyStats($statsCalculator->getDeviceName(), $date);
            $dailyStats->paste($newDailyStats);

            $this->statsRepository->save($dailyStats);
        }

        return Command::SUCCESS;
    }
}
