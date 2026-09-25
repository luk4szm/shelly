<?php

declare(strict_types=1);

namespace App\Model\Device;

use App\Enum\ShellyComponentType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.shelly.rpc_device')]
interface ShellyRpcDeviceInterface extends DeviceInterface
{
    public function getDeviceId(): string;

    public function getHostname(): string;

    public function getFallbackIp(): ?string;

    public function getChannel(): int;

    public function getComponentType(): ShellyComponentType;

    public function getModel(): string;

    public function getGeneration(): int;

    public function getProfile(): string;
}
