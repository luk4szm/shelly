<?php

namespace App\Model\Device;

use App\Model\Device\Device;
use App\Model\Device\DeviceInterface;

final class Hydrophore extends Device implements DeviceInterface
{
    public const NAME              = 'hydrofor';
    public const DEVICE_ID         = 'fce8c0fd0a7c';
    public const BOUNDARY_POWER    = 10;
    public const INSTALLATION_DATE = '2025-09-26';
}
