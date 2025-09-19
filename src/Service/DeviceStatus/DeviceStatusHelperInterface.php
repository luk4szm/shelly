<?php

namespace App\Service\DeviceStatus;

use App\Entity\Hook;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.device_status_helper')]
interface DeviceStatusHelperInterface
{
    public function supports(string $device): bool;

    public function getDeviceName(): string;

    public function getDeviceId(): string;

    public function isActive(Hook $hook): bool;

    public function showOnDashboard(): bool;
}
