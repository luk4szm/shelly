<?php

namespace App\Service\Shelly\Light;

use App\Model\Device\LightDevice;
use App\Service\Shelly\ShellyDeviceService;

readonly class ShellyLightService extends ShellyDeviceService
{
    public function turnOn(LightDevice $device, int $brightness = null, int $white = null, array $colors = []): array
    {
        return $this->curlRequest->light($device, 'on', $brightness, $white, $colors);
    }

    public function turnOff(LightDevice $device): array
    {
        return $this->curlRequest->light($device, 'off');
    }
}
