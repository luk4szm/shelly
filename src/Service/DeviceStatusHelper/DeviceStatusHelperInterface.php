<?php

namespace App\Service\DeviceStatusHelper;

use App\Entity\Hook;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.device_status_helper')]
interface DeviceStatusHelperInterface
{
    public function supports(string $device): bool;

    public function isActive(Hook $hook): bool;
}
