<?php

namespace App\Model\Location;

final class Fireplace extends Location implements LocationInterface
{
    public const NAME        = 'kominek';
    public const GROUP       = ['heating-full'];
    public const CHART_COLOR = '#f5f542';
}
