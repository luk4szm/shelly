<?php

namespace App\Model\Device\Hydration;

final class LeftSideValve extends ValveDevice
{
    public const NAME             = 'hydration_valve_left_side';
    public const DEVICE_ID        = '30c922573230';
    public const CHANNEL          = 3;
    public const DEFAULT_DURATION = 10;
    public const PRIORITY         = 80;
}
