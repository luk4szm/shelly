<?php

namespace App\Service\Shelly\Light;

use App\Service\Shelly\ShellyDeviceService;

readonly class ShellyLightService extends ShellyDeviceService
{
    public function turnOn(string $deviceId, int $channel, int $brightness): array
    {
        return $this->curlRequest->light($deviceId, 'on', $channel, $brightness);
    }

    public function turnOff(string $deviceId, int $channel): array
    {
        return $this->curlRequest->light($deviceId, 'off', $channel);
    }
}
