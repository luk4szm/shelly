<?php

namespace App\Model\Device;

final class Boiler extends Device implements DeviceInterface
{
    public const NAME           = 'piec';
    public const DEVICE_ID      = '5432046b0fd8';
    public const BOUNDARY_POWER = 12;
}
