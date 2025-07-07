<?php

namespace App\Model\Location;

class Garage extends Location implements LocationInterface
{
    public const NAME      = 'garage';
    public const DEVICE_ID = '1C6920097BBC';
    public const GROUP     = ['rooms'];
}
