<?php

namespace App\Model\Device;

final class TvLedsMonitor extends LightDevice implements DeviceInterface
{
    public const NAME      = 'tv_leds_monitor';
    public const TYPE      = 'white';
    public const DEVICE_ID = 'ecc9ff4dc3f4';
    public const CHANNEL   = 2;
}
