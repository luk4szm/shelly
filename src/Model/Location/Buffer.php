<?php

namespace App\Model\Location;

final class Buffer extends Location implements LocationInterface
{
    public const NAME      = 'bufor';
    public const DEVICE_ID = 'ecc9ff4b35e4';
    public const GROUP     = ['heating-full', 'heating', 'buffer'];
}
