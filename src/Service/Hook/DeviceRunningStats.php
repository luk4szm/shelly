<?php

namespace App\Service\Hook;

class DeviceRunningStats
{
    private array $hooks;
    private float $energy            = 0; // Ws
    private int   $runningTime       = 0; // seconds
    private int   $inclusionsCounter = 0;

    public function __construct(
        private readonly DeviceStatusHelper $statusHelper,
    ) {
    }

    public function process(\DateTimeInterface $date, array $hooks): void
    {
        array_unshift($hooks, (clone $hooks[0])->setCreatedAt($date->setTime(0, 0)));

        $this->hooks ??= $hooks;

        for ($i = 0; $i < count($hooks); $i++) {
            $isActive = $this->statusHelper->isActive('piec', $hooks[$i]);
            $duration = $i !== count($hooks) - 1
                ? $hooks[$i]->getCreatedAt()->diff($hooks[$i + 1]->getCreatedAt())
                : $hooks[$i]->getCreatedAt()->diff(new \DateTime());

            $duration = $duration->h * 3600 + $duration->i * 60 + $duration->s;

            $this->energy += $hooks[$i]->getValue() * $duration;

            if ($isActive) {
                if (
                    $i !== 0
                    && !$this->statusHelper->isActive('piec', $hooks[$i - 1])
                ) {
                    $this->inclusionsCounter++;
                }

                $this->runningTime += $duration;
            }
        }
    }

    public function getRunningTime(): string
    {
        return gmdate("H:i:s", $this->runningTime);
    }

    public function getEnergy(): float
    {
        return number_format($this->energy / 3600000, 2);
    }

    public function getInclusionsCounter(): int
    {
        return $this->inclusionsCounter;
    }
}
