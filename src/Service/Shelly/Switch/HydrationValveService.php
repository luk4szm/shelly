<?php

namespace App\Service\Shelly\Switch;

use App\Model\Device\Hydration\ValveDevice;
use App\Service\Shelly\ShellyDeviceService;

readonly class HydrationValveService extends ShellyDeviceService
{
    public function start(ValveDevice $device, int $toggleAfter = 0): void
    {
        $this->curlRequest->valve($device, 'on', $toggleAfter);
    }

    public function stop(ValveDevice $device): void
    {
        $this->curlRequest->valve($device, 'off');
    }
}
