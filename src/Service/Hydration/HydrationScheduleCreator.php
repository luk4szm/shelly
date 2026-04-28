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
        $startAt          = !empty($durations['hydration_start_time']) ? new \DateTimeImmutable($durations['hydration_start_time']) : null;
        $multiplicity     = (int)$durations['multiplicity'];
        $currentTimePoint = $startAt ?? new \DateTimeImmutable();
        $processes        = new ArrayCollection();

        unset($durations['hydration_start_time']);
        unset($durations['multiplicity']);

        for ($m = 0; $m < $multiplicity; $m++) {
            foreach ($durations as $valveName => $duration) {
                if ($duration === '0' || $duration === 0) {
                    continue;
                }

                // Calculate duration to always end at the 55th second of a minute
                $seconds = $this->calculateDurationInSeconds($currentTimePoint, (string)$duration);
                $valve   = $this->hydrationDeviceFinder->getByName($valveName);

                if ($i === 0 && $startAt === null) {
                    // First valve starts immediately only if start time was not provided
                    $this->hydrationValveService->start($valve, $seconds);
                }

                // Future valves should be scheduled at $currentTimePoint for $seconds duration
                // If $startAt is provided, the first valve (i === 0) is also just scheduled, not marked as executed
                $process = $this->scheduleProcess($valve, $currentTimePoint, $seconds, ($i === 0 && $startAt === null));

                $processes->add($process);

                // Move the starting point for the next valve
                // End at 55s + 5s gap = start exactly at XX:XX:00
                $currentTimePoint = $currentTimePoint
                    ->add(new \DateInterval("PT{$seconds}S"))
                    ->add(new \DateInterval("PT5S"));

                $i++;
            }
        }

        $this->hydrationProcessRepository->save($processes);
    }

    private function calculateDurationInSeconds(\DateTimeImmutable $startTime, string $durationString): int
    {
        // Planned duration in seconds (e.g., 15 min = 900s)
        $plannedDurationSeconds = (int)$durationString * 60;

        // Theoretical end time (e.g., 14:30:35 + 15min = 14:45:35)
        $theoreticalEndTime = $startTime->add(new \DateInterval("PT{$plannedDurationSeconds}S"));

        // Check at which second it ends (0-59)
        $theoreticalEndSecond = (int)$theoreticalEndTime->format('s');

        // We want to end at the 55th second of the minute.
        // Calculate the adjustment needed to reach the 55th second.
        $adjustment = 55 - $theoreticalEndSecond;

        // If the adjustment is positive (e.g., ends at :35, needs +20s to reach :55),
        // it would exceed the requested duration.
        // We subtract 60 seconds to ensure the final duration is slightly less than requested,
        // providing a buffer before the next full minute.
        if ($adjustment > 0) {
            $adjustment -= 60;
        }

        // Example: Start at 14:30:35, Plan 15 min (900s).
        // Theoretical end: 14:45:35. Adjustment: 55 - 35 = 20.
        // Since 20 > 0, adjustment becomes 20 - 60 = -40.
        // Final duration: 900 - 40 = 860s (14min 20s).
        // 14:30:35 + 14:20 = 14:44:55. PERFECT.

        return max(0, $plannedDurationSeconds + $adjustment);
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
