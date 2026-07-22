<?php

namespace App\Command;

use App\Service\DailyStats\DailyStatsCalculatorInterface;
use App\Service\Device\DeviceFinder;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

abstract class DailyStatsCommand extends DeviceCommand
{
    public function __construct(
        #[AutowireIterator('app.shelly.daily_stats')]
        protected readonly iterable $dailyStatsCalculators,
        DeviceFinder                $deviceFinder,
    ) {
        parent::__construct($deviceFinder);
    }

    protected function getDeviceDailyStatsCalculator(string $deviceName): DailyStatsCalculatorInterface
    {
        /** @var DailyStatsCalculatorInterface $statsCalculator */
        foreach ($this->dailyStatsCalculators as $statsCalculator) {
            if ($statsCalculator->supports($deviceName)) {
                return $statsCalculator;
            }
        }

        throw new \RuntimeException(sprintf('There is no configured daily stats calculator for the device called "%s"', $deviceName));
    }
}
