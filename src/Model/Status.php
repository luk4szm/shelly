<?php

namespace App\Model;

enum Status: string
{
    case ACTIVE = 'running';
    case INACTIVE = 'standby';
}
