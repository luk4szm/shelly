<?php

namespace App\Service\Shelly\Switch;

use App\Model\Device\Relay\HotWaterPump;

final readonly class HotWaterPumpService extends ShellySwitchService
{
    public function turnOn(): void
    {
        $this->switch(HotWaterPump::DEVICE_ID, HotWaterPump::CHANNEL, 'on');
    }

    public function turnOff(): void
    {
        $this->switch(HotWaterPump::DEVICE_ID, HotWaterPump::CHANNEL, 'off');
    }
}
