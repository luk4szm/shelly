<?php

namespace App\Model\Device\Relay;

use App\Enum\ShellyComponentType;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;

final class HeatingPumpReturn extends Relay implements ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const NAME              = 'pompa-powrot';
    public const DEVICE_ID         = 'ecc9ff4b35e4';
    public const MDNS_HOSTNAME     = 'ShellyPlus2PM-ECC9FF4B35E4.local';
    public const FALLBACK_IP       = '192.168.1.167';
    public const MODEL             = 'SNSW-102P16EU';
    public const GENERATION        = 2;
    public const PROFILE           = 'switch';
    public const COMPONENT_TYPE    = ShellyComponentType::Switch;
    public const CHANNEL           = 1;
    public const BOUNDARY_POWER    = 5;
    public const INSTALLATION_DATE = '2025-09-25';
}
