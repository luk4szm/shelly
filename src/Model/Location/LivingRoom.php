<?php

namespace App\Model\Location;

final class LivingRoom extends Location implements LocationInterface
{
    public const NAME  = 'salon';
    public const GROUP = ['rooms'];
}
