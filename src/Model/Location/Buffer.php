<?php

namespace App\Model\Location;

final class Buffer extends Location implements LocationInterface
{
    public const NAME        = 'bufor';
    public const GROUP       = ['heating-full', 'heating', 'buffer'];
    public const CHART_COLOR = '#D63939';
}
