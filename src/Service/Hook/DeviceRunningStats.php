<?php

namespace App\Service\Hook;

use App\Entity\Hook;

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

    /**
     * @param \DateTimeInterface $date
     * @param array{Hook}        $hooks
     * @return void
     * @throws \DateMalformedStringException
     */
    public function process(\DateTimeInterface $date, array $hooks): void
    {
        if ($date->format("Y-z") !== $hooks[0]->getCreatedAt()->format("Y-z")) {
            $hooks[0]->setCreatedAt((clone $date)->setTime(0, 0));
        }

        $this->hooks ??= $hooks;

        for ($i = 0; $i < count($hooks); $i++) {
            $isActive = $this->statusHelper->isActive('piec', $hooks[$i]);
            $duration = $this->statusHelper->calculateHookDuration($hooks[$i], $hooks[$i+1] ?? null);

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

    public function getEnergy(string $unit = 'kWh'): float
    {
        return match ($unit) {
            'Wh'    => round($this->energy / 3600, 1),
            default => round($this->energy / 3600000, 2), // kWh
        };
    }

    public function getInclusionsCounter(): int
    {
        return $this->inclusionsCounter;
    }
}
