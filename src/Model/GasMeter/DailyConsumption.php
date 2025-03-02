<?php

namespace App\Model\GasMeter;

use App\Utils\TimeUtils;

class DailyConsumption
{
    public const GAS_PRICE = 380; // estimated price of m3 gas in pennies

    private float $consumption;
    private int   $boilerActiveTime;
    private float $energyUsed;
    private float $boilerInclusions;

    public function setConsumption(float $consumption): static
    {
        $this->consumption = round($consumption, 3);

        return $this;
    }

    public function getConsumption(): float
    {
        return $this->consumption;
    }

    public function setBoilerActiveTime(int $boilerActiveTime): static
    {
        $this->boilerActiveTime = $boilerActiveTime;

        return $this;
    }

    public function getBoilerActiveTime(): int
    {
        return $this->boilerActiveTime;
    }

    public function getBoilerActiveTimeReadable(): string
    {
        return TimeUtils::getReadableTime($this->boilerActiveTime);
    }

    public function getBoilerAverageRuntimeReadable(): string
    {
        return TimeUtils::getReadableTime($this->boilerActiveTime / $this->boilerInclusions);
    }

    public function setEnergyUsed(float $energyUsed): static
    {
        $this->energyUsed = $energyUsed;

        return $this;
    }

    public function getEnergyUsed(): float
    {
        return $this->energyUsed;
    }

    public function setBoilerInclusions(float $boilerInclusions): static
    {
        $this->boilerInclusions = $boilerInclusions;

        return $this;
    }

    public function getBoilerInclusions(): float
    {
        return $this->boilerInclusions;
    }

    public function getAverageFuelConsumePerRuntime(): float
    {
        return round($this->consumption / $this->boilerInclusions, 3);
    }

    public function getEstimatedCost(): float
    {
        return round($this->consumption * self::GAS_PRICE / 100, 2);
    }
}
