<?php

declare(strict_types=1);

namespace App\Model\Device;

use App\Enum\ShellyComponentType;

trait ShellyRpcDevice
{
    public function getDeviceId(): string
    {
        return $this::DEVICE_ID;
    }

    public function getHostname(): string
    {
        return $this::MDNS_HOSTNAME;
    }

    public function getFallbackIp(): ?string
    {
        return defined($this::class . '::FALLBACK_IP') ? $this::FALLBACK_IP : null;
    }

    public function getChannel(): int
    {
        return $this::CHANNEL;
    }

    public function getComponentType(): ShellyComponentType
    {
        return $this::COMPONENT_TYPE;
    }

    public function getModel(): string
    {
        return $this::MODEL;
    }

    public function getGeneration(): int
    {
        return $this::GENERATION;
    }

    public function getProfile(): string
    {
        return $this::PROFILE;
    }
}
