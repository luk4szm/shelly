<?php

namespace App\Model\Device\Hydration;

use App\Model\Device\Device;
use App\Model\Device\DeviceInterface;

abstract class ValveDevice extends Device implements DeviceInterface
{
    public function getDeviceId(): string
    {
        return $this::DEVICE_ID;
    }

    public function getChannel(): int
    {
        return $this::CHANNEL;
    }
}
