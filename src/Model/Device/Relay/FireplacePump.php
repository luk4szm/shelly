<?php

namespace App\Model\Device\Relay;

use App\Enum\ShellyComponentType;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;

final class FireplacePump extends Relay implements ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const NAME              = 'pompa-kominek';
    public const DEVICE_ID         = 'cc7b5c8378b4';
    public const MDNS_HOSTNAME     = 'ShellyPlus1PM-CC7B5C8378B4.local';
    public const FALLBACK_IP       = '192.168.1.16';
    public const MODEL             = 'SNSW-001P16EU';
    public const GENERATION        = 2;
    public const PROFILE           = null;
    public const COMPONENT_TYPE    = ShellyComponentType::Switch;
    public const CHANNEL           = 0;
    public const BOUNDARY_POWER    = 10; // Watts
    public const INSTALLATION_DATE = '2026-01-17';
}
