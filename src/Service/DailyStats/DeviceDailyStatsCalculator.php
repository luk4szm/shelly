<?php

namespace App\Service\DailyStats;

use App\Entity\DeviceDailyStats;
use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Service\DeviceStatus\DeviceStatusHelper;
use App\Utils\Hook\HookDurationUtil;

abstract class DeviceDailyStatsCalculator implements DailyStatsCalculatorInterface
{
    /** @var array{Hook} */
    private array $hooks;

    public function __construct(
        private readonly HookRepository $hookRepository,
        private readonly DeviceStatusHelper $statusHelper,
    ) {}

    public function getCalculatorInstance(): static
    {
        return $this;
    }

    /**
     * Processes data from the sensor for the selected device on a given day
     *
     * @param \DateTimeInterface $date
     * @return DeviceDailyStats
     * @throws \DateMalformedStringException
     */
    public function calculateDailyStats(\DateTimeInterface $date): DeviceDailyStats
    {
        $this->getDailyHooks($date);

        if (empty($this->hooks)) {
            throw new \RuntimeException(sprintf('No data to process for device %s in %s', $this->getDeviceName(), $date->format('Y-m-d')));
        }

        $dailyStats = new DeviceDailyStats($this->getDeviceName(), $date);

        $activeTime       = 0; // seconds
        $runTime          = 0; // seconds
        $longestRunTime   = 0; // seconds
        $pauseTime        = 0; // seconds
        $longestPauseTime = 0; // seconds
        $energy           = 0; // Ws
        $inclusions       = 0;

        for ($i = 0; $i < count($this->hooks); $i++) {
            $isActive = $this->statusHelper->isActive($this->hooks[$i]);
            $duration = HookDurationUtil::calculateHookDuration($this->hooks[$i], $this->hooks[$i + 1] ?? null);
            $energy   += $this->hooks[$i]->getValue() * $duration;

            if ($isActive) {
                if (
                    $i !== 0
                    && !$this->statusHelper->isActive($this->hooks[$i - 1])
                ) {
                    $inclusions++;
                }

                $pauseTime      = 0;
                $runTime        += $duration;
                $activeTime     += $duration;
                $longestRunTime = max($longestRunTime, $runTime);
            } else {
                $runTime          = 0;
                $pauseTime        += $duration;
                $longestPauseTime = max($longestPauseTime, $pauseTime);
            }
        }

        return $dailyStats
            ->setEnergy(round($energy / 3600, 1)) // Wh
            ->setInclusions($inclusions)
            ->setTotalActiveTime($activeTime)
            ->setLongestRunTime($longestRunTime)
            ->setLongestPauseTime($longestPauseTime)
        ;
    }

    /**
     * @param \DateTimeInterface $date
     * @return void
     * @throws \DateMalformedStringException
     */
    private function getDailyHooks(\DateTimeInterface $date): void
    {
        $this->hooks ??= $this->hookRepository->findHooksByDeviceAndDate($this->getDeviceName(), $date);

        if (count($this->hooks) === 0) {
            return;
        }

        if ($this->hooks[0]->getCreatedAt()->format('H:i:s') === '00:00:00') {
            return;
        }

        if (null !== $lastHookOfDayBefore = $this->hookRepository->findLastHookOfDay($this->getDeviceName(), (clone $date)->modify("-1 day"))) {
            $virtualFirstHook = clone $lastHookOfDayBefore;
            $virtualFirstHook->setCreatedAt(new \DateTimeImmutable((clone $date)->format('Y-m-d')));

            array_unshift($this->hooks, $virtualFirstHook);
        }
    }

    abstract public function getDeviceName(): string;
}
