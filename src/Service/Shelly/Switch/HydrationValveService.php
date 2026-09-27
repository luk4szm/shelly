<?php

declare(strict_types=1);

namespace App\Service\Shelly\Switch;

use App\Model\Device\Valve\ValveDevice;
use App\Service\Shelly\Local\ShellyValveWriter;

final readonly class HydrationValveService
{
    public function __construct(
        private ShellyValveWriter $valveWriter,
    ) {}

    public function start(ValveDevice $device, int $toggleAfter): void
    {
        $this->valveWriter->openValve($device, $toggleAfter);
    }

    public function stop(ValveDevice $device): void
    {
        $this->valveWriter->closeValve($device);
    }
}
