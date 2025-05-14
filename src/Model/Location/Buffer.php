<?php

namespace App\Model\Location;

final class Buffer extends Location implements LocationInterface
{
    public const NAME  = 'bufor';
    public const GROUP = ['heating', 'buffer'];
}
