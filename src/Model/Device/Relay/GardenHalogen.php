<?php

declare(strict_types=1);

namespace App\Model\Device\Relay;

use App\Enum\ShellyComponentType;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;

final class GardenHalogen extends Relay implements ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const NAME           = 'garden_halogen';
    public const DEVICE_ID      = '345f45193b80';
    public const MDNS_HOSTNAME  = 'ShellyPlus2PM-345F45193B80.local';
    public const FALLBACK_IP    = '192.168.1.118';
    public const MODEL          = 'SNSW-102P16EU';
    public const GENERATION     = 2;
    public const PROFILE        = 'switch';
    public const COMPONENT_TYPE = ShellyComponentType::Switch;
    public const CHANNEL        = 0;
}
