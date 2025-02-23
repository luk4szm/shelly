<?php

namespace App\Service;

use App\Repository\HookRepository;
use App\Service\Hook\DeviceStatusHelper;

class DeviceDailyStatsCalculator
{
    private array $hooks;
    private float $energy            = 0; // Ws
    private int   $runningTime       = 0; // seconds
    private int   $longestRun        = 0; // seconds
    private int   $longestPause      = 0; // seconds
    private int   $inclusionsCounter = 0;

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
     * @return void
     * @throws \DateMalformedStringException
     */
    public function process(string $device, \DateTimeInterface $date): void
    {
        $this->getDailyHooks($device, $date);

        if (empty($this->hooks)) {
            throw new \RuntimeException(sprintf('No data to process for device %s in %s', $device, $date->format('Y-m-d')));
        }

        $runTime     = 0;
        $pauseTime   = 0;

        for ($i = 0; $i < count($this->hooks); $i++) {
            $isActive = $this->statusHelper->isActive('piec', $this->hooks[$i]);
            $duration = $this->statusHelper->calculateHookDuration($this->hooks[$i], $this->hooks[$i+1] ?? null);

            $this->energy += $this->hooks[$i]->getValue() * $duration;

            if ($isActive) {
                if (
                    $i !== 0
                    && !$this->statusHelper->isActive('piec', $this->hooks[$i - 1])
                ) {
                    $this->inclusionsCounter++;
                }

                $pauseTime         = 0;
                $runTime           += $duration;
                $this->runningTime += $duration;

                if ($runTime > $this->longestRun) {
                    $this->longestRun = $runTime;
                }
            } else {
                $runTime   = 0;
                $pauseTime += $duration;

                if ($pauseTime > $this->longestPause) {
                    $this->longestPause = $pauseTime;
                }
            }
        }
    }

    public function getRunningTime(): int
    {
        return $this->runningTime;
    }

    public function getLongestRunTime(): int
    {
        return $this->longestRun;
    }

    public function getLongestPauseTime(): int
    {
        return $this->longestPause;
    }

    public function getEnergy(string $unit = 'kWh'): float
    {
        return match ($unit) {
            'Wh'    => round($this->energy / 3600, 1),
            default => round($this->energy / 3600000, 2), // kWh
        };
    }

    public function getInclusionsCounter(): int
    {
        return $this->inclusionsCounter;
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

        if (null !== $lastHookOfDayBefore = $this->hookRepository->findLastHookOfDay($device, (clone $date)->modify("-1 day"))) {
            array_unshift($this->hooks, $lastHookOfDayBefore);
        }

         if ($date->format("Y-z") !== $this->hooks[0]->getCreatedAt()->format("Y-z")) {
            $this->hooks[0]->setCreatedAt((clone $date)->setTime(0, 0));
        }
    }
}
