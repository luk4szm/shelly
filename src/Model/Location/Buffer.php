<?php

namespace App\Model\Location;

final class Buffer extends Location implements LocationInterface
{
    public const NAME      = 'bufor';
    public const DEVICE_ID = 'b0a7324d5034';
    public const GROUP     = ['heating-full', 'heating', 'buffer'];
}
