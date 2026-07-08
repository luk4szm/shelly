<?php

namespace App\Model\Device;

abstract class Device implements DeviceInterface
{
    public function getName(): string
    {
        return $this::NAME;
    }
}
