<?php

namespace App\Model\Location;

final class FloorSupply extends Location implements LocationInterface
{
    public const NAME        = 'podl-zasilanie';
    public const GROUP       = ['heating-full', 'underfloor-heating'];
    public const CHART_COLOR = '#74B816';
}
