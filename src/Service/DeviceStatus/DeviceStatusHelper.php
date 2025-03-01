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
    private int     $element = 0;

    public function __construct(
        protected readonly HookRepository $hookRepository,
    ) {}

    public function getHistory(int $elements = 2): ?ArrayCollection
    {
        $this->hooks ??= $this->hookRepository->findLastActiveByDevice($this->getDeviceName());

        if (empty($this->hooks)) {
            return null;
        }

        $history = new ArrayCollection();

        for ($i = 0; $i < $elements; $i++) {
            $history->add($this->getStatus());

            $this->element++;
        }

        return $history;
    }

    public function getStatus(): ?DeviceStatus
    {
        $this->hooks ??= $this->hookRepository->findLastActiveByDevice($this->getDeviceName());

        if (empty($this->hooks)) {
            return null;
        }

        return (new DeviceStatus())
            ->setStatus($this->isActive($this->hooks[$this->element]) ? Status::ACTIVE : Status::INACTIVE)
            ->setLastValue($this->hooks[$this->element]->getValue())
            ->setStatusDuration($this->getDeviceStatusUnchangedDuration())
        ;
    }

    public function getStatusHelperInstance(): static
    {
        return $this;
    }

    private function getDeviceStatusUnchangedDuration(): int
    {
        $reference = $this->element === 0 ? new \DateTime() : $this->hooks[$this->element - 1]->getCreatedAt();
        $interval  = $reference->diff($this->getFirstHookOfCurrentStatus()->getCreatedAt());

        return $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    private function getFirstHookOfCurrentStatus(): Hook
    {
        $currentStatus = $this->isActive($this->hooks[$this->element]);

        for ($i = $this->element + 1; $i < count($this->hooks); $i++) {
            if ($currentStatus !== $this->isActive($this->hooks[$i])) {
                $this->element = $i - 1;

                return $this->hooks[$i - 1];
            }
        }

        throw new \RuntimeException('First hook of actual status not found');
    }
}
