<?php

namespace App\Model\Device\Relay;

use App\Enum\ShellyComponentType;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;

final class Hydrophore extends Relay implements ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const NAME              = 'hydrofor';
    public const DEVICE_ID         = 'fce8c0fd0a7c';
    public const MDNS_HOSTNAME     = 'ShellyPlus1PM-FCE8C0FD0A7C.local';
    public const FALLBACK_IP       = '192.168.1.83';
    public const MODEL             = 'SNSW-001P16EU';
    public const GENERATION        = 2;
    public const PROFILE           = null;
    public const COMPONENT_TYPE    = ShellyComponentType::Switch;
    public const CHANNEL           = 0;
    public const BOUNDARY_POWER    = 10;
    public const INSTALLATION_DATE = '2025-09-26';
}
