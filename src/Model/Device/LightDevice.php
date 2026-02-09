<?php

namespace App\Model\Device;

abstract class LightDevice extends Device
{
    public function getType(): string
    {
        return $this::TYPE;
    }
}
