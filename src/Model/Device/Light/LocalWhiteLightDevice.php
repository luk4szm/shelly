<?php

declare(strict_types=1);

namespace App\Model\Device\Light;

use App\Enum\ShellyComponentType;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;

abstract class LocalWhiteLightDevice extends LightDevice implements ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const TYPE           = 'white';
    public const MODEL          = 'SNDC-0D4P10WW';
    public const GENERATION     = 2;
    public const PROFILE        = 'light';
    public const COMPONENT_TYPE = ShellyComponentType::Light;
}
