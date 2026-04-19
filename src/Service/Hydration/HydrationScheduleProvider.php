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
//        $this->getCurrentStatuses();
        $this->getScheduledProcesses();

        return $this->plan;
    }

    public function getCurrentStatuses(): void
    {
        $currentStatuses = $this->logRepository->findActiveLogs();

        /** @var HydrationLog $currentStatus */
        foreach ($currentStatuses as $currentStatus) {
            $valve = $this->deviceFinder->getByName($currentStatus->getValve());
            $plan  = (new HydrationPlanDto($valve))
                ->setStartsAt($currentStatus->getStartsAt());

            $this->plan->add($plan);
        }
    }

    public function getScheduledProcesses(): void
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
