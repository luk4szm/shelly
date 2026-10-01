<?php

namespace App\Model\Device\Light;

final class BedLeds extends LocalRgbwLightDevice
{
    public const NAME          = 'bed_leds';
    public const DEVICE_ID     = '9451dc0ab154';
    public const MDNS_HOSTNAME = 'ShellyPlusRGBWPM-9451DC0AB154.local';
    public const FALLBACK_IP   = '192.168.1.105';
    public const CHANNEL       = 0;
}
