<?php

namespace App\Model\Hydration;

use App\Model\Device\Valve\ValveDevice;

class GroupedHydrationHistoryDto
{
    private ValveDevice $valve;
    private int         $totalDuration   = 0;
    private array       $hydrationEvents = [];

    public function __construct(ValveDevice $valve)
    {
        $this->valve = $valve;
    }

    public function getValve(): ValveDevice
    {
        return $this->valve;
    }

    public function getTotalDuration(): int
    {
        return $this->totalDuration;
    }

    public function addHydrationEvent(\DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt, int $duration): void
    {
        $this->hydrationEvents[] = [
            'startsAt' => $startsAt,
            'endsAt'   => $endsAt,
        ];
        $this->totalDuration += $duration;
    }

    public function getHydrationEvents(): array
    {
        return $this->hydrationEvents;
    }
}
