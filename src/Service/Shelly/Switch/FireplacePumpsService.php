<?php

namespace App\Service\Shelly\Switch;

use App\Model\Device\FireplacePump;

readonly class FireplacePumpsService extends ShellySwitchService
{
    public function turnOn(): void
    {
        $this->switch(FireplacePump::DEVICE_ID, FireplacePump::CHANNEL, 'on');
    }

    public function turnOff(): void
    {
        $this->switch(FireplacePump::DEVICE_ID, FireplacePump::CHANNEL, 'off');
    }
}
