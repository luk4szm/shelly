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
    ) {
        $this->plan = new ArrayCollection();
    }

    public function getPlan(): ArrayCollection
    {
        $this->getPreviousRuns();
        $this->getActiveRuns();
        $this->getScheduledProcesses();

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

    private function getScheduledProcesses(): void
    {
        $scheduledProcesses = $this->processRepository->findScheduledProcess();

        /** @var HydrationProcess $scheduledProcess */
        foreach ($scheduledProcesses as $scheduledProcess) {
            $valve = $this->deviceFinder->getByName($scheduledProcess->getValve());
            $plan  = (new HydrationPlanDto($valve))
                ->setScheduledStartAt($scheduledProcess->getScheduledAt())
                ->setDuration($scheduledProcess->getDuration())
                ->setScheduledEndAt($scheduledProcess->getScheduledAt()->modify("+{$scheduledProcess->getDuration()} seconds"));

            $this->plan->add($plan);
        }
    }
}
