<?php

namespace App\Model\Device\Hydration;

final class RotatingValve extends ValveDevice
{
    public const NAME             = 'hydration_valve_rotating';
    public const DEVICE_ID        = '30c922573230';
    public const CHANNEL          = 2;
    public const DEFAULT_DURATION = 20;
    public const PRIORITY         = 60;
}
