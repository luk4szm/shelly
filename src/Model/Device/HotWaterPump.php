<?php

namespace App\Model\Device;

final class HotWaterPump extends Device implements DeviceInterface
{
    public const NAME              = 'pompa-recyrkulacja';
    public const DEVICE_ID         = 'e4b3233b00ac';
    public const CHANNEL           = 0;
    public const BOUNDARY_POWER    = 5;
    public const INSTALLATION_DATE = '2026-06-28';
}
