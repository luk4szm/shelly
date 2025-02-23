<?php

namespace App\Service;

use App\Entity\DeviceDailyStats;
use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Service\Hook\DeviceStatusHelper;

class DeviceDailyStatsCalculator
{
    /** @var array{Hook} */
    private array $hooks = [];

    public function __construct(
        private readonly DeviceStatusHelper $statusHelper,
        private readonly HookRepository     $hookRepository,
    ) {
    }

    /**
     * Processes data from the sensor for the selected device on a given day
     *
     * @param string             $device
     * @param \DateTimeInterface $date
     * @return DeviceDailyStats
     * @throws \DateMalformedStringException
     */
    public function process(string $device, \DateTimeInterface $date): DeviceDailyStats
    {
        $this->getDailyHooks($device, $date);

        if (empty($this->hooks)) {
            throw new \RuntimeException(sprintf('No data to process for device %s in %s', $device, $date->format('Y-m-d')));
        }

        $dailyStats = new DeviceDailyStats($device, $date);

        $activeTime       = 0; // seconds
        $runTime          = 0; // seconds
        $longestRunTime   = 0; // seconds
        $pauseTime        = 0; // seconds
        $longestPauseTime = 0; // seconds
        $energy           = 0; // Ws
        $inclusions       = 0;

        for ($i = 0; $i < count($this->hooks); $i++) {
            $isActive = $this->statusHelper->isActive('piec', $this->hooks[$i]);
            $duration = $this->statusHelper->calculateHookDuration($this->hooks[$i], $this->hooks[$i + 1] ?? null);
            $energy   += $this->hooks[$i]->getValue() * $duration;

            if ($isActive) {
                if (
                    $i !== 0
                    && !$this->statusHelper->isActive('piec', $this->hooks[$i - 1])
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
     * @param string             $device
     * @param \DateTimeInterface $date
     * @return void
     */
    private function getDailyHooks(string $device, \DateTimeInterface $date): void
    {
        $this->hooks = $this->hookRepository->findHooksByDeviceAndDate($device, $date);

        if (count($this->hooks) === 0) {
            return;
        }

        if ($this->hooks[0]->getCreatedAt()->format('H:i:s') === '00:00:00') {
            return;
        }

        if (null !== $lastHookOfDayBefore = $this->hookRepository->findLastHookOfDay($device, (clone $date)->modify("-1 day"))) {
            $virtualFirstHook = clone $lastHookOfDayBefore;
            $virtualFirstHook->setCreatedAt((clone $date)->setTime(0, 0));

            array_unshift($this->hooks, $virtualFirstHook);
        }
    }
}
