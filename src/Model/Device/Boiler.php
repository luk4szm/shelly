<?php

namespace App\Model\Device;

final class Boiler extends Device implements DeviceInterface
{
    public const NAME           = 'piec';
    public const BOUNDARY_POWER = 12;
}
