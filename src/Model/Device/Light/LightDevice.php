<?php

namespace App\Model\Device\Light;

use App\Model\Device\Device;

abstract class LightDevice extends Device
{
    public function getType(): string
    {
        return $this::TYPE;
    }
}
