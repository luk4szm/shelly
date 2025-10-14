<?php

namespace App\Model\Location;

final class BufferCoil extends Location implements LocationInterface
{
    public const NAME        = 'bufor-solary';
    public const GROUP       = ['heating-full', 'heating', 'buffer'];
    public const CHART_COLOR = '#F76707';
}
