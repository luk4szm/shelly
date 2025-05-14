<?php

namespace App\Model\Location;

class BufferCoil extends Location implements LocationInterface
{
    public const NAME  = 'bufor-solary';
    public const GROUP = ['heating', 'buffer'];
}
