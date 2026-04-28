<?php

namespace App\Model\Device\Hydration;

use App\Model\Device\Device;
use App\Model\Device\DeviceInterface;
use App\Model\Device\ValveDeviceInterface;

abstract class ValveDevice extends Device implements DeviceInterface, ValveDeviceInterface
{
    public function __toString(): string
    {
        return $this->getName();
    }

    public function getDeviceId(): string
    {
        return $this::DEVICE_ID;
    }

    public function getChannel(): int
    {
        return $this::CHANNEL;
    }

    public function getDefaultDuration(): int
    {
        return static::DEFAULT_DURATION;
    }

    public static function getPriority(): int
    {
        return static::PRIORITY;
    }
}
