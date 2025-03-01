<?php

namespace App\Model\Device;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.devices')]
interface DeviceInterface
{
    public function getName(): string;
}
