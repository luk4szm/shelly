<?php

namespace App\Model;

use App\Utils\TimeUtils;

class DeviceStatus
{
    private Status $status;
    private array  $hooks;
    private ?int   $statusDuration; // seconds
    private float  $lastValue; // W

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): self
    {
        $this->status = $status;

        return $this;
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

    public function getStatusDurationReadable(): ?string
    {
        return $this->statusDuration ? TimeUtils::getReadableTime($this->statusDuration) : null;
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

    public function getLastValueReadable(): string
    {
        return number_format($this->lastValue, 1) . ' W';
    }

    public function setLastValue(float $lastValue): self
    {
        $this->lastValue = $lastValue;

        return $this;
    }
}
