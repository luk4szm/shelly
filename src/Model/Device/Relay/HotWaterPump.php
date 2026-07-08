<?php

namespace App\Model\Device\Relay;

use App\Model\Device\Device;

final class HotWaterPump extends Device
{
    public const NAME              = 'pompa-cwu';
    public const DEVICE_ID         = '64b708097270';
    public const CHANNEL           = 0;
    public const BOUNDARY_POWER    = 5;
    public const INSTALLATION_DATE = '2026-07-06';
}
