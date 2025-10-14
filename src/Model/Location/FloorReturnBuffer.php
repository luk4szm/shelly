<?php

namespace App\Model\Location;

final class FloorReturnBuffer extends Location implements LocationInterface
{
    public const NAME        = 'podl-powrot-bufor';
    public const GROUP       = ['heating-full', 'underfloor-heating'];
    public const CHART_COLOR = '#1877F2';
}
