<?php

namespace App\Model\GasMeter;

class DailyConsumption
{
    private float $consumption;

    public function setConsumption(float $consumption): static
    {
        $this->consumption = $consumption;

        return $this;
    }

    public function getConsumption(): float
    {
        return $this->consumption;
    }
}
