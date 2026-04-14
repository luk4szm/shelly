<?php

namespace App\Service\Hydration;

use App\Entity\Process\HydrationProcess;
use App\Entity\Process\ScheduledProcess;
use App\Model\Device\Hydration\ValveDevice;
use App\Repository\Process\HydrationProcessRepository;
use App\Service\Processable\StartHydrationProcess;
use App\Service\Shelly\Switch\HydrationValveService;
use Doctrine\Common\Collections\ArrayCollection;

readonly class HydrationScheduleCreator
{
    public function __construct(
        private HydrationDeviceFinder      $hydrationDeviceFinder,
        private HydrationValveService      $hydrationValveService,
        private HydrationProcessRepository $hydrationProcessRepository,
    ) {
    }

    public function create(array $durations): void
    {
        $i                = 0;
        $currentTimePoint = new \DateTimeImmutable();
        $processes        = new ArrayCollection();

        foreach ($durations as $valveName => $duration) {
            if ($duration === '00:00' || $duration === '00:00:00') {
                continue;
            }

            // Calculate duration for current valve based on starting point
            $seconds = $this->calculateDurationInSeconds($currentTimePoint, $duration);
            $valve   = $this->hydrationDeviceFinder->getByName($valveName);

            if ($i === 0) {
                // First valve starts immediately
                $this->hydrationValveService->start($valve, $seconds);
            }

            // Future valves should be scheduled at $currentTimePoint for $seconds duration
            $process = $this->scheduleProcess($valve, $currentTimePoint, $seconds, $i === 0);

            $processes->add($process);

            // Move the starting point for the next valve
            // Current duration + 5 seconds gap to start exactly at the beginning of the next minute (XX:XX:00)
            $currentTimePoint = $currentTimePoint
                ->add(new \DateInterval("PT{$seconds}S"))
                ->add(new \DateInterval("PT5S"));

            $i++;
        }

        $this->hydrationProcessRepository->save($processes);
    }

    private function calculateDurationInSeconds(\DateTimeImmutable $startTime, string $durationString): int
    {
        // Split the duration string (format 00:15:00) into parts
        $parts   = explode(':', $durationString);
        $hours   = (int)($parts[0] ?? 0);
        $minutes = (int)($parts[1] ?? 0);
        $seconds = (int)($parts[2] ?? 0);

        // Calculate base duration in seconds from form input
        $baseDurationSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

        // Determine theoretical end time based on provided start time
        $theoreticalEndTime = $startTime->add(new \DateInterval("PT{$baseDurationSeconds}S"));

        // Get the second of the minute when it would theoretically end (0-59)
        $theoreticalEndSecond = (int)$theoreticalEndTime->format('s');

        // Calculate adjustment to ensure it always ends at the 55th second of the minute
        // If it ends at 56s, adjustment is -1. If it ends at 10s, adjustment is +45.
        $adjustment = 55 - $theoreticalEndSecond;

        // Return final duration, ensuring it's not negative
        return max(0, $baseDurationSeconds + $adjustment);
    }

    private function scheduleProcess(
        ValveDevice        $valve,
        \DateTimeImmutable $startTime,
        int                $duration,
        bool               $executed = false
    ): ScheduledProcess
    {
        $process = (new HydrationProcess())
            ->setName(StartHydrationProcess::NAME)
            ->setScheduledAt($startTime)
            ->setDuration($duration)
            ->setValve($valve->getName());

        if ($executed) {
            $process->setExecutedAt($startTime);
        }

        return $process;
    }
}
