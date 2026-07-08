<?php

namespace App\Model\Device\Relay;

use App\Model\Device\Device;

final class HeatingPumpSupply extends Device
{
    public const NAME              = 'pompa-zasilanie';
    public const DEVICE_ID         = 'ecc9ff4b35e4';
    public const CHANNEL           = 0;
    public const BOUNDARY_POWER    = 5;
    public const INSTALLATION_DATE = '2025-09-25';
}
