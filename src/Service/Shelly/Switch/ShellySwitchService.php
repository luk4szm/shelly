<?php

namespace App\Service\Shelly\Switch;

use App\Service\Shelly\ShellyDeviceService;

readonly class ShellySwitchService extends ShellyDeviceService
{
    public function switch(string $deviceId, int $channel, string $action): array
    {
        return $this->curlRequest->switch($deviceId, $channel, $action);
    }

    public function getStatus(string $deviceId): array
    {
        return $this->curlRequest->getStatus($deviceId);
    }
}
