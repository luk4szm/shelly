<?php

namespace App\Model\Device\Valve;

use App\Model\Device\Device;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;
use App\Enum\ShellyComponentType;

abstract class ValveDevice extends Device implements ValveDeviceInterface, ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const MODEL          = 'SNDC-0D4P10WW';
    public const GENERATION     = 2;
    public const PROFILE        = 'light';
    public const COMPONENT_TYPE = ShellyComponentType::Light;

    public function __toString(): string
    {
        return $this->getName();
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
