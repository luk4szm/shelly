<?php

namespace App\Model;

use App\Utils\TimeUtils;

class DeviceStatus
{
    private Status $status;
    private bool   $isOngoing = false;
    private array  $hooks     = [];
    private ?int   $statusDuration; // seconds
    private float  $lastValue; // W
    private float  $usedEnergy; // Wh

    /**
     * PROPERTY GETTERS and SETTERS
     */

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function setIsOngoing(bool $isOngoing): static
    {
        $this->isOngoing = $isOngoing;

        return $this;
    }

    public function isOngoing(): bool
    {
        return $this->isOngoing;
    }

    public function getHooks(): array
    {
        return $this->hooks;
    }

    public function setHooks(array $hooks): self
    {
        $this->hooks = $hooks;

        return $this;
    }

    public function getStatusDuration(): ?int
    {
        return $this->statusDuration;
    }

    public function setStatusDuration(?int $statusDuration): self
    {
        $this->statusDuration = $statusDuration;

        return $this;
    }

    public function getLastValue(): float
    {
        return $this->lastValue;
    }

    public function setLastValue(float $lastValue): self
    {
        $this->lastValue = $lastValue;

        return $this;
    }

    public function setUsedEnergy(float $usedEnergy): self
    {
        $this->usedEnergy = $usedEnergy;

        return $this;
    }

    public function getUsedEnergy(): float
    {
        return $this->usedEnergy;
    }

    /**
     * CUSTOM METHODS
     */

    public function getStatusDurationReadable(): ?string
    {
        return $this->statusDuration ? TimeUtils::getReadableTime($this->statusDuration) : null;
    }

    public function getLastValueReadable(): string
    {
        return number_format($this->lastValue, 1) . ' W';
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->getHooks() ? $this->getHooks()[0]->getCreatedAt() : null;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->getStartTime()
            ? (clone $this->getStartTime())->modify("+ $this->statusDuration seconds")
            : null;
    }
}
