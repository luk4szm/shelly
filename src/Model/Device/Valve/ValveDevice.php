<?php

namespace App\Model\Device\Valve;

use App\Model\Device\Device;
use App\Model\Device\DeviceInterface;

abstract class ValveDevice extends Device implements ValveDeviceInterface
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
