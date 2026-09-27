<?php

namespace App\Model\Device\Relay;

use App\Enum\ShellyComponentType;
use App\Model\Device\ShellyRpcDevice;
use App\Model\Device\ShellyRpcDeviceInterface;

final class DrivewayLights extends Relay implements ShellyRpcDeviceInterface
{
    use ShellyRpcDevice;

    public const NAME              = 'driveway-lights';
    public const DEVICE_ID         = 'b0b21c103c08';
    public const MDNS_HOSTNAME     = 'ShellyPlus1PM-B0B21C103C08.local';
    public const FALLBACK_IP       = '192.168.1.195';
    public const MODEL             = 'SNSW-001P16EU';
    public const GENERATION        = 2;
    public const PROFILE           = null;
    public const COMPONENT_TYPE    = ShellyComponentType::Switch;
    public const CHANNEL           = 0;
    public const BOUNDARY_POWER    = 10;
    public const INSTALLATION_DATE = '2026-09-01';
}
