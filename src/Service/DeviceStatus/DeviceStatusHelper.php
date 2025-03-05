<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use App\Model\DeviceStatus;
use App\Model\Status;
use App\Repository\HookRepository;
use Doctrine\Common\Collections\ArrayCollection;

abstract class DeviceStatusHelper implements DeviceStatusHelperInterface
{
    /** @var array{Hook} */
    protected array $hooks;
    private int     $pointer = 0;
    private bool    $isFirst = true;

    public function __construct(
        protected readonly HookRepository $hookRepository,
    ) {
    }

    public function getHistory(int $elements = 0): ?ArrayCollection
    {
        $this->hooks ??= $this->hookRepository->findLastActiveByDevice($this->getDeviceName());

        if (empty($this->hooks)) {
            return null;
        }

        $history = new ArrayCollection();

        if ($elements > 0) {
            for ($i = 0; $i < $elements; $i++) {
                if (null === $deviceStatus = $this->getStatus()) {
                    break;
                }

                $history->add($deviceStatus);

                $this->setPointerOnNextHook($deviceStatus);

                $this->isFirst = false;
            }
        } else {
            while ($this->pointer <= count($this->hooks)) {
                if (null === $deviceStatus = $this->getStatus()) {
                    break;
                }

                $history->add($deviceStatus);

                $this->setPointerOnNextHook($deviceStatus);

                $this->isFirst = false;
            }
        }

        return $history;
    }

    public function getStatus(): ?DeviceStatus
    {
        $this->hooks ??= $this->hookRepository->findLastActiveByDevice($this->getDeviceName());

        if (
            empty($this->hooks)
            || !isset($this->hooks[$this->pointer])
            || null === $firstHookNo = $this->findFirstHookOfCurrentStatus()
        ) {
            return null;
        }

        $lastHookNo  = $this->pointer;
        $statusHooks = $this->isFirst
            ? array_reverse(array_slice($this->hooks, $lastHookNo, $firstHookNo + 1))
            : array_reverse(array_slice($this->hooks, $lastHookNo - 1, $firstHookNo - $lastHookNo + 2));

        return (new DeviceStatus())
            ->setIsOngoing($this->isFirst)
            ->setStatus($this->isActive($this->hooks[$firstHookNo]) ? Status::ACTIVE : Status::INACTIVE)
            ->setHooks($statusHooks)
            ->setLastValue(end($statusHooks)->getValue())
            ->setStatusDuration($this->countStatusDuration($statusHooks));
    }

    public function getStatusHelperInstance(): static
    {
        return $this;
    }

    private function countStatusDuration(array $hooks): int
    {
        $reference = $this->pointer === 0 ? new \DateTime() : end($hooks)->getCreatedAt();
        $interval  = $reference->diff($hooks[0]->getCreatedAt());

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
        $this->pointer = $this->isFirst
            ? count($deviceStatus->getHooks())
            : $this->pointer + count($deviceStatus->getHooks()) - 1;
    }
}
