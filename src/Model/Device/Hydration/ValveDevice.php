<?php

namespace App\Model\Device\Hydration;

use App\Model\Device\Device;
use App\Model\Device\DeviceInterface;
use App\Model\Device\ValveDeviceInterface;

abstract class ValveDevice extends Device implements DeviceInterface, ValveDeviceInterface
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
