<?php

namespace App\Model\Device\Valve;

final class OrielValve extends ValveDevice
{
    public const NAME             = 'hydration_valve_oriel';
    public const DEVICE_ID        = '9451dc0ac424';
    public const MDNS_HOSTNAME    = 'ShellyPlusRGBWPM-9451DC0AC424.local';
    public const FALLBACK_IP      = '192.168.1.73';
    public const CHANNEL          = 2;
    public const DEFAULT_DURATION = 8;
    public const PRIORITY         = 100;
}
