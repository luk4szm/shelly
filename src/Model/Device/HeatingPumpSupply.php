<?php

namespace App\Model\Device;

final class HeatingPumpSupply extends Device implements DeviceInterface
{
    public const NAME              = 'pompa-zasilanie';
    public const DEVICE_ID         = 'ecc9ff4b35e4';
    public const CHANNEL           = 0;
    public const BOUNDARY_POWER    = 5;
    public const INSTALLATION_DATE = '2025-09-25';
}
