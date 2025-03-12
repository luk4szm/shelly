<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\DateRange;
use App\Model\DeviceStatus;
use App\Model\Status;
use App\Repository\HookRepository;
use Doctrine\Common\Collections\ArrayCollection;

abstract class DeviceStatusHelper implements DeviceStatusHelperInterface
{
    /** @var array{Hook} */
    protected array    $hooks;
    private ?DateRange $dateRange;
    private int        $pointer = 0;

    public function __construct(
        protected readonly HookRepository $hookRepository,
    ) {
    }

    public function getHistory(
        int       $historyLimit = 0,
        DateRange $dateRange    = null,
        bool      $grouped      = false,
    ): ?ArrayCollection
    {
        $this->dateRange = $dateRange;

        if (empty($this->hooks = $this->getHooks())) {
            return null;
        }

        $history       = new ArrayCollection();
        $maxIterations = ($historyLimit > 0) ? $historyLimit : count($this->hooks);

        for ($i = 0; $i < $maxIterations; $i++) {
            if (null === $deviceStatus = $this->getStatus()) {
                break;
            }

            $history->add($deviceStatus);

            $this->setPointerOnNextHook($deviceStatus);
        }

        return $grouped ? $this->groupHistory($history) : $history;
    }

    public function getStatusHelperInstance(): static
    {
        return $this;
    }

    private function getStatus(): ?DeviceStatus
    {
        if (
            empty($this->hooks)
            || !isset($this->hooks[$this->pointer])
            || null === $firstHookNo = $this->findFirstHookOfCurrentStatus()
        ) {
            return null;
        }

        $lastHookNo  = $this->pointer;
        $statusHooks = $this->pointer === 0
            ? array_reverse(array_slice($this->hooks, $lastHookNo, $firstHookNo + 1))
            : array_reverse(array_slice($this->hooks, $lastHookNo - 1, $firstHookNo - $lastHookNo + 2));

        return (new DeviceStatus())
            ->setIsOngoing($this->pointer === 0)
            ->setStatus($this->isActive($this->hooks[$firstHookNo]) ? Status::ACTIVE : Status::INACTIVE)
            ->setHooks($statusHooks)
            ->setLastValue(end($statusHooks)->getValue())
            ->setStatusDuration($this->countStatusDuration($statusHooks));
    }

    private function groupHistory(ArrayCollection $history): ArrayCollection
    {
        $i = 0;

        /** @var DeviceStatus $status */
        foreach ($history as $status) {
            if ($status->getStatus() === Status::INACTIVE) {
                $i++;
            }

            $grouped[$i][$status->getStatus()->value] = $status;
        }

        return new ArrayCollection(array_values($grouped ?? []));
    }

    private function getHooks(): array
    {
        return $this->dateRange
            ? $this->hookRepository->findHooksByDeviceForDateRange($this->getDeviceName(), $this->dateRange)
            : $this->hookRepository->findLastPowerHookForDevice($this->getDeviceName());
    }

    private function countStatusDuration(array $hooks): int
    {
        $reference = $this->pointer === 0
            ? $this->dateRange ? $this->dateRange->getTo() : new \DateTime()
            : end($hooks)->getCreatedAt();

        $interval = $reference->diff($hooks[0]->getCreatedAt());

        return $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    private function findFirstHookOfCurrentStatus(): ?int
    {
        for ($i = $this->pointer; $i < count($this->hooks); $i++) {
            if ($this->isActive($this->hooks[$this->pointer]) !== $this->isActive($this->hooks[$i])) {
                // the state changes
                return $i - 1;
            }
        }

        // no change in the state was found
        return null;
    }

    private function setPointerOnNextHook(DeviceStatus $deviceStatus): void
    {
        $this->pointer = $this->pointer === 0
            ? count($deviceStatus->getHooks())
            : $this->pointer + count($deviceStatus->getHooks()) - 1;
    }
}
