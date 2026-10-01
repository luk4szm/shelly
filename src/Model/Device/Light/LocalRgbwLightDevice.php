<?php

declare(strict_types=1);

namespace App\Model\Device\Light;

use App\Enum\ShellyComponentType;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;

abstract class LocalRgbwLightDevice extends LightDevice implements ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const TYPE           = 'rgbw';
    public const MODEL          = 'SNDC-0D4P10WW';
    public const GENERATION     = 2;
    public const PROFILE        = 'rgbw';
    public const COMPONENT_TYPE = ShellyComponentType::Rgbw;
}
