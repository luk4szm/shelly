<?php

namespace App\Model;

class DeviceStatus
{
    private Status $status;
    private int    $statusDuration; // seconds
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

    public function getStatusDuration(): int
    {
        return $this->statusDuration;
    }

    public function getStatusDurationReadable(): string
    {
        return gmdate("G:i:s", $this->statusDuration);
    }

    public function setStatusDuration(int $statusDuration): self
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
