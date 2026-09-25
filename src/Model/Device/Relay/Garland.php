<?php

namespace App\Model\Device\Relay;

use App\Enum\ShellyComponentType;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;

final class Garland extends Relay implements ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const NAME              = 'girlanda';
    public const DEVICE_ID         = '345f45193b80';
    public const MDNS_HOSTNAME     = 'ShellyPlus2PM-345F45193B80.local';
    public const FALLBACK_IP       = '192.168.1.118';
    public const MODEL             = 'SNSW-102P16EU';
    public const GENERATION        = 2;
    public const PROFILE           = 'switch';
    public const COMPONENT_TYPE    = ShellyComponentType::Switch;
    public const CHANNEL           = 1;
    public const BOUNDARY_POWER    = 5;
    public const INSTALLATION_DATE = '2026-05-30';
}
