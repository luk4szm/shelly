<?php

declare(strict_types=1);

namespace App\Model\Device\Cover;

use App\Enum\ShellyComponentType;
use App\Model\Device\Device;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;

final class RollerCover extends Device implements ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const NAME           = 'roleta';
    public const DEVICE_ID      = '2cbcbb2dc408';
    public const MDNS_HOSTNAME  = 'ShellyPlus2PM-2CBCBB2DC408.local';
    public const FALLBACK_IP    = '192.168.1.51';
    public const MODEL          = 'SNSW-102P16EU';
    public const GENERATION     = 2;
    public const PROFILE        = 'cover';
    public const COMPONENT_TYPE = ShellyComponentType::Cover;
    public const CHANNEL        = 0;
}
