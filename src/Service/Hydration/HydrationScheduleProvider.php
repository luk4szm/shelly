<?php

namespace App\Service\Hydration;

use App\Entity\HydrationLog;
use App\Entity\Process\HydrationProcess;
use App\Model\Hydration\HydrationPlanDto;
use App\Repository\HydrationLogRepository;
use App\Repository\Process\HydrationProcessRepository;
use Doctrine\Common\Collections\ArrayCollection;

class HydrationScheduleProvider
{
    private ArrayCollection $plan;

    public function __construct(
        private readonly HydrationLogRepository     $logRepository,
        private readonly HydrationProcessRepository $processRepository,
        private readonly HydrationDeviceFinder      $deviceFinder,
    ) {}

    public function getPlan(): ArrayCollection
    {
        $this->plan = new ArrayCollection();

        $this->getActiveRuns();
        $this->getQueueRuns();

        $this->mergePlans();

        return $this->plan;
    }

    public function getHistory(): ArrayCollection
    {
        $this->plan = new ArrayCollection();

        $this->getPreviousRuns();

        return $this->plan;
    }

    private function getPreviousRuns(): void
    {
        $previousRuns = $this->logRepository->findPreviousRuns();

        /** @var HydrationLog $currentStatus */
        foreach ($previousRuns as $log) {
            $valve = $this->deviceFinder->getByName($log->getValve());
            $plan  = (new HydrationPlanDto($valve))
                ->setStartsAt($log->getStartsAt())
                ->setDuration($log->getDuration())
                ->setEndsAt($log->getEndsAt());

            $this->plan->add($plan);
        }
    }

    private function getActiveRuns(): void
    {
        $activeRuns = $this->logRepository->findActiveLogs();

        /** @var HydrationLog $log */
        foreach ($activeRuns as $log) {
            $valve = $this->deviceFinder->getByName($log->getValve());
            $plan  = (new HydrationPlanDto($valve))->setStartsAt($log->getStartsAt());

            $scheduledProcess = $this->processRepository->findByValveAndStartMinute($log->getValve(), $log->getStartsAt());

            if ($scheduledProcess) {
                $duration = $scheduledProcess->getDuration();
                $endsAt   = (clone $log->getStartsAt())->modify("+{$duration} seconds");

                $plan->setDuration($duration)->setScheduledEndAt($endsAt);
            }

            $this->plan->add($plan);
        }
    }

    /**
     * Fetches processes from the hydration_process table (the queue).
     * These can be both future (pending) and current (active) processes.
     */
    private function getQueueRuns(): void
    {
        $scheduledProcesses = $this->processRepository->findPendingAndActiveProcesses();

        /** @var HydrationProcess $scheduledProcess */
        foreach ($scheduledProcesses as $scheduledProcess) {
            $valve = $this->deviceFinder->getByName($scheduledProcess->getValve());
            $plan  = (new HydrationPlanDto($valve))
                ->setScheduledStartAt($scheduledProcess->getScheduledAt())
                ->setDuration($scheduledProcess->getDuration())
                ->setScheduledEndAt(
                    (clone $scheduledProcess->getScheduledAt())
                        ->modify("+{$scheduledProcess->getDuration()} seconds")
                );

            $this->plan->add($plan);
        }
    }

    private function mergePlans(): void
    {
        $uniquePlans = [];

        foreach ($this->plan as $plan) {
            /** @var HydrationPlanDto $plan */
            $valveName = $plan->getValve()->getName();

            // The key is the valve name and the rounded start time (actual or scheduled).
            // Rounding to the minute avoids issues with a few seconds difference between DB and Shelly hook.
            $startTime = $plan->getStartsAt() ?? $plan->getScheduledStartAt();
            $key       = $valveName . '_' . $startTime->format('Y-m-d_H:i');

            if (!isset($uniquePlans[$key])) {
                $uniquePlans[$key] = $plan;
                continue;
            }

            // If a duplicate is found, prefer the one that already has an actual start date (startsAt).
            // This means it's an entry from logs (confirmed by the device hook).
            if ($plan->getStartsAt() !== null) {
                $uniquePlans[$key] = $plan;
            }
        }

        // Sort by start time
        usort($uniquePlans, function (HydrationPlanDto $a, HydrationPlanDto $b) {
            $dateA = $a->getStartsAt() ?? $a->getScheduledStartAt();
            $dateB = $b->getStartsAt() ?? $b->getScheduledStartAt();
            return $dateA <=> $dateB;
        });

        $this->plan = new ArrayCollection($uniquePlans);
    }
}
