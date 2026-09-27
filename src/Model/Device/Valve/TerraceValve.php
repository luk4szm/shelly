<?php

namespace App\Model\Device\Valve;

final class TerraceValve extends ValveDevice
{
    public const NAME             = 'hydration_valve_terrace';
    public const DEVICE_ID        = '30c922573230';
    public const MDNS_HOSTNAME    = 'ShellyPlusRGBWPM-30C922573230.local';
    public const FALLBACK_IP      = '192.168.1.99';
    public const CHANNEL          = 1;
    public const DEFAULT_DURATION = 10;
    public const PRIORITY         = 20;
}
