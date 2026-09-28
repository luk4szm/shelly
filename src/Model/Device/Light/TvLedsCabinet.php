<?php

namespace App\Model\Device\Light;

final class TvLedsCabinet extends LocalWhiteLightDevice
{
    public const NAME          = 'tv_leds_cabinet';
    public const TYPE          = 'white';
    public const DEVICE_ID     = 'ecc9ff4dc3f4';
    public const MDNS_HOSTNAME = 'ShellyPlusRGBWPM-ECC9FF4DC3F4.local';
    public const FALLBACK_IP   = '192.168.1.120';
    public const CHANNEL       = 1;
}
