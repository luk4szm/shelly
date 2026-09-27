<?php

namespace App\Model\Device\Relay;

use App\Enum\ShellyComponentType;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;

final class HotWaterPump extends Relay implements ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const NAME              = 'pompa-cwu';
    public const DEVICE_ID         = '64b708097270';
    public const MDNS_HOSTNAME     = 'ShellyPlus1PM-64B708097270.local';
    public const FALLBACK_IP       = '192.168.1.239';
    public const MODEL             = 'SNSW-001P16EU';
    public const GENERATION        = 2;
    public const PROFILE           = null;
    public const COMPONENT_TYPE    = ShellyComponentType::Switch;
    public const CHANNEL           = 0;
    public const BOUNDARY_POWER    = 5;
    public const INSTALLATION_DATE = '2026-07-06';
}
