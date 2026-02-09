<?php

namespace App\Model\Device;

final class BedLeds extends LightDevice implements DeviceInterface
{
    public const NAME      = 'bed_leds';
    public const TYPE      = 'rgbw';
    public const DEVICE_ID = '9451dc0ab154';
    public const CHANNEL   = 0;
}
