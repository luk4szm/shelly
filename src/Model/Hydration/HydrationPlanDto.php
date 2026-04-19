<?php

namespace App\Model\Hydration;

use App\Model\Device\Hydration\ValveDevice;

class HydrationPlanDto
{
    private ValveDevice $valve;

    private ?\DateTimeImmutable $scheduledStartAt = null;

    private ?\DateTimeImmutable $startsAt = null;

    private ?\DateTimeImmutable $scheduledEndAt = null;

    private ?\DateTimeImmutable $endsAt = null;

    private ?int $duration = null;

    public function __construct(ValveDevice $valve)
    {
        $this->valve = $valve;
    }

    public function getValve(): ValveDevice
    {
        return $this->valve;
    }

    public function setValve(ValveDevice $valve): static
    {
        $this->valve = $valve;

        return $this;
    }

    public function getScheduledStartAt(): ?\DateTimeImmutable
    {
        return $this->scheduledStartAt;
    }

    public function setScheduledStartAt(?\DateTimeImmutable $scheduledStartAt): static
    {
        $this->scheduledStartAt = $scheduledStartAt;

        return $this;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(?\DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getScheduledEndAt(): ?\DateTimeImmutable
    {
        return $this->scheduledEndAt;
    }

    public function setScheduledEndAt(?\DateTimeImmutable $scheduledEndAt): static
    {
        $this->scheduledEndAt = $scheduledEndAt;

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function calculateActualProgress(): int
    {
        if (null === $this->startsAt) {
            return 0;
        }

        $now = new \DateTimeImmutable();

        // If the start is in the future, the progress is 0
        if ($this->startsAt > $now) {
            return 0;
        }

        // Calculate the difference in seconds between "now" and the start time
        return $now->getTimestamp() - $this->startsAt->getTimestamp();
    }
}
