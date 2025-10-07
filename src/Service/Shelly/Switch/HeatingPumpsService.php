<?php

namespace App\Service\Shelly\Switch;

use App\Model\Device\HeatingPumpReturn;
use App\Model\Device\HeatingPumpSupply;

readonly class HeatingPumpsService extends ShellySwitchService
{
    public function turnOn(): void
    {
        $this->switch(HeatingPumpSupply::DEVICE_ID, HeatingPumpSupply::CHANNEL, 'on');
        sleep(2); // shelly cloud has limits requests per second
        $this->switch(HeatingPumpReturn::DEVICE_ID, HeatingPumpReturn::CHANNEL, 'on');
    }

    public function turnOff(): void
    {
        $this->switch(HeatingPumpSupply::DEVICE_ID, HeatingPumpSupply::CHANNEL, 'off');
        sleep(2); // shelly cloud has limits requests per second
        $this->switch(HeatingPumpReturn::DEVICE_ID, HeatingPumpReturn::CHANNEL, 'off');
    }
}
