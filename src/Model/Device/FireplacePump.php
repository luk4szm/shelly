<?php

namespace App\Model\Device;

final class FireplacePump extends Device implements DeviceInterface
{
    public const NAME              = 'pompa-kominek';
    public const DEVICE_ID         = 'cc7b5c8378b4';
    public const CHANNEL           = 0;
    public const BOUNDARY_POWER    = 10; // Watts
    public const INSTALLATION_DATE = '2026-01-17';
}
