<?php

namespace App\Model\Location;

final class FloorReturnRecirculation extends Location implements LocationInterface
{
    public const NAME  = 'podl-powrot-recyrkulacja';
    public const GROUP = ['heating-full', 'underfloor-heating'];
}
