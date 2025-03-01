<?php

namespace App\Model\Device;

abstract class Device
{
    public function getName(): string
    {
        return $this::NAME;
    }
}
